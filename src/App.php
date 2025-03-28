<?php

namespace Ivy;

use Bramus\Router\Router;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Delight\Db\PdoDatabase;
use PDO;
use PDOException;
use RuntimeException;

class App
{
    private static Router $router;
    private static Session $session;
    private static PdoDatabase $db;

    public static function session(): Session
    {
        if (!isset(self::$session)) {
            $storage = new NativeSessionStorage([], new NativeFileSessionHandler());
            self::$session = new Session($storage);
        }
        return self::$session;
    }

    public static function router(): Router
    {
        if (!isset(self::$router)) {
            self::$router = new Router();
        }
        return self::$router;
    }

    public static function db(): PdoDatabase
    {
        if (!isset(self::$db)) {
            try {
                $pdo = new PDO(
                    "mysql:host=" . $_ENV['DB_HOST'] . ";port=" . $_ENV['DB_PORT'] . ";dbname=" . $_ENV['DB_DATABASE'] . ";charset=utf8",
                    $_ENV['DB_USERNAME'],
                    $_ENV['DB_PASSWORD']
                );
                self::$db = PdoDatabase::fromPdo($pdo);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                throw new RuntimeException("Database connection failed.");
            }
        }
        return self::$db;
    }

    private function setTemplate(): void
    {
        $sql = "SELECT `value` FROM `template` WHERE `type` = :type";
        define('_TEMPLATE_BASE', Path::get('TEMPLATES_PATH') . App::db()->selectValue($sql, ['base']) . DIRECTORY_SEPARATOR);
        define('_TEMPLATE_SUB', Path::get('TEMPLATES_PATH') . App::db()->selectValue($sql, ['sub']) . DIRECTORY_SEPARATOR);
    }

    private function setLanguage(): void
    {
        Setting::stash()->keyByColumn('name');
        Language::setDefaultLang(substr(Setting::getStashItem('language')->value, 0, 2));
    }

    private function loadPluginRoutesAssets(): void
    {
        $plugins = (new Plugin)->where('active', 1)->fetchAll();
        if (!empty($plugins)) {
            self::session()->set('plugin_actives', array_map(fn($plugin) => $plugin->name, $plugins));
            foreach ($plugins as $plugin) {
                require Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $plugin->url . DIRECTORY_SEPARATOR . 'plugin.php';
            }
        } else {
            self::session()->set('plugin_actives', []);
        }
    }

    private function loadRoutes(): void
    {
        self::router()->setBasePath(Path::get('SUBFOLDER'));
        require Path::get('PUBLIC_PATH') . 'routes/middleware.php';
        require TemplateManager::file('template.php');
        $this->loadPluginRoutesAssets();
        require Path::get('PUBLIC_PATH') . 'routes/web.php';
        require Path::get('PUBLIC_PATH') . 'routes/admin.php';
        require Path::get('PUBLIC_PATH') . 'routes/error.php';
        self::router()->run();
    }

    private function bootstrap(): void
    {
        User::setAuth();
        $this->setTemplate();
        $this->setLanguage();
    }

    public function run(): void
    {
        (\Dotenv\Dotenv::createImmutable(Path::get('PUBLIC_PATH')))->load();
        $this->bootstrap();
        $this->loadRoutes();
    }
}
