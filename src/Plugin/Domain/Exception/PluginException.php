<?php

namespace Ivy\Plugin\Domain\Exception;

use RuntimeException;
use Throwable;

class PluginException extends RuntimeException
{
    public function __construct(
        string $message,
        public ?string $plugin = null,
        ?Throwable $previous = null
    ) {
        if ($plugin !== null) {
            $message = "{$plugin} {$message}";
        }

        parent::__construct($message, 0, $previous);
    }
}