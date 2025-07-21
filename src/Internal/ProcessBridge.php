<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Internal;

use Closure;
use Exception;
use Generator;
use HelgeSverre\ClaudeCode\Exceptions\CLIConnectionException;
use HelgeSverre\ClaudeCode\Exceptions\CLIJSONDecodeException;
use HelgeSverre\ClaudeCode\Exceptions\CLINotFoundException;
use HelgeSverre\ClaudeCode\Exceptions\ProcessException;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use JsonException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ProcessBridge
{
    protected const int|float MAX_BUFFER_SIZE = 1024 * 1024; // 1MB

    protected ?Process $process = null;

    protected ?string $mcpConfigTempFile = null;

    public function __construct(
        protected readonly string $prompt,
        protected readonly Options $options,
        protected readonly ?string $cliPath = null,
        protected readonly ?Closure $onRawMessage = null,
        protected MessageParser $messageParser = new MessageParser,
    ) {}

    public function connect(): void
    {
        if ($this->process !== null) {
            return;
        }

        $command = $this->buildCommand();

        // Check working directory before creating process
        if ($this->options->cwd && ! is_dir($this->options->cwd)) {
            throw new CLIConnectionException(
                "Working directory does not exist: {$this->options->cwd}",
            );
        }

        $this->process = new Process(
            command: $command,
            cwd: $this->options->cwd,
            env: array_merge($_ENV, [
                'CLAUDE_CODE_ENTRYPOINT' => 'sdk-php',
            ]),
            timeout: null,
        );

        try {
            $this->process->start();
        } catch (Exception $e) {
            // TODO: rethrow loses context?
            throw new CLIConnectionException("Failed to start Claude Code: {$e->getMessage()}", 0, $e);
        }
    }

    public function disconnect(): void
    {
        if ($this->process === null) {
            return;
        }

        if ($this->process->isRunning()) {
            $this->process->stop(5.0);
        }

        $this->process = null;

        // Clean up temporary MCP config file
        if ($this->mcpConfigTempFile !== null && file_exists($this->mcpConfigTempFile)) {
            unlink($this->mcpConfigTempFile);
            $this->mcpConfigTempFile = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->process !== null && $this->process->isRunning();
    }

    public function send(mixed $message): void
    {
        if (! $this->isConnected()) {
            throw new CLIConnectionException('Not connected');
        }

        // Not implemented for ProcessBridge as communication is one-way
    }

    public function receive(): Generator
    {
        return $this->receiveMessages();
    }

    /**
     * @throws ProcessException
     * @throws CLIJSONDecodeException
     * @throws CLIConnectionException
     */
    public function receiveMessages(): Generator
    {
        if (! $this->isConnected()) {
            throw new CLIConnectionException('Not connected');
        }

        $jsonBuffer = '';
        $iterator = $this->process->getIterator(Process::ITER_SKIP_ERR | Process::ITER_KEEP_OUTPUT);

        foreach ($iterator as $data) {
            $lines = explode("\n", trim($data));

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $jsonBuffer .= $line;

                if (strlen($jsonBuffer) > self::MAX_BUFFER_SIZE) {
                    $jsonBuffer = '';
                    throw new CLIJSONDecodeException(
                        sprintf(
                            'JSON message exceeded maximum buffer size of %d bytes',
                            self::MAX_BUFFER_SIZE,
                        ),
                    );
                }

                try {
                    $decoded = json_decode($jsonBuffer, true, 512, JSON_THROW_ON_ERROR);
                    $jsonBuffer = '';

                    if (is_array($decoded)) {
                        if ($this->onRawMessage !== null) {
                            call_user_func($this->onRawMessage, $decoded);
                        }

                        $message = $this->messageParser->parse($decoded);
                        if ($message !== null) {
                            yield $message;
                        }
                    }
                } catch (JsonException) {
                    // Continue accumulating buffer
                }
            }
        }

        // Check process exit status
        $exitCode = $this->process->getExitCode();
        $stderr = $this->process->getErrorOutput();

        if ($exitCode !== null && $exitCode !== 0) {
            throw new ProcessException(
                "Command failed with exit code {$exitCode}",
                $exitCode,
                $stderr,
            );
        }
    }

    /**
     * @return array<string>
     *
     * @throws CLINotFoundException
     * @throws JsonException
     */
    protected function buildCommand(): array
    {
        $cliPath = $this->cliPath ?? $this->findCLI();

        $cmd = [$cliPath, '--output-format', 'stream-json', '--verbose'];

        if ($this->options->systemPrompt !== null) {
            $cmd[] = '--system-prompt';
            $cmd[] = $this->options->systemPrompt;
        }

        if ($this->options->appendSystemPrompt !== null) {
            $cmd[] = '--append-system-prompt';
            $cmd[] = $this->options->appendSystemPrompt;
        }

        if ($this->options->allowedTools !== null) {
            $cmd[] = '--allowedTools';
            $cmd[] = implode(',', $this->options->allowedTools);
        }

        if ($this->options->maxTurns !== null) {
            $cmd[] = '--max-turns';
            $cmd[] = (string) $this->options->maxTurns;
        }

        if ($this->options->disallowedTools !== null) {
            $cmd[] = '--disallowedTools';
            $cmd[] = implode(',', $this->options->disallowedTools);
        }

        if ($this->options->model !== null) {
            $cmd[] = '--model';
            $cmd[] = $this->options->model;
        }

        if ($this->options->permissionPromptToolName !== null) {
            $cmd[] = '--permission-prompt-tool';
            $cmd[] = $this->options->permissionPromptToolName;
        }

        if ($this->options->permissionMode !== null) {
            $cmd[] = '--permission-mode';
            $cmd[] = $this->options->permissionMode->value;
        }

        if ($this->options->continueConversation === true) {
            $cmd[] = '--continue';
        }

        if ($this->options->resume !== null) {
            $cmd[] = '--resume';
            $cmd[] = $this->options->resume;
        }

        if ($this->options->mcpServers !== null) {
            // Write MCP config to temporary file
            $mcpConfig = ['mcpServers' => $this->options->toArray()['mcpServers']];
            $tempFile = tempnam(sys_get_temp_dir(), 'claude_mcp_') . '.json';
            file_put_contents($tempFile, json_encode($mcpConfig, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            // Store temp file path for cleanup
            $this->mcpConfigTempFile = $tempFile;

            $cmd[] = '--mcp-config';
            $cmd[] = $tempFile;
        }

        // $cmd[] = '--debug';
        $cmd[] = '--print';
        $cmd[] = $this->prompt;

        return $cmd;
    }

    protected function findCLI(): string
    {
        $finder = new ExecutableFinder;

        if ($claude = $finder->find('claude')) {
            return $claude;
        }

        $locations = [
            $_SERVER['HOME'] . '/.npm-global/bin/claude',
            '/usr/local/bin/claude',
            $_SERVER['HOME'] . '/.local/bin/claude',
            $_SERVER['HOME'] . '/node_modules/.bin/claude',
            $_SERVER['HOME'] . '/.yarn/bin/claude',
        ];

        foreach ($locations as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        $nodeInstalled = $finder->find('node') !== null;

        if (! $nodeInstalled) {
            throw new CLINotFoundException(
                "Claude Code requires Node.js, which is not installed.\n\n" .
                "Install Node.js from: https://nodejs.org/\n" .
                "\nAfter installing Node.js, install Claude Code:\n" .
                '  npm install -g @anthropic-ai/claude-code',
            );
        }

        throw new CLINotFoundException(
            "Claude Code not found. Install with:\n" .
            "  npm install -g @anthropic-ai/claude-code\n" .
            "\nIf already installed locally, try:\n" .
            '  export PATH="$HOME/node_modules/.bin:$PATH"' . "\n" .
            "\nOr specify the path when creating transport:\n" .
            "  new SubprocessCLITransport(..., cliPath: '/path/to/claude')",
        );
    }
}
