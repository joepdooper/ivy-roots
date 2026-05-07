<?php

namespace Ivy\Shared\Core;

use Bramus\Router\Router;
use Dotenv\Dotenv;
use Illuminate\Container\Container;
use Ivy\Domain\Model\InfoModel;
use Ivy\Domain\Model\PluginModel;
use Ivy\Domain\Model\SettingModel;
use Ivy\Shared\Contracts\PluginInterface;
use Ivy\Domain\Exception\AuthorizationException;
use Ivy\Application\Handler\MinifyCssHandler;
use Ivy\Application\Handler\MinifyJsHandler;
use Ivy\Infrastructure\Manager\DatabaseManager;
use Ivy\Infrastructure\Manager\ErrorManager;
use Ivy\Infrastructure\Manager\LanguageManager;
use Ivy\Infrastructure\Manager\RouterManager;
use Ivy\Infrastructure\Manager\SecurityManager;
use Ivy\Infrastructure\Manager\TemplateManager;
use Ivy\Presentation\Middleware\CsrfVerifier;
use Ivy\Presentation\Middleware\MiddlewarePipeline;
use Ivy\Presentation\Middleware\RequestNormalizer;
use Ivy\Infrastructure\Registry\PluginRegistry;
use Ivy\Infrastructure\Registry\SettingRegistry;
use Ivy\Application\Service\AuthApplicationService;
use Ivy\Presentation\View\Engine\LatteEngine;
use Ivy\Presentation\View\View;
use Symfony\Component\HttpFoundation\Request;

class App
{
    private DatabaseManager $databaseManager;
    private Router $router;
    protected Container $container;

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
        $plugins = PluginModel::select('name', 'interface')
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

        $this->container = new Container();
        $request = Request::createFromGlobals();
        $this->container->instance(Request::class, $request);
        Container::setInstance($this->container);

        $pipeline = new MiddlewarePipeline;
        $pipeline->add(new RequestNormalizer());
        $pipeline->add(new CsrfVerifier());

        ErrorManager::setErrorReporting();
        SecurityManager::setSecurityHeaders();

        $this->initDatabase();

        $auth = new AuthApplicationService();
        $this->container->instance(AuthApplicationService::class, $auth);

        $this->router = RouterManager::router();
        $this->router->setBasePath(Path::get('SUBFOLDER'));

        InfoModel::stash()->keyByColumn('name');
        SettingModel::stash()->keyByColumn('name');

        TemplateManager::init();
        LanguageManager::init();

        $engine = match ($_ENV['VIEW_ENGINE'] ?? 'latte') {
            'latte' => new LatteEngine(),
            default => new LatteEngine(),
        };

        $engine->setAuth($auth);

        View::setEngine($engine);

        $this->initPlugins();

        TemplateManager::require('template.php');

        SettingRegistry::define('Minify CSS', [
            'handler' => MinifyCssHandler::class,
        ]);

        SettingRegistry::define('Minify JS', [
            'handler' => MinifyJsHandler::class,
        ]);

        try {
            $pipeline->handle($request, function () {
                $this->router->run();
            });
        } catch (AuthorizationException $e) {
            http_response_code(403);
            View::render('errors/forbidden.latte', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
