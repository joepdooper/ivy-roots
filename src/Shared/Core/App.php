<?php

namespace Ivy\Shared\Core;

use Bramus\Router\Router;
use Dotenv\Dotenv;
use Illuminate\Container\Container;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Application\Contracts\PluginInterface;
use Ivy\Setting\Domain\Entity\Info;
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Setting\Infrastructure\Registry\SettingRegistry;
use Ivy\Shared\Domain\Exception\FileException;
use Ivy\Shared\Infrastructure\Manager\DatabaseManager;
use Ivy\Shared\Infrastructure\Manager\ErrorManager;
use Ivy\Shared\Infrastructure\Manager\LanguageManager;
use Ivy\Shared\Infrastructure\Manager\RouterManager;
use Ivy\Shared\Infrastructure\Manager\SecurityManager;
use Ivy\Template\Infrastructure\Manager\TemplateManager;
use Ivy\Template\Presentation\View\Engine\BladeEngine;
use Ivy\User\Application\Service\AuthService;
use Ivy\Template\Application\Handler\MinifyCssHandler;
use Ivy\Template\Application\Handler\MinifyJsHandler;
use Ivy\Shared\Presentation\Middleware\CsrfVerifier;
use Ivy\Shared\Presentation\Middleware\MiddlewarePipeline;
use Ivy\Shared\Presentation\Middleware\RequestNormalizer;
use Ivy\Plugin\Infrastructure\Registry\PluginRegistry;
use Ivy\Template\Presentation\View\Engine\LatteEngine;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class App
{
    private Router $router;
    protected Container $container;

    private function guardUploadLimits(): void
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        $postMaxRaw = ini_get('post_max_size');

        $postMax = (int) $postMaxRaw * match (strtolower(substr(trim($postMaxRaw), -1))) {
                'g' => 1024 * 1024 * 1024,
                'm' => 1024 * 1024,
                'k' => 1024,
                default => 1,
            };

        if ($contentLength > $postMax && empty($_FILES)) {
            throw new FileException('Upload exceeds server limit');
        }
    }

    private function initDatabase(): void
    {
        $databaseManager = new DatabaseManager();

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

        $databaseManager->addConnection($config);

        $databaseManager->boot();
    }

    private function initPlugins(AuthService $auth): void
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
                $plugin->register($auth);
            }
        }

        PluginRegistry::setActive($active);
    }

    public function run(): void
    {
        $this->guardUploadLimits();

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

        $auth = new AuthService();
        $this->container->instance(AuthService::class, $auth);

        $this->router = RouterManager::router();
        $this->router->setBasePath(Path::get('SUBFOLDER'));

        Info::stash()->keyByColumn('name');
        Setting::stash()->keyByColumn('name');

        TemplateManager::init();
        LanguageManager::init();

        $engine = match ($_ENV['VIEW_ENGINE'] ?? 'latte') {
            'blade' => new BladeEngine(),
            default => new LatteEngine(),
        };

        $engine->setAuth($auth);

        View::setEngine($engine);

        $this->initPlugins($auth);

        TemplateManager::require('template.php');

        SettingRegistry::define('Minify CSS', [
            'handler' => MinifyCssHandler::class,
        ]);

        SettingRegistry::define('Minify JS', [
            'handler' => MinifyJsHandler::class,
        ]);

        try {
            $pipeline->handle($request, function () {
                if (!$this->router->run()) {
                    $this->router->trigger404();
                }
            });
        } catch (AuthorizationException $e) {
            http_response_code(403);
            RouterManager::triggerError(403, $e->getMessage());
            exit;
        } catch (FileException $e) {
            http_response_code(413);
            RouterManager::triggerError(413, $e->getMessage());
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            RouterManager::triggerError(500, $e->getMessage());
            exit;
        }
    }
}
