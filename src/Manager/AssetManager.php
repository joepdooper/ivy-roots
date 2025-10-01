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
    protected static array $vite = array();

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
        if (!in_array($name, self::$vite, true)) self::$vite[] = $name;
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
//            if(self::isViteRunning()){
                self::generatePluginsEntry();
                $viteDevUrl = Path::get('PROTOCOL') . '://' . $_ENV['VITE_FRONTEND_HOST'] . ':' . $_ENV['VITE_PORT'];
                return [
                    $viteDevUrl . '/@vite/client',
                    $viteDevUrl . '/vite.modules.js?t=' . time()
                ];
//            } else {
//                foreach (self::$vite as $module) {
//                    self::handleAsset($module, self::$esm);
//                }
//                return self::$esm;
//            }
        }

        return [
            Path::get('PUBLIC_URL') . 'js/vite.bundle.js'
        ];
    }

    public static function isViteRunning(): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init(Path::get('PROTOCOL') . '://' . $_ENV['VITE_BACKEND_HOST'] . ':' . $_ENV['VITE_PORT'] . '/' . '@vite/client');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0.2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0.5);

        curl_exec($ch);
        $error = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($error === 0 && $httpCode >= 200 && $httpCode < 500);
    }

    protected static function generatePluginsEntry(): void
    {
        if (empty(self::$vite)) return;

        $entryFile = Path::get('PROJECT_PATH') . 'vite.modules.js';

        $lines = [];
        foreach (self::$vite as $module) {
            $module = str_replace('\\', '/', $module);
//            if (preg_match('/_(admin|dev)\.js$/', $module)) {
//                continue;
//            }
            $lines[] = "import '/" . $module . "';";
            if (file_exists(Path::get('PUBLIC_PATH') . $module)) {
                unlink(Path::get('PUBLIC_PATH') . $module);
            }
        }

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
