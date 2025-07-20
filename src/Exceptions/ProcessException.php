<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Exceptions;

class ProcessException extends ClaudeSDKException
{
    public function __construct(
        string $message,
        public readonly int $exitCode,
        public readonly string $stderr,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
