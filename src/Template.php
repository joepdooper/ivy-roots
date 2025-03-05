<?php

namespace Ivy;

use Hooks;
use Latte\Engine;

class Template extends Model
{
    protected string $table = 'template';
    protected string $path = 'admin/template';
    protected array $columns = [
        'type',
        'value',
    ];

    protected string $type;
    protected string $value;

    protected static array $css = array();
    protected static array $js = array();
    protected static array $esm = array();

    private static bool|string $file;

    public static string $identifier;
    public static string $route;
    public static string $url = "";

    protected static ?Engine $latte = null;
    protected static bool|string $name = '';
    protected static array $params = [];
    protected static ?string $block;
    protected static ?Hooks $hooks = null;

    // -- file

    public static function file(string $file): bool|string
    {
        if (file_exists(_TEMPLATE_SUB . $file)) {
            self::$file = _TEMPLATE_SUB . $file;
        } elseif (file_exists(_TEMPLATE_BASE . $file)) {
            self::$file = _TEMPLATE_BASE . $file;
        } elseif (file_exists($file)) {
            self::$file = $file;
        } else {
            self::$file = false;
        }
        return self::$file;
    }

    // -- template rendering

    protected static function latte(string $name, object|array $params = [], ?string $block = null): void
    {
        if (self::$latte === null) {
            self::$latte = new Engine();
            self::$latte->addFunction('icon', function ($icon) {
                return file_get_contents(Path::get('PUBLIC_PATH') . "/media/icon/" . $icon);
            });
            self::$latte->addFunction('text', function ($language_key) {
                return Language::translate($language_key) ?? $language_key;
            });
            self::$latte->addFunction('path', function ($path_key) {
                return Path::get($path_key);
            });
            self::$latte->addFunction('isLoggedIn', function () {
                return User::getAuth()->isLoggedIn();
            });
            self::$latte->addFunction('setting', function ($settings_key) {
                return isset(Setting::getStash()[$settings_key]) ? Setting::getStash()[$settings_key]?->value : '';
            });
            self::$latte->addFunction('csrf', function () {
                return new \Latte\Runtime\Html('<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">');
            });
            self::$latte->setTempDirectory(Path::get('PUBLIC_PATH') . 'cache/templates');
        }
        self::$latte->render($name, $params, $block);
    }

    public static function name(string $name, object|array $params = [], ?string $block = null): bool|string
    {
        self::$name = self::file($name);
        self::$params = $params;
        self::$block = $block;
        return self::$name;
    }

    public static function view(string $name, object|array $params = [], ?string $block = null): void
    {
        self::name($name, $params, $block);
    }

    public static function render(string $name, object|array $params = [], ?string $block = null): void
    {
        self::latte(self::name($name), $params, $block);
    }

    public static function body(string $name, object|array $params = [], ?string $block = null): void
    {
        if (!self::$name) {
            self::name($name, $params, $block);
        }
        self::latte(self::$name, self::$params);
    }

    public static function head(string $name, object|array $params = [], ?string $block = null): void
    {
        if (file_exists(_TEMPLATE_SUB . $name)) {
            $name = _TEMPLATE_SUB . $name;
        } elseif (file_exists(_TEMPLATE_BASE . $name)) {
            $name = _TEMPLATE_BASE . $name;
        }
        self::latte($name, $params, $block);
    }

    // -- assets

    public static function addCSS($name): void
    {
        self::$css[] = self::file($name);
    }

    public static function addJS($name): void
    {
        self::$js[] = self::file($name);
    }

    public static function addESM($name): void
    {
        self::$esm[] = self::file($name);
    }

    // -- hooks

    public static function hooks(): Hooks
    {
        if (self::$hooks === null) {
            self::$hooks = new Hooks();
        }
        return self::$hooks;
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

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

}
