<?php

namespace Ivy\Manager;

use Ivy\Config\Environment;
use Ivy\Core\Path;
use Ivy\Model\Setting;

class AssetManager
{
    protected static array $css = [];

    protected static array $js = [];

    protected static array $module = [];

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
     */
    public static function getCSS(): array
    {
        return self::processAssets(self::$css, 'css', Setting::stashGet('minify_css')->bool);
    }

    /**
     * Get compiled JS assets.
     */
    public static function getJS(): array
    {
        return self::processAssets(self::$js, 'js', Setting::stashGet('minify_js')->bool);
    }

    /**
     * Get compiled JS module assets.
     */
    public static function getModules(): array
    {
        return self::processAssets(self::$js, 'js', Setting::stashGet('minify_js')->bool);
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
            CURLOPT_CONNECTTIMEOUT => 0.2,
            CURLOPT_TIMEOUT => 0.5,
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

    /**
     * Handle minification and dev cleanup.
     */
    private static function processAssets(array $assets, string $type, bool $shouldMinify): array
    {
        $minifiedUrl = "/{$type}/minified.{$type}";
        $minifiedPath = Path::get('PUBLIC_PATH').$minifiedUrl;

        if ($shouldMinify) {
            if (Environment::isDev() && ! file_exists($minifiedPath)) {
                $minifierClass = '\\MatthiasMullie\\Minify\\'.strtoupper($type);
                $minify = new $minifierClass;

                foreach ($assets as $file) {
                    $minify->add(Path::get('PUBLIC_PATH').ltrim($file, '/'));
                }

                $minify->minify($minifiedPath);
            }

            return [$minifiedUrl];
        }

        if (Environment::isDev() && file_exists($minifiedPath)) {
            unlink($minifiedPath);
        }

        return $assets;
    }
}

//            $src = TemplateManager::file($path);
//            $dest = Path::get('PUBLIC_PATH') . $path;
//
//            if (file_exists($src)) {
//                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
//                if (file_exists($dest)) unlink($dest);
//                copy($src, $dest);
//            }
