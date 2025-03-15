<?php

namespace Ivy;

use Bramus\Router\Router;

class App
{
    private static \Bramus\Router\Router $router;

    private string $templateRoutesAssets = 'template.php';
    private string $pluginRoutesAssets = 'plugin.php';
    private string $coreMiddlewareRoutes = 'routes/middleware.php';
    private string $coreAdminRoutes = 'routes/admin.php';
    private string $coreErrorRoutes = 'routes/error.php';
    private string $coreWebRoutes = 'routes/web.php';

    public static function router(): Router
    {
        return self::$router;
    }

    private function setTemplate(): void
    {
        $sql = "SELECT `value` FROM `template` WHERE `type` = :type";
        define('_TEMPLATE_BASE', Path::get('TEMPLATES_PATH') . DB::getConnection()->selectValue($sql, ['base']) . DIRECTORY_SEPARATOR);
        define('_TEMPLATE_SUB', Path::get('TEMPLATES_PATH') . DB::getConnection()->selectValue($sql, ['sub']) . DIRECTORY_SEPARATOR);
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
            $_SESSION['plugin_actives'] = array_map(fn($plugin) => $plugin->name, $plugins);
            foreach ($plugins as $plugin) {
                include PluginHelper::getRealPath(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $plugin->url . DIRECTORY_SEPARATOR . $this->pluginRoutesAssets);
            }
        } else {
            $_SESSION['plugin_actives'] = [];
        }
    }

    private function loadRoutes(): void
    {
        self::$router = new \Bramus\Router\Router();
        self::$router->setBasePath(Path::get('SUBFOLDER'));
        self::include(Path::get('PUBLIC_PATH') . $this->coreMiddlewareRoutes);
        self::include(Template::file($this->templateRoutesAssets));
        $this->loadPluginRoutesAssets();
        self::include(Path::get('PUBLIC_PATH') . $this->coreWebRoutes);
        self::include(Path::get('PUBLIC_PATH') . $this->coreAdminRoutes);
        self::include(Path::get('PUBLIC_PATH') . $this->coreErrorRoutes);
        self::$router->run();
    }

    private function bootstrap(): void
    {
        DB::init();
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

    private static function include(string $filePath): void
    {
        try {
            $file = new \Symfony\Component\HttpFoundation\File\File($filePath, false);
            $realPath = $file->getRealPath();

            if ($realPath === false || !str_starts_with($realPath, Path::get('PUBLIC_PATH'))) {
                Message::add('Unauthorized file access');
                return;
            }

            include $realPath;
        } catch (\Exception $e) {
            Message::add('Error: ' . $e->getMessage(), Path::get('BASE_PATH'));
        }
    }

}
