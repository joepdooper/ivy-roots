<?php

namespace Ivy\Template\Presentation\View\Engine;

use Carbon\Carbon;
use Ivy\Setting\Domain\Entity\Info;
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Shared\Infrastructure\Manager\CsrfManager;
use Ivy\Shared\Infrastructure\Manager\HookManager;
use Ivy\Shared\Infrastructure\Manager\SecurityManager;
use Ivy\Template\Application\Contracts\ViewEngineInterface;
use Ivy\Template\Infrastructure\Manager\AssetManager;
use Ivy\Template\Infrastructure\Manager\TemplateManager;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Application\Service\AuthService;
use Latte\Engine;
use Latte\Extension;
use Latte\Runtime\Html;
use Ivy\Shared\Config\Environment;
use Ivy\Shared\Core\Language;
use Ivy\Shared\Core\Path;
use Ivy\Plugin\Infrastructure\Registry\PluginRegistry;
use Ivy\Template\Presentation\Tag\ButtonTag;
use Symfony\Component\HttpFoundation\Request;

class LatteEngine implements ViewEngineInterface
{
    private Engine $latte;
    private AuthService $auth;
    private Request $request;

    public function __construct(AuthService $auth, Request $request)
    {
        $this->latte = new Engine();
        $this->auth = $auth;
        $this->request = $request;
    }

    public function boot(): void
    {
        $this->latte->setCacheDirectory(Path::get('PROJECT_PATH') . 'cache/templates');
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

    public function addExtension(Extension $extension): void
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

        $this->latte->addFunction('info', fn ($key) =>
            Info::stashGet($key)->value ?? ''
        );

        $this->latte->addFunction('setting', fn ($key) =>
            Setting::stashGet($key)->value ?? ''
        );

        $this->latte->addFunction('enabled', fn ($key) =>
            Setting::stashGet($key)->bool ?? false
        );

        $this->latte->addFunction('isPluginActive', fn (string $key): bool =>
            PluginRegistry::isActive($key)
        );

        $this->latte->addFunction('csrf', fn () =>
            new Html('<input type="hidden" name="csrf_token" value="' . CsrfManager::token() . '">')
        );

        $this->latte->addFunction('canEditAsEditor', fn () =>
            $this->auth->canEditAsEditor()
        );

        $this->latte->addFunction('canEditAsAdmin', fn () =>
            $this->auth->canEditAsAdmin()
        );

        $this->latte->addFunction('canEditAsSuperAdmin', fn () =>
            $this->auth->canEditAsSuperAdmin()
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

        $this->latte->addFunction('authProfile', fn () =>
            $this->auth->authProfile()
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

        $this->latte->addFunction('cssFiles', fn () =>
            AssetManager::getCSS()
        );

        $this->latte->addFunction('jsFiles', fn () =>
            AssetManager::getJS()
        );

        $this->latte->addFunction('moduleFiles', fn () =>
            AssetManager::getModules()
        );

        $this->latte->addFunction('now', fn () => Carbon::now());

        $this->latte->addFunction('parseDate', fn ($v) => Carbon::parse($v));

        $this->latte->addFunction('monthName', fn ($i) =>
            Carbon::create()->month($i)->format('F')
        );

        $this->latte->addFunction('value', fn ($key, $default = null) =>
            $key ?? $default
        );

        $this->latte->addFunction('queryUrl', function (array $replace = [], array $remove = []) {

            $query = $this->request->query->all();

            foreach ($replace as $key => $value) {
                $query[$key] = $value;
            }

            foreach ($remove as $key) {
                unset($query[$key]);
            }

            return '?' . http_build_query($query);
        });
    }

    private function registerExtensions(): void
    {
        $this->latte->addExtension(new ButtonTag());
    }

    private function registerProviders(): void
    {

    }
}
