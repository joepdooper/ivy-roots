<?php

namespace Ivy\Core;

use Dotenv\Dotenv;
use Ivy\Exceptions\AuthorizationException;
use Ivy\Manager\DatabaseManager;
use Ivy\Manager\ErrorManager;
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
    private DatabaseManager $db;

    private function initDatabase(): void
    {
        $this->db = new DatabaseManager();

        $config = [
            'driver'    => $_ENV['DB_DRIVER'],
            'host'      => $_ENV['DB_HOST'],
            'port'      => $_ENV['DB_PORT'],
            'database'  => $_ENV['DB_DATABASE'],
            'username'  => $_ENV['DB_USERNAME'],
            'password'  => $_ENV['DB_PASSWORD'],
        ];

        if ($_ENV['DB_DRIVER'] === 'mysql') {
            $config['charset']   = 'utf8mb4';
            $config['collation'] = 'utf8mb4_unicode_ci';
        }

        $this->db->addConnection($config);

        $this->db->boot();
    }

    private function loadPluginRoutesAssets(): void
    {
        $plugins = Plugin::where('active', 1)->get()->toArray();
        if (! empty($plugins)) {
            SessionManager::set('plugin_actives', array_map(fn ($plugin) => $plugin->name, $plugins));
            foreach ($plugins as $plugin) {
                require Path::get('PLUGINS_PATH').$plugin->url.DIRECTORY_SEPARATOR.'plugin.php';
            }
        } else {
            SessionManager::set('plugin_actives', []);
        }
    }

    private function loadRoutes(): void
    {
        $router = RouterManager::router();
        $router->setBasePath(Path::get('SUBFOLDER'));

        $this->loadPluginRoutesAssets();

        require TemplateManager::file('template.php');

        require Path::get('PROJECT_PATH').'routes/web.php';
        require Path::get('PROJECT_PATH').'routes/user.php';
        require Path::get('PROJECT_PATH').'routes/admin.php';
        require Path::get('PROJECT_PATH').'routes/error.php';

        try {
            $router->run();
        } catch (AuthorizationException $e) {
            http_response_code(403);
            View::set('errors/forbidden.latte', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function bootstrap(): void
    {
        $this->initDatabase();

        User::setAuth();

        Info::stash()->keyByColumn('name');
        Setting::stash()->keyByColumn('name');

        TemplateManager::init();
        LanguageManager::init();
    }

    public function run(): void
    {
        (Dotenv::createImmutable(Path::get('PROJECT_PATH')))->load();
        ErrorManager::setErrorReporting();
        SecurityManager::setSecurityHeaders();
        $this->bootstrap();
        $this->loadRoutes();
    }
}
