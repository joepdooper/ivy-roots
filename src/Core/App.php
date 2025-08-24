<?php

namespace Ivy\Core;

use Ivy\Manager\DatabaseManager;
use Ivy\Manager\LanguageManager;
use Ivy\Manager\RouterManager;
use Ivy\Manager\SecurityManager;
use Ivy\Manager\SessionManager;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Info;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\User;
use Ivy\View\View;

class App
{
    private function loadPluginRoutesAssets(): void
    {
        $plugins = (new Plugin)->where('active', 1)->fetchAll();
        if (!empty($plugins)) {
            SessionManager::set('plugin_actives', array_map(fn($plugin) => $plugin->name, $plugins));
            foreach ($plugins as $plugin) {
                require Path::get('PLUGINS_PATH') . $plugin->url . DIRECTORY_SEPARATOR . 'plugin.php';
            }
        } else {
            SessionManager::set('plugin_actives', []);
        }
    }

    private function loadRoutes(): void
    {
        $router = RouterManager::instance();
        $router->setBasePath(Path::get('SUBFOLDER'));

        require Path::get('PROJECT_PATH') . 'routes/middleware.php';

        $this->loadPluginRoutesAssets();
        require TemplateManager::file('template.php');

        require Path::get('PROJECT_PATH') . 'routes/web.php';
        require Path::get('PROJECT_PATH') . 'routes/user.php';
        require Path::get('PROJECT_PATH') . 'routes/admin.php';
        require Path::get('PROJECT_PATH') . 'routes/error.php';

        try {
            $router->run();
        } catch (\Ivy\Exceptions\AuthorizationException $e) {
            http_response_code(403);
            View::set('errors/forbidden.latte', [
                'message' => $e->getMessage()
            ]);
            // exit;
        }
    }

    private function bootstrap(): void
    {
        User::setAuth();
        Info::stash()->keyByColumn('name');
        Setting::stash()->keyByColumn('name');
        TemplateManager::init();
        LanguageManager::init();
    }

    public function run(): void
    {
        (\Dotenv\Dotenv::createImmutable(Path::get('PROJECT_PATH')))->load();
        SecurityManager::setSecurityHeaders();
        $this->bootstrap();
        $this->loadRoutes();
    }
}
