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
        ray($data)->purple();

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
        $sessionId = $data['session_id'] ?? null;

        // Check if we have a nested message structure with content blocks
        if (isset($data['message']['content']) && is_array($data['message']['content'])) {
            $content = [];
            foreach ($data['message']['content'] as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $parsedBlock = $this->parseContentBlock($block);
                if ($parsedBlock !== null) {
                    $content[] = $parsedBlock;
                }
            }

            return new UserMessage($content, $sessionId);
        }

        // Fall back to simple string content for backward compatibility
        return new UserMessage($data['content'] ?? '', $sessionId);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function parseAssistantMessage(array $data): AssistantMessage
    {
        $content = [];
        $sessionId = $data['session_id'] ?? null;

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

        return new AssistantMessage($content, $sessionId);
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
        return new ResultMessage(
            subtype: $data['subtype'] ?? '',
            durationMs: $data['duration_ms'] ?? 0.0,
            durationApiMs: $data['duration_api_ms'] ?? 0.0,
            isError: $data['is_error'] ?? false,
            numTurns: $data['num_turns'] ?? 0,
            sessionId: $data['session_id'] ?? '',
            totalCostUsd: $data['total_cost_usd'] ?? 0.0,
            result: $data['result'] ?? null,
            usage: $data['usage'] ?? null,
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
