<?php

namespace Ivy;

use Hooks;
use Latte\Engine;
use Symfony\Component\HttpFoundation\Session\Session;

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
    protected static ?Session $session = null;

    // -- file

    public static function file(string $filename): ?string
    {
        $paths = [_TEMPLATE_SUB, _TEMPLATE_BASE];
        foreach ($paths as $path) {
            $fullPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        return $filename;
    }

    // -- template rendering

    protected static function latte(string $name, object|array $params = [], ?string $block = null): void
    {
        if (self::$latte === null) {
            self::$latte = new Engine();
            self::$latte->setTempDirectory(Path::get('PUBLIC_PATH') . 'cache/templates');
            self::$latte->setAutoRefresh($_ENV['APP_ENV'] ?? 'production' === 'development');

            self::$latte->addFunction('icon', fn($icon) => file_get_contents(Path::get('PUBLIC_PATH') . "/media/icon/" . $icon));
            self::$latte->addFunction('text', fn($key, $vars = null) => Language::translate($key, $vars) ?? $key);
            self::$latte->addFunction('path', fn($key) => Path::get($key));
            self::$latte->addFunction('isLoggedIn', fn() => User::getAuth()->isLoggedIn());
            self::$latte->addFunction('setting', fn($key) => Setting::getStash()[$key]->value ?? '');
            self::$latte->addFunction('csrf', fn() => new \Latte\Runtime\Html('<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">'));

            self::$latte->addExtension(new \Ivy\Tags\ButtonTag());
            self::$latte->addProvider('customButtonRender', function ($args) {
                if ($file = self::file('buttons/button.' . $args['type'] . '.latte')) {
                    self::$latte->render($file, $args);
                } else {
                    throw new \Exception("Button template for type '{$args['type']}' not found.");
                }
            });
        }

        self::$latte->render($name, $params, $block);
    }


    public static function name(string $name, array $params = [], ?string $block = null): string
    {
        self::$name = self::file($name);
        self::$params = $params;
        self::$block = $block;
        return self::$name;
    }

    public static function view(string $name, array $params = [], ?string $block = null): void
    {
        $params['flashes'] = App::session()->getFlashBag()->all();
        self::name($name, $params, $block);
    }

    public static function render(string $name, array $params = [], ?string $block = null): void
    {
        self::latte(self::name($name), $params, $block);
    }

    public static function body(string $name, array $params = [], ?string $block = null): void
    {
        if (!self::$name) {
            $params['flashes'] = isset($params['flashes']) ? $params['flashes'] : [];
            self::name($name, $params, $block);
        }
        self::latte(self::$name, self::$params);
    }

    public static function head(string $name, array $params = [], ?string $block = null): void
    {
        $file = self::file($name);
        if ($file === null) {
            return;
        }
        self::latte($file, $params, $block);
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
        $session = App::session();
        if (!$session->has('csrf_token')) {
            $session->set('csrf_token', bin2hex(random_bytes(32)));
        }
        return $session->get('csrf_token');
    }

}
