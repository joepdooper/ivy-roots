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
     * Check if Vite dev server is running.
     */
    public static function isViteRunning(): bool
    {
        if (! function_exists('curl_init')) {
            return false;
        }

        $url = Path::get('PROTOCOL').'://'.$_ENV['VITE_BACKEND_HOST'].':'.$_ENV['VITE_PORT'].'/@vite/client';
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 1,
        ]);

        curl_exec($ch);
        $success = (curl_errno($ch) === 0 && curl_getinfo($ch, CURLINFO_HTTP_CODE) < 500);
        curl_close($ch);

        return $success;
    }

    /**
     * Generate plugin-based Vite entry files dynamically.
     */
    protected static function generatePluginsEntry(): void
    {
        if (empty(self::$vite)) {
            return;
        }

        $groups = ['base' => [], 'admin' => [], 'editor' => []];

        foreach (self::$vite as $module) {
            $path = str_replace('\\', '/', $module);
            match (true) {
                str_ends_with($path, '_admin.js') => $groups['admin'][] = $path,
                str_ends_with($path, '_editor.js') => $groups['editor'][] = $path,
                default => $groups['base'][] = $path,
            };
        }

        foreach ($groups as $name => $modules) {
            if (! $modules) {
                continue;
            }

            $entryFile = Path::get('PROJECT_PATH')."vite.{$name}.js";
            $content = implode("\n", array_map(fn ($m) => "import '/$m';", $modules))."\n";

            if (! file_exists($entryFile) || file_get_contents($entryFile) !== $content) {
                file_put_contents($entryFile, $content);
            }
        }
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
