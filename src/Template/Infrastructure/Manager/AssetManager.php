<?php

namespace Ivy\Template\Infrastructure\Manager;

use Ivy\Setting\Domain\Entity\Setting;
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
     *
     * @return array<string>
     */
    public static function getCSS(): array
    {
        if (Environment::isProd() && Setting::stashGet('minify_css')->bool) {
            self::$css = [Path::get('PUBLIC_URL').'css/minified.css'];
        }

        return self::$css;
    }

    /**
     * Get compiled JS assets.
     *
     * @return array<string>
     */
    public static function getJS(): array
    {
        if (Environment::isProd() && Setting::stashGet('minify_js')->bool) {
            self::$js = [Path::get('PUBLIC_URL').'js/minified.js'];
        }

        return self::$js;
    }

    /**
     * Get compiled JS module assets.
     *
     * @return array<string>
     */
    public static function getModules(): array
    {
        return self::$module;
    }

    /**
     * Handle adding and syncing asset in dev mode.
     *
     * @param  array<string>  $collection
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
