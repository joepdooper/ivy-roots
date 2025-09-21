<?php

namespace Ivy\Manager;

use Ivy\Config\Environment;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Core\Path;

class AssetManager
{
    protected static array $css = array();
    protected static array $js = array();
    protected static array $esm = array();

    public static function addCSS(string $name): void
    {
        self::handleAsset($name, self::$css);
    }

    public static function addJS(string $name): void
    {
        self::handleAsset($name, self::$js);
    }

    public static function addViteEntry(string $name): void
    {
        $name = ltrim($name, '/');
        if (!in_array($name, self::$esm, true)) self::$esm[] = $name;
    }

    /**
     * @return array
     */
    public static function getCss(): array
    {
        if (Setting::stashGet('minify_css')->bool) {
            if (Environment::isDev() && !file_exists('/css/minified.css')) {
                $minify = new \MatthiasMullie\Minify\CSS();
                foreach (self::$css as $cssfile) {
                    $minify->add(Path::get('PUBLIC_PATH') . ltrim($cssfile, '/'));
                }
                $minify->minify(Path::get('PUBLIC_PATH') . 'css/minified.css');
            }
            self::$css = ['/css/minified.css'];
        } else {
            if (Environment::isDev() && file_exists(Path::get('PUBLIC_PATH') . 'css/minified.css')) {
                unlink(Path::get('PUBLIC_PATH') . 'css/minified.css');
            }
        }

        return self::$css;
    }

    /**
     * @return array
     */
    public static function getJs(): array
    {
        if (Setting::stashGet('minify_js')->bool) {
            if (Environment::isDev() && !file_exists('/js/minified.js')) {
                $minify = new \MatthiasMullie\Minify\JS();
                foreach (self::$js as $jsfile) {
                    $minify->add(Path::get('PUBLIC_PATH') . ltrim($jsfile, '/'));
                }
                $minify->minify(Path::get('PUBLIC_PATH') . 'js/minified.js');
            }
            self::$js = ['/js/minified.js'];
        } else {
            if (Environment::isDev() && file_exists(Path::get('PUBLIC_PATH') . 'js/minified.js')) {
                unlink(Path::get('PUBLIC_PATH') . 'js/minified.js');
            }
        }

        return self::$js;
    }

    /**
     * @return array
     */
    public static function getViteEntry(): array
    {
        if (Environment::isDev()) {
            self::generatePluginsEntry();
            $viteDevUrl = 'http://' . $_ENV['VITE_HOST'] . ':' . $_ENV['VITE_PORT'];
            return [$viteDevUrl . '/js/vite.modules.js?t=' . time()];
        }

        $manifestPath = Path::get('PUBLIC_PATH') . 'dist/manifest.json';
        if (!file_exists($manifestPath)) return [];

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $entryKey = 'js/modules.js';
        if (!isset($manifest[$entryKey])) return [];

        return ['/dist/' . $manifest[$entryKey]['file']];
    }

    protected static function generatePluginsEntry(): void
    {
        if (empty(self::$esm)) return;

        $srcDir = Path::get('PUBLIC_PATH') . DIRECTORY_SEPARATOR . 'js';
        $entryFile = $srcDir . DIRECTORY_SEPARATOR . 'vite.modules.js';

        $lines = [];
        foreach (self::$esm as $esm) {
            $lines[] = "import '../" . str_replace('\\', '/', $esm) . "';";
        }

        if (!is_dir($srcDir)) mkdir($srcDir, 0755, true);

        $content = implode("\n", $lines) . "\n";

        if (!file_exists($entryFile) || file_get_contents($entryFile) !== $content) {
            file_put_contents($entryFile, $content);
        }
    }

    private static function handleAsset(string $name, array &$collection): void
    {
        if (Environment::isDev()) {
            $publicFile = Path::get('PUBLIC_PATH') . $name;
            $originalFile = TemplateManager::file($name);

            if (file_exists($originalFile)) {
                $publicDir = dirname($publicFile);
                if (!is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }

                if (file_exists($publicFile)) {
                    unlink($publicFile);
                }

                copy($originalFile, $publicFile);
            }
        }

        $collection[] = '/' . $name;
    }
}
