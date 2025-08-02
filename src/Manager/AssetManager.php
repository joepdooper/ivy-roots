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

    public static function addCSS($name): void
    {
        if(Environment::isDev()){
            unlink(Path::get('PUBLIC_PATH').$name);
            symlink(TemplateManager::file($name), Path::get('PUBLIC_PATH') . $name);
        }
        self::$css[] = '/' . $name;
    }

    public static function addJS($name): void
    {
        if(Environment::isDev()){
            unlink(Path::get('PUBLIC_PATH').$name);
            symlink(TemplateManager::file($name), Path::get('PUBLIC_PATH').$name);
        }
        self::$js[] = '/' . $name;
    }

    public static function addESM($name): void
    {
        if(Environment::isDev()){
            unlink(Path::get('PUBLIC_PATH').$name);
            symlink(TemplateManager::file($name), Path::get('PUBLIC_PATH').$name);
        }
        self::$esm[] = '/' . $name;
    }

    /**
     * @return array
     */
    public static function getCss(): array
    {
        if (Setting::getStash()['minify_css']->bool) {
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
        if (Setting::getStash()['minify_js']->bool) {
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
    public static function getEsm(): array
    {
        return self::$esm;
    }
}

