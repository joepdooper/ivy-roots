<?php

namespace Ivy;

use Ivy\Manager\DatabaseManager;
use Ivy\Manager\LanguageManager;
use Ivy\Manager\RouterManager;
use Ivy\Manager\SessionManager;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\User;

class App
{
    private function loadPluginRoutesAssets(): void
    {
        $plugins = (new Plugin)->where('active', 1)->fetchAll();
        if (!empty($plugins)) {
            SessionManager::set('plugin_actives', array_map(fn($plugin) => $plugin->name, $plugins));
            foreach ($plugins as $plugin) {
                require Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $plugin->url . DIRECTORY_SEPARATOR . 'plugin.php';
            }
        } else {
            SessionManager::set('plugin_actives', []);
        }
    }

    private function loadRoutes(): void
    {
        $router = RouterManager::instance();
        $router->setBasePath(Path::get('SUBFOLDER'));

        require Path::get('PUBLIC_PATH') . 'routes/middleware.php';

        require TemplateManager::file('template.php');
        $this->loadPluginRoutesAssets();

        require Path::get('PUBLIC_PATH') . 'routes/web.php';
        require Path::get('PUBLIC_PATH') . 'routes/admin.php';
        require Path::get('PUBLIC_PATH') . 'routes/error.php';

        $router->run();
    }

    private function bootstrap(): void
    {
        User::setAuth();
        TemplateManager::init();
        LanguageManager::init();
    }

    public function run(): void
    {
        (\Dotenv\Dotenv::createImmutable(Path::get('PUBLIC_PATH')))->load();
        $this->bootstrap();
        $this->loadRoutes();
    }
}
