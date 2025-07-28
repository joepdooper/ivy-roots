<?php

namespace Ivy\View;

use Ivy\Language;
use Ivy\Manager\HookManager;
use Ivy\Manager\SessionManager;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Info;
use Ivy\Model\Profile;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Path;
use Latte\Engine;

class View
{
    protected static ?Engine $latte = null;
    protected static string $name = '';
    protected static array $params = [];
    protected static ?string $block = null;

    public static function name(string $name, array $params = [], ?string $block = null): string
    {
        self::$name = TemplateManager::file($name);
        self::$params = $params;
        self::$block = $block;
        return self::$name;
    }

    public static function set(string $name, array $params = [], ?string $block = null): void
    {
        $params['flashes'] = SessionManager::getFlashBag()->all();
        self::name($name, $params, $block);
    }

    public static function render(string $name, array $params = [], ?string $block = null): void
    {
        self::renderWithEngine(self::name($name), $params, $block);
    }

    public static function body(string $name, array $params = [], ?string $block = null): void
    {
        if (empty(self::$name)) {
            $params['flashes'] = $params['flashes'] ?? [];
            self::name($name, $params, $block);
        }
        self::renderWithEngine(self::$name, self::$params, self::$block);
    }

    public static function head(string $name, array $params = [], ?string $block = null): void
    {
        $file = TemplateManager::file($name);
        if ($file !== null) {
            self::renderWithEngine($file, $params, $block);
        }
    }

    protected static function renderWithEngine(string $name, array $params = [], ?string $block = null): void
    {
        if (self::$latte === null) {
            self::initializeLatte();
        }

        self::$latte->render($name, $params, $block);
    }

    protected static function initializeLatte(): void
    {
        self::$latte = new Engine();
        self::$latte->setTempDirectory(Path::get('PROJECT_PATH') . 'cache/templates');
        self::$latte->setAutoRefresh($_ENV['APP_ENV'] ?? 'production' === 'development');

        self::$latte->addFunction('icon', fn($icon) => file_get_contents(Path::get('PROJECT_PATH') . "/media/icon/" . $icon));
        self::$latte->addFunction('text', fn($key, $vars = null) => Language::translate($key, $vars) ?? $key);
        self::$latte->addFunction('path', fn($key) => Path::get($key));
        self::$latte->addFunction('file', fn($key) => TemplateManager::file($key));
        self::$latte->addFunction('render', fn($key, $vars = []) => View::render($key, $vars));
        self::$latte->addFunction('setting', fn($key) => Info::getStash()[$key]->value ?? '');
        self::$latte->addFunction('enabled', fn($key) => Info::getStash()[$key]->bool ?? false);
        self::$latte->addFunction('isPluginActive', fn($key) => in_array($key, SessionManager::get('plugin_actives')));
        self::$latte->addFunction('csrf', fn() => new \Latte\Runtime\Html('<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">'));
        self::$latte->addFunction('auth', fn() => User::getAuth());
        self::$latte->addFunction('canEditAsEditor', fn() => User::canEditAsEditor());
        self::$latte->addFunction('canEditAsAdmin', fn() => User::canEditAsAdmin());
        self::$latte->addFunction('canEditAsSuperAdmin', fn() => User::canEditAsSuperAdmin());
        self::$latte->addFunction('profile', fn() => Profile::getUserProfile());
        self::$latte->addFunction('hook', fn($key) => HookManager::do($key));

        self::$latte->addExtension(new \Ivy\Tag\ButtonTag());
        self::$latte->addProvider('customButtonRender', function ($args) {
            if ($file = TemplateManager::file('buttons/button.' . $args['type'] . '.latte')) {
                self::$latte->render($file, $args);
            } else {
                throw new \Exception("Button template for type '{$args['type']}' not found.");
            }
        });
    }

    public static function generateCsrfToken(): string
    {
        $session = SessionManager::getSession();
        if (!$session->has('csrf_token')) {
            $session->set('csrf_token', bin2hex(random_bytes(32)));
        }
        return $session->get('csrf_token');
    }
}
