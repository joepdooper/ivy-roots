<?php

namespace Ivy\Infrastructure\Manager;

use Ivy\Shared\Config\Environment;
use Ivy\Shared\Core\Path;

class AssetManager
{
    /** @var array<string> */
    protected static array $css = [];

    /** @var array<string> */
    protected static array $js = [];

    /** @var array<string> */
    protected static array $module = [];

    /** @var array<string> */
    protected static array $vite = [];

    /**
     * Register a CSS asset.
     */
    public static function addCSS(string $path): void
    {
        self::addAsset($path, self::$css);
    }

    /**
     * Register a JS asset.
     */
    public static function addJS(string $path): void
    {
        self::addAsset($path, self::$js);
    }

    /**
     * Register a JS module asset.
     */
    public static function addModule(string $path): void
    {
        self::addAsset($path, self::$module);
    }

    /**
     * Get compiled CSS assets.
     * @return array<string>
     */
    public static function getCSS(): array
    {
        return self::$css;
    }

    /**
     * Get compiled JS assets.
     * @return array<string>
     */
    public static function getJS(): array
    {
        return self::$js;
    }

    /**
     * Get compiled JS module assets.
     * @return array<string>
     */
    public static function getModules(): array
    {
        return self::$module;
    }

    /**
     * Handle adding and syncing asset in dev mode.
     * @param array<string> $collection
     */
    private static function addAsset(string $path, array &$collection): void
    {
        $host = '/';
        $path = ltrim($path, '/');

        if (Environment::isDev()) {
            $host = Path::get('PROTOCOL').'://'.$_ENV['VITE_FRONTEND_HOST'].':'.$_ENV['VITE_PORT'];
            $path = TemplateManager::file($path);
        }

        $collection[] = $host.$path;
    }
}
