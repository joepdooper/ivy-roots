<?php

namespace Ivy\View;

use Carbon\Carbon;
use Ivy\Core\Language;
use Ivy\Manager\HookManager;
use Ivy\Manager\SecurityManager;
use Ivy\Manager\SessionManager;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Info;
use Ivy\Model\Profile;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Core\Path;
use Ivy\Config\Environment;
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
        self::$latte->setAutoRefresh(Environment::isDev());

        self::$latte->addFunction('icon', fn($icon) => file_get_contents(Path::get('MEDIA_PATH') . "icons/" . $icon));
        self::$latte->addFunction('text', fn($key, $vars = null) => Language::translate($key, $vars) ?? $key);
        self::$latte->addFunction('path', fn($key) => Path::get($key));
        self::$latte->addFunction('media', fn($key) => Path::get('PUBLIC_URL') . 'media/' . $key);
        self::$latte->addFunction('route', fn($key) => preg_match('#^' . str_replace('*', '.*', $key) . '$#', Path::get('CURRENT_ROUTE')) === 1);
        self::$latte->addFunction('file', fn($key) => TemplateManager::file($key));
        self::$latte->addFunction('render', fn($key, $vars = []) => View::render($key, $vars));
        self::$latte->addFunction('info', fn($key) => Info::stashGet($key)->value ?? '');
        self::$latte->addFunction('setting', fn($key) => Setting::stashGet($key)->value ?? '');
        self::$latte->addFunction('enabled', fn($key) => Setting::stashGet($key)->bool ?? false);
        self::$latte->addFunction('isPluginActive', fn($key) => in_array($key, SessionManager::get('plugin_actives')));
        self::$latte->addFunction('csrf', fn() => new \Latte\Runtime\Html('<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">'));
        self::$latte->addFunction('auth', fn() => User::getAuth());
        self::$latte->addFunction('profile', fn() => Profile::getUserProfile());
        self::$latte->addFunction('canEditAsEditor', fn() => User::canEditAsEditor());
        self::$latte->addFunction('canEditAsAdmin', fn() => User::canEditAsAdmin());
        self::$latte->addFunction('canEditAsSuperAdmin', fn() => User::canEditAsSuperAdmin());
        self::$latte->addFunction('hook', fn($key) => HookManager::do($key));
        self::$latte->addFunction('csp', fn() => SecurityManager::getNonce());
        self::$latte->addFunction('datetime', fn() => Carbon::class);

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
