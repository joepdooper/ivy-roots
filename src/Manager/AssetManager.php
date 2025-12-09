<?php

namespace Ivy\Manager;

use Ivy\Config\Environment;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Core\Path;

class AssetManager
{
    protected static array $css  = [];
    protected static array $js   = [];
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
     * Register a Vite entry module.
     */
    public static function addViteEntry(string $entry): void
    {
        $entry = ltrim($entry, '/');
        if (!in_array($entry, self::$vite, true)) {
            self::$vite[] = $entry;
        }
    }

    /**
     * Get compiled CSS assets.
     */
    public static function getCss(): array
    {
        return self::processAssets(self::$css, 'css', Setting::stashGet('minify_css')->bool);
    }

    /**
     * Get compiled JS assets.
     */
    public static function getJs(): array
    {
        return self::processAssets(self::$js, 'js', Setting::stashGet('minify_js')->bool);
    }

    /**
     * Get active Vite entry files depending on environment.
     */
    public static function getViteEntry(): array
    {
        $isDev = Environment::isDev();

        if ($isDev) {
            self::generatePluginsEntry();
            $viteHost = Path::get('PROTOCOL') . '://' . $_ENV['VITE_FRONTEND_HOST'] . ':' . $_ENV['VITE_PORT'] . '/';

            $files = [
                $viteHost . '@vite/client',
                $viteHost . 'vite.base.js'
            ];

            if (User::getAuth()->isLoggedIn()) {
                if (User::canEditAsEditor()) $files[] = $viteHost . 'vite.editor.js';
                if (User::canEditAsAdmin())  $files[] = $viteHost . 'vite.admin.js';
            }

            return $files;
        }

        $version = Info::stashGet('updated')->value ?? time();
        $public  = Path::get('PUBLIC_URL') . 'js/';

        $files = [$public . 'vite.base.js?d=' . $version];

        if (User::getAuth()->isLoggedIn()) {
            if (User::canEditAsEditor()) $files[] = $public . 'vite.editor.js?d=' . $version;
            if (User::canEditAsAdmin())  $files[] = $public . 'vite.admin.js?d=' . $version;
        }

        return $files;
    }

    /**
     * Check if Vite dev server is running.
     */
    public static function isViteRunning(): bool
    {
        if (!function_exists('curl_init')) return false;

        $url = Path::get('PROTOCOL') . '://' . $_ENV['VITE_BACKEND_HOST'] . ':' . $_ENV['VITE_PORT'] . '/@vite/client';
        $ch  = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_CONNECTTIMEOUT => 0.2,
            CURLOPT_TIMEOUT        => 0.5,
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
        if (empty(self::$vite)) return;

        $groups = ['base' => [], 'admin' => [], 'editor' => []];

        foreach (self::$vite as $module) {
            $path = str_replace('\\', '/', $module);
            match (true) {
                str_ends_with($path, '_admin.js')  => $groups['admin'][]  = $path,
                str_ends_with($path, '_editor.js') => $groups['editor'][] = $path,
                default                            => $groups['base'][]   = $path,
            };
        }

        foreach ($groups as $name => $modules) {
            if (!$modules) continue;

            $entryFile = Path::get('PROJECT_PATH') . "vite.{$name}.js";
            $content   = implode("\n", array_map(fn($m) => "import '/$m';", $modules)) . "\n";

            if (!file_exists($entryFile) || file_get_contents($entryFile) !== $content) {
                file_put_contents($entryFile, $content);
            }
        }
    }

    /**
     * Handle adding and syncing asset in dev mode.
     */
    private static function addAsset(string $path, array &$collection): void
    {
        $path = ltrim($path, '/');

        if (Environment::isDev()) {
            $src = TemplateManager::file($path);
            $dest = Path::get('PUBLIC_PATH') . $path;

            if (file_exists($src)) {
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                if (file_exists($dest)) unlink($dest);
                copy($src, $dest);
            }
        }

        $collection[] = '/' . $path;
    }

    /**
     * Handle minification and dev cleanup.
     */
    private static function processAssets(array $assets, string $type, bool $shouldMinify): array
    {
        $minifiedPath = Path::get('PUBLIC_PATH') . "{$type}/minified.{$type}";
        $minifiedUrl  = "/{$type}/minified.{$type}";

        if ($shouldMinify) {
            if (Environment::isDev() && !file_exists($minifiedPath)) {
                $minifierClass = "\\MatthiasMullie\\Minify\\" . strtoupper($type);
                $minify = new $minifierClass();

                foreach ($assets as $file) {
                    $minify->add(Path::get('PUBLIC_PATH') . ltrim($file, '/'));
                }

                $minify->minify($minifiedPath);
            }
            return [$minifiedUrl];
        }

        // Cleanup old minified file in dev mode
        if (Environment::isDev() && file_exists($minifiedPath)) {
            unlink($minifiedPath);
        }

        return $assets;
    }
}