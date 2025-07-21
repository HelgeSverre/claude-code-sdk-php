<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Examples\Interceptors;

/**
 * File logger interceptor that logs all Claude Code events to a file
 */
readonly class FileLoggerInterceptor
{
    public function __construct(
        public string $logFile = 'claude.log',
    ) {}

    public function __invoke(string $event, mixed $data): void
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'event' => $event,
            'data' => $data,
        ];

        file_put_contents(
            $this->logFile,
            json_encode($entry, JSON_PRETTY_PRINT) . "\n\n",
            FILE_APPEND | LOCK_EX,
        );
    }
}
