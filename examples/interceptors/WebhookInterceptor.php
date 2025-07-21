<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Examples\Interceptors;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Webhook interceptor that sends events to an HTTP endpoint
 */
class WebhookInterceptor
{
    private Client $client;

    private string $webhookUrl;

    private array $events;

    /**
     * @param string $webhookUrl The URL to send events to
     * @param array $events Array of events to send (default: ['onQueryStart', 'onQueryComplete', 'onError'])
     */
    public function __construct(string $webhookUrl, array $events = ['onQueryStart', 'onQueryComplete', 'onError'])
    {
        $this->webhookUrl = $webhookUrl;
        $this->events = $events;
        $this->client = new Client(['timeout' => 5]);
    }

    public function __invoke(string $event, mixed $data): void
    {
        // Only send specified events to webhook
        if (in_array($event, $this->events)) {
            try {
                $this->client->postAsync($this->webhookUrl, [
                    'json' => [
                        'event' => $event,
                        'data' => $data,
                        'timestamp' => time(),
                    ],
                ]);
            } catch (RequestException $e) {
                // Log webhook failure but don't interrupt query
                error_log('Webhook failed: ' . $e->getMessage());
            }
        }
    }
}
