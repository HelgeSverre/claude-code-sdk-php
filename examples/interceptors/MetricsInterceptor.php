<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Examples\Interceptors;

use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;

/**
 * Metrics collector interceptor that tracks usage statistics
 */
class MetricsInterceptor
{
    protected array $metrics = [];

    protected float $startTime = 0;

    public function __invoke(string $event, mixed $data): void
    {
        switch ($event) {
            case 'onQueryStart':
                $this->startTime = microtime(true);
                $this->metrics = [
                    'prompt_length' => strlen($data['prompt']),
                    'start_time' => $this->startTime,
                    'messages' => [],
                ];
                break;

            case 'onMessageParsed':
                $this->metrics['messages'][] = [
                    'type' => $data['type'],
                    'timestamp' => $data['timestamp'],
                ];

                // If it's a result message, extract token usage
                if ($data['message'] instanceof ResultMessage) {
                    $this->metrics['usage'] = $data['message']->usage;
                    $this->metrics['total_cost_usd'] = $data['message']->totalCostUsd;
                }
                break;

            case 'onQueryComplete':
                $this->metrics['duration'] = microtime(true) - $this->startTime;
                $this->metrics['message_count'] = count($this->metrics['messages']);
                // Send to monitoring service
                $this->sendMetrics($this->metrics);
                break;

            case 'onError':
                $this->metrics['error'] = $data['error'];
                $this->metrics['duration'] = microtime(true) - $this->startTime;
                $this->sendMetrics($this->metrics);
                break;
        }
    }

    private function sendMetrics(array $metrics): void
    {
        // In a real implementation, this would send to a monitoring service
        // For demo purposes, we'll just log to stderr
        error_log('Claude Code Metrics: ' . json_encode($metrics));
    }
}
