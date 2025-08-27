<?php
namespace Ivy\Manager;

use Ivy\Config\Environment;

class SecurityManager
{
    private static string $nonce;

    public static function setSecurityHeaders(): void
    {
        if (!Environment::isDev()) {
            self::$nonce = base64_encode(random_bytes(16));

            header("Content-Security-Policy: "
                . "upgrade-insecure-requests; "
                . "object-src 'none'; "
                . "block-all-mixed-content; "
                . "frame-ancestors 'self'; "
                . "script-src 'self' 'nonce-" . self::$nonce . "'; "
            );
        }
    }

    public static function getNonce(): string
    {
        return self::$nonce ?? '';
    }

}