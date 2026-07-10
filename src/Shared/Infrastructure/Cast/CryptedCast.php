<?php

namespace Ivy\Shared\Infrastructure\Cast;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Ivy\Shared\Infrastructure\Service\CryptedService;
use Random\RandomException;
use RuntimeException;
use Throwable;

/**
 * @phpstan-implements CastsAttributes<string|null, string|null>
 */
class CryptedCast implements CastsAttributes
{
    private ?CryptedService $crypto = null;

    private function crypto(): CryptedService
    {
        return $this->crypto ??= Container::getInstance()->make(CryptedService::class);
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return $this->crypto()->decrypt($value);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to decrypt [$key]: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws RandomException|BindingResolutionException
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $this->crypto()->encrypt($value);
    }
}
