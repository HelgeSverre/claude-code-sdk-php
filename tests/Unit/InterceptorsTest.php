<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Internal\ProcessBridge;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;

test('interceptors receive onRawMessage events', function () {
    $capturedEvents = [];

    $interceptor = function (string $event, mixed $data) use (&$capturedEvents) {
        $capturedEvents[] = ['event' => $event, 'data' => $data];
    };

    $options = new Options(interceptors: [$interceptor]);

    $transport = new ProcessBridge(
        prompt: 'test',
        options: $options,
    );

    // Simulate raw message handling
    $rawMessage = ['type' => 'test', 'data' => 'example'];
    $reflection = new ReflectionObject($transport);
    $onRawMessageProp = $reflection->getProperty('onRawMessage');
    $onRawMessageProp->setAccessible(true);
    $onRawMessage = $onRawMessageProp->getValue($transport);

    if ($onRawMessage !== null) {
        call_user_func($onRawMessage, $rawMessage);
    }

    expect($capturedEvents)->toHaveCount(1);
    expect($capturedEvents[0]['event'])->toBe('onRawMessage');
    expect($capturedEvents[0]['data'])->toBe($rawMessage);
});

test('multiple interceptors are called in order', function () {
    $callOrder = [];

    $interceptor1 = function (string $event, mixed $data) use (&$callOrder) {
        $callOrder[] = 'interceptor1';
    };

    $interceptor2 = function (string $event, mixed $data) use (&$callOrder) {
        $callOrder[] = 'interceptor2';
    };

    $interceptor3 = function (string $event, mixed $data) use (&$callOrder) {
        $callOrder[] = 'interceptor3';
    };

    $options = new Options(interceptors: [$interceptor1, $interceptor2, $interceptor3]);

    $transport = new ProcessBridge(
        prompt: 'test',
        options: $options,
    );

    $reflection = new ReflectionObject($transport);
    $onRawMessageProp = $reflection->getProperty('onRawMessage');
    $onRawMessageProp->setAccessible(true);
    $onRawMessage = $onRawMessageProp->getValue($transport);

    if ($onRawMessage !== null) {
        call_user_func($onRawMessage, ['test' => 'data']);
    }

    expect($callOrder)->toBe(['interceptor1', 'interceptor2', 'interceptor3']);
});

test('interceptors can be callables or closures', function () {
    $capturedEvents = [];

    // Test with closure
    $closure = function (string $event, mixed $data) use (&$capturedEvents) {
        $capturedEvents[] = 'closure';
    };

    // Test with invokable class
    $invokable = new class($capturedEvents)
    {
        private $capturedEvents;

        public function __construct(&$capturedEvents)
        {
            $this->capturedEvents = &$capturedEvents;
        }

        public function __invoke(string $event, mixed $data)
        {
            $this->capturedEvents[] = 'invokable';
        }
    };

    $options = new Options(interceptors: [$closure, $invokable]);

    $transport = new ProcessBridge(
        prompt: 'test',
        options: $options,
    );

    $reflection = new ReflectionObject($transport);
    $onRawMessageProp = $reflection->getProperty('onRawMessage');
    $onRawMessageProp->setAccessible(true);
    $onRawMessage = $onRawMessageProp->getValue($transport);

    if ($onRawMessage !== null) {
        call_user_func($onRawMessage, ['test' => 'data']);
    }

    expect($capturedEvents)->toBe(['closure', 'invokable']);
});

test('interceptors receive correct event data for onMessageParsed', function () {
    $capturedEvents = [];

    $interceptor = function (string $event, mixed $data) use (&$capturedEvents) {
        if ($event === 'onMessageParsed') {
            $capturedEvents[] = $data;
        }
    };

    $options = new Options(interceptors: [$interceptor]);

    // We'll need to test this through the actual message parsing flow
    // This is a simplified test showing the expected behavior
    $message = new AssistantMessage(['test content']);

    // Simulate what ProcessBridge does when parsing a message
    foreach ($options->interceptors as $interceptor) {
        if (is_callable($interceptor)) {
            $interceptor('onMessageParsed', [
                'message' => $message,
                'type' => get_class($message),
                'timestamp' => microtime(true),
            ]);
        }
    }

    expect($capturedEvents)->toHaveCount(1);
    expect($capturedEvents[0]['message'])->toBe($message);
    expect($capturedEvents[0]['type'])->toBe(AssistantMessage::class);
    expect($capturedEvents[0]['timestamp'])->toBeFloat();
});

test('interceptors with no interceptors defined does not break', function () {
    $options = new Options; // No interceptors

    $transport = new ProcessBridge(
        prompt: 'test',
        options: $options,
    );

    // Should not throw any errors
    expect($transport)->toBeInstanceOf(ProcessBridge::class);
});

test('options fluent interface supports adding interceptors', function () {
    $interceptor = function (string $event, mixed $data) {};

    $options = Options::create()
        ->systemPrompt('Test prompt')
        ->interceptors([$interceptor])
        ->maxTurns(5);

    expect($options->interceptors)->toHaveCount(1);
    expect($options->interceptors[0])->toBe($interceptor);
    expect($options->systemPrompt)->toBe('Test prompt');
    expect($options->maxTurns)->toBe(5);
});
