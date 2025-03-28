<?php

namespace Ivy\Manager;

use Ivy\Model\Template;

class AssetManager
{
    protected static array $css = array();
    protected static array $js = array();
    protected static array $esm = array();

    public static function addCSS($name): void
    {
        self::$css[] = TemplateManager::file($name);
    }

    public static function addJS($name): void
    {
        self::$js[] = TemplateManager::file($name);
    }

    public static function addESM($name): void
    {
        self::$esm[] = TemplateManager::file($name);
    }

    /**
     * @return array
     */
    public static function getCss(): array
    {
        return self::$css;
    }

    /**
     * @return array
     */
    public static function getJs(): array
    {
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

