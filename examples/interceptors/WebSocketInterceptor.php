<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Examples\Interceptors;

/**
 * WebSocket interceptor for real-time event streaming
 * Note: This example assumes you have a WebSocket client implementation
 */
class WebSocketInterceptor
{
    private $client;

    private string $sessionId;

    public function __construct($wsClient)
    {
        $this->client = $wsClient;
        $this->sessionId = uniqid('session_');
    }

    public function __invoke(string $event, mixed $data): void
    {
        $payload = [
            'sessionId' => $this->sessionId,
            'event' => $event,
            'timestamp' => microtime(true),
        ];

        switch ($event) {
            case 'onQueryStart':
                $payload['prompt'] = $data['prompt'];
                break;

            case 'onMessageParsed':
                $payload['messageType'] = $data['type'];
                $payload['message'] = $data['message'];
                break;

            case 'onRawMessage':
                $payload['raw'] = $data;
                break;

            case 'onError':
                $payload['error'] = $data;
                break;
        }

        try {
            $this->client->send(json_encode($payload));
        } catch (\Exception $e) {
            error_log('WebSocket send failed: ' . $e->getMessage());
        }
    }
}
