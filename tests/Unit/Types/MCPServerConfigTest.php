<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\HTTPServerConfig;
use HelgeSverre\ClaudeCode\Types\SSEServerConfig;
use HelgeSverre\ClaudeCode\Types\StdioServerConfig;

describe('StdioServerConfig', function () {
    it('creates stdio config with command only', function () {
        $config = new StdioServerConfig('node');

        expect($config->type)->toBe('stdio');
        expect($config->command)->toBe('node');
        expect($config->args)->toBeEmpty();
        expect($config->env)->toBeEmpty();
        expect($config->toArray())->toBe(['command' => 'node']);
    });

    it('creates stdio config with all properties', function () {
        $args = ['server.js', '--port', '3000'];
        $env = ['NODE_ENV' => 'production'];

        $config = new StdioServerConfig('node', $args, $env);

        expect($config->toArray())->toBe([
            'command' => 'node',
            'args' => $args,
            'env' => $env,
        ]);
    });
});

describe('SSEServerConfig', function () {
    it('creates SSE config with URL only', function () {
        $config = new SSEServerConfig('https://api.example.com/sse');

        expect($config->type)->toBe('sse');
        expect($config->url)->toBe('https://api.example.com/sse');
        expect($config->headers)->toBeEmpty();
        expect($config->toArray())->toBe(['url' => 'https://api.example.com/sse']);
    });

    it('creates SSE config with headers', function () {
        $headers = ['Authorization' => 'Bearer token'];
        $config = new SSEServerConfig('https://api.example.com/sse', $headers);

        expect($config->toArray())->toBe([
            'url' => 'https://api.example.com/sse',
            'headers' => $headers,
        ]);
    });
});

describe('HTTPServerConfig', function () {
    it('creates HTTP config with URL only', function () {
        $config = new HTTPServerConfig('https://api.example.com');

        expect($config->type)->toBe('http');
        expect($config->url)->toBe('https://api.example.com');
        expect($config->headers)->toBeEmpty();
        expect($config->toArray())->toBe(['url' => 'https://api.example.com']);
    });

    it('creates HTTP config with headers', function () {
        $headers = ['Authorization' => 'Bearer token', 'Content-Type' => 'application/json'];
        $config = new HTTPServerConfig('https://api.example.com', $headers);

        expect($config->toArray())->toBe([
            'url' => 'https://api.example.com',
            'headers' => $headers,
        ]);
    });
});
