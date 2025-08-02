<?php

namespace Ivy\View;

use Ivy\Core\Language;
use Ivy\Manager\HookManager;
use Ivy\Manager\SessionManager;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Info;
use Ivy\Model\Profile;
use Ivy\Model\User;
use Ivy\Core\Path;
use Latte\Engine;

class LatteEngine implements EngineInterface
{
    protected Engine $latte;

    public function __construct()
    {
        $this->latte = new Engine();
        $this->latte->setTempDirectory(Path::get('PROJECT_PATH') . 'cache/templates');
        $this->latte->setAutoRefresh($_ENV['APP_ENV'] ?? 'production' === 'development');

        // Register functions
        $this->latte->addFunction('icon', fn($icon) => file_get_contents(Path::get('PROJECT_PATH') . "/media/icon/" . $icon));
        $this->latte->addFunction('text', fn($key, $vars = null) => Language::translate($key, $vars) ?? $key);
        $this->latte->addFunction('path', fn($key) => Path::get($key));
        $this->latte->addFunction('file', fn($key) => TemplateManager::file($key));
        $this->latte->addFunction('render', fn($key, $vars = []) => View::render($key, $vars));
        $this->latte->addFunction('setting', fn($key) => Info::getStash()[$key]->value ?? '');
        $this->latte->addFunction('enabled', fn($key) => Info::getStash()[$key]->bool ?? false);
        $this->latte->addFunction('isPluginActive', fn($key) => in_array($key, SessionManager::get('plugin_actives')));
        $this->latte->addFunction('csrf', fn() => new \Latte\Runtime\Html('<input type="hidden" name="csrf_token" value="' . View::generateCsrfToken() . '">'));
        $this->latte->addFunction('auth', fn() => User::getAuth());
        $this->latte->addFunction('canEditAsEditor', fn() => User::canEditAsEditor());
        $this->latte->addFunction('canEditAsAdmin', fn() => User::canEditAsAdmin());
        $this->latte->addFunction('canEditAsSuperAdmin', fn() => User::canEditAsSuperAdmin());
        $this->latte->addFunction('profile', fn() => Profile::getUserProfile());
        $this->latte->addFunction('hook', fn($key) => HookManager::do($key));

        $this->latte->addExtension(new \Ivy\Tag\ButtonTag());
        $this->latte->addProvider('customButtonRender', function ($args) {
            if ($file = TemplateManager::file('buttons/button.' . $args['type'] . '.latte')) {
                $this->latte->render($file, $args);
            } else {
                throw new \Exception("Button template for type '{$args['type']}' not found.");
            }
        });
    }

    public function render(string $template, array $params = [], ?string $block = null): void
    {
        $this->latte->render($template, $params, $block);
    }
}