<?php

namespace Ivy\Core;

use Bramus\Router\Router;
use Dotenv\Dotenv;
use Ivy\Core\Contracts\PluginInterface;
use Ivy\Exception\AuthorizationException;
use Ivy\Handler\MinifyCssHandler;
use Ivy\Handler\MinifyJsHandler;
use Ivy\Manager\DatabaseManager;
use Ivy\Manager\ErrorManager;
use Ivy\Manager\LanguageManager;
use Ivy\Manager\PluginManager;
use Ivy\Manager\RouterManager;
use Ivy\Manager\SecurityManager;
use Ivy\Manager\SessionManager;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Info;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\User;
use Ivy\Registry\PluginRegistry;
use Ivy\Registry\SettingRegistry;
use Ivy\View\View;

class App
{
    private DatabaseManager $databaseManager;
    private Router $router;

    private function initDatabase(): void
    {
        $this->databaseManager = new DatabaseManager();

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

        $this->databaseManager->addConnection($config);

        $this->databaseManager->boot();
    }

    private function initPlugins(): void
    {
        $plugins = Plugin::select('name', 'interface')
            ->where('active', 1)
            ->get();

        $active = [];

        foreach ($plugins as $p) {
            $name = $p->name ?? null;
            $class = $p->interface ?? null;

            if ($name) {
                $active[$name] = true;
            }

            if (!$class || !class_exists($class)) {
                continue;
            }

            $plugin = new $class();

            if ($plugin instanceof PluginInterface) {
                $plugin->register();
            }
        }

        PluginRegistry::setActive($active);
    }

    public function run(): void
    {
        (Dotenv::createImmutable(Path::get('PROJECT_PATH')))->load();

        ErrorManager::setErrorReporting();
        SecurityManager::setSecurityHeaders();

        $this->initDatabase();

        $this->router = RouterManager::router();
        $this->router->setBasePath(Path::get('SUBFOLDER'));

        User::setAuth();

        Info::stash()->keyByColumn('name');
        Setting::stash()->keyByColumn('name');

        TemplateManager::init();
        LanguageManager::init();

        $this->initPlugins();

        TemplateManager::require('template.php');

        SettingRegistry::define('Minify CSS', [
            'handler' => MinifyCssHandler::class,
        ]);

        SettingRegistry::define('Minify JS', [
            'handler' => MinifyJsHandler::class,
        ]);

        try {
            $this->router->run();
        } catch (AuthorizationException $e) {
            http_response_code(403);
            View::set('errors/forbidden.latte', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
