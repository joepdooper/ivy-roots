<?php

namespace Ivy\Presentation\View\Engine;

use Carbon\Carbon;
use Ivy\Domain\Model\InfoModel;
use Ivy\Domain\Model\SettingModel;
use Ivy\Infrastructure\Manager\CsrfManager;
use Ivy\Shared\Contracts\ViewEngineInterface;
use Latte\Engine;
use Latte\Runtime\Html;
use Ivy\Shared\Config\Environment;
use Ivy\Shared\Core\Language;
use Ivy\Shared\Core\Path;
use Ivy\Infrastructure\Manager\HookManager;
use Ivy\Infrastructure\Manager\SecurityManager;
use Ivy\Infrastructure\Manager\TemplateManager;
use Ivy\Infrastructure\Registry\PluginRegistry;
use Ivy\Application\Service\AuthApplicationService;
use Ivy\Presentation\Tag\ButtonTag;

class LatteEngine implements ViewEngineInterface
{
    private Engine $latte;
    private AuthApplicationService $auth;


    public function __construct()
    {
        $this->latte = new Engine();
    }

    public function setAuth(AuthApplicationService $auth): void
    {
        $this->auth = $auth;
    }

    public function boot(): void
    {
        $this->latte->setTempDirectory(Path::get('PROJECT_PATH') . 'cache/templates');
        $this->latte->setAutoRefresh(Environment::isDev());

        $this->registerFunctions();
        $this->registerExtensions();
        $this->registerProviders();
    }

    public function render(string $template, array $params = [], ?string $block = null): void
    {
        $this->latte->render($template, $params, $block);
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->latte->addFunction($name, $callback);
    }

    public function addExtension(object $extension): void
    {
        $this->latte->addExtension($extension);
    }

    public function getEngine(): Engine
    {
        return $this->latte;
    }

    private function registerFunctions(): void
    {
        $this->latte->addFunction('icon', fn ($icon) =>
        file_get_contents(Path::get('MEDIA_PATH') . 'icons/' . $icon)
        );

        $this->latte->addFunction('text', fn ($key, $vars = null) =>
            Language::translate($key, $vars) ?? $key
        );

        $this->latte->addFunction('path', fn ($key) => Path::get($key));

        $this->latte->addFunction('media', fn ($key) =>
            Path::get('PUBLIC_URL') . 'media/' . $key
        );

        $this->latte->addFunction('route', fn ($key) =>
            preg_match('#^' . str_replace('*', '.*', $key) . '$#', Path::get('CURRENT_ROUTE')) === 1
        );

        $this->latte->addFunction('file', fn ($key) =>
        TemplateManager::file($key)
        );

        $this->latte->addFunction('render', fn ($key, $vars = []) =>
        \Ivy\Presentation\View\View::render($key, $vars)
        );

        $this->latte->addFunction('info', fn ($key) =>
            InfoModel::stashGet($key)->value ?? ''
        );

        $this->latte->addFunction('setting', fn ($key) =>
            SettingModel::stashGet($key)->value ?? ''
        );

        $this->latte->addFunction('enabled', fn ($key) =>
            SettingModel::stashGet($key)->bool ?? false
        );

        $this->latte->addFunction('isPluginActive', fn (string $key): bool =>
        PluginRegistry::isActive($key)
        );

        $this->latte->addFunction('csrf', fn () =>
        new Html('<input type="hidden" name="csrf_token" value="' . CsrfManager::token() . '">')
        );

        $this->latte->addFunction('doesUserHaveRole', fn ($user, $role) =>
        $this->auth->auth()->admin()->doesUserHaveRole($user, $role)
        );

        $this->latte->addFunction('authRoles', fn () =>
        $this->auth->auth()->getRoles()
        );

        $this->latte->addFunction('authUser', fn () =>
        $this->auth->authUser()
        );

        $this->latte->addFunction('isLoggedIn', fn () =>
        $this->auth->isLoggedIn()
        );

        $this->latte->addFunction('can', fn ($action, $model) =>
        (bool) $this->auth->can($action, $model)
        );

        $this->latte->addFunction('hook', fn ($key) =>
        HookManager::do($key)
        );

        $this->latte->addFunction('csp', fn () =>
        SecurityManager::getNonce()
        );

        $this->latte->addFunction('datetime', fn () =>
        Carbon::class
        );

        $this->latte->addFunction('value', fn ($key, $default = null) =>
        isset($key) ? $key : $default
        );
    }

    private function registerExtensions(): void
    {
        $this->latte->addExtension(new ButtonTag());
    }

    private function registerProviders(): void
    {
        $this->latte->addProvider('customButtonRender', function ($args) {
            if ($file = TemplateManager::file('buttons/button.' . $args['type'] . '.latte')) {
                $this->latte->render($file, $args);
                return;
            }

            throw new \Exception("Button template for type '{$args['type']}' not found.");
        });
    }
}
