<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Internal;

use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ContentBlock;
use HelgeSverre\ClaudeCode\Types\Message;
use HelgeSverre\ClaudeCode\Types\ResultMessage;
use HelgeSverre\ClaudeCode\Types\SystemMessage;
use HelgeSverre\ClaudeCode\Types\TextBlock;
use HelgeSverre\ClaudeCode\Types\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\UserMessage;

class MessageParser
{
    /**
     * @param array<string, mixed> $data
     */
    public function parse(array $data): ?Message
    {
        $type = $data['type'] ?? null;

        return match ($type) {
            'user' => $this->parseUserMessage($data),
            'assistant' => $this->parseAssistantMessage($data),
            'system' => $this->parseSystemMessage($data),
            'result' => $this->parseResultMessage($data),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function parseUserMessage(array $data): UserMessage
    {
        return new UserMessage($data['content'] ?? '');
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function parseAssistantMessage(array $data): AssistantMessage
    {
        $content = [];

        // Handle wrapped message format
        $messageData = $data['message'] ?? $data;

        if (isset($messageData['content']) && is_array($messageData['content'])) {
            foreach ($messageData['content'] as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $parsedBlock = $this->parseContentBlock($block);
                if ($parsedBlock !== null) {
                    $content[] = $parsedBlock;
                }
            }
        }

        return new AssistantMessage($content);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function parseSystemMessage(array $data): SystemMessage
    {
        // Extract all data except type and subtype
        $systemData = $data;
        unset($systemData['type'], $systemData['subtype']);

        return new SystemMessage(
            $data['subtype'] ?? '',
            $systemData,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function parseResultMessage(array $data): ResultMessage
    {
        // Parse session data
        $session = null;
        if (isset($data['session_id'])) {
            $session = [
                'id' => $data['session_id'],
                'turns' => $data['num_turns'] ?? null,
            ];
        }

        return new ResultMessage(
            cost: $data['total_cost_usd'] ?? null,
            usage: $data['usage'] ?? null,
            model: null, // Model is not in result message
            session: $session,
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    protected function parseContentBlock(array $block): ?ContentBlock
    {
        $type = $block['type'] ?? null;

        return match ($type) {
            'text' => new TextBlock($block['text'] ?? ''),
            'tool_use' => new ToolUseBlock(
                $block['id'] ?? '',
                $block['name'] ?? '',
                $block['input'] ?? [],
            ),
            'tool_result' => new ToolResultBlock(
                $block['tool_use_id'] ?? '',
                $block['content'] ?? '',
                $block['is_error'] ?? null,
            ),
            default => null,
        };
    }
}
