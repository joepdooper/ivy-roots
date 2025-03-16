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
                include PluginHelper::getRealPath($this->pluginPath($plugin->url . DIRECTORY_SEPARATOR . $this->pluginRoutesAssets));
            }
        } else {
            $_SESSION['plugin_actives'] = [];
        }
    }

    private function loadRoutes(): void
    {
        self::$router = new \Bramus\Router\Router();
        self::$router->setBasePath(Path::get('SUBFOLDER'));
        require $this->publicPath($this->coreMiddlewareRoutes);
        require Template::file($this->templateRoutesAssets);
        $this->loadPluginRoutesAssets();
        require $this->publicPath($this->coreWebRoutes);
        require $this->publicPath($this->coreAdminRoutes);
        require $this->publicPath($this->coreErrorRoutes);
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

    public function basePath($path = ''): string
    {
        return Path::get('BASE_PATH') . $path;
    }

    public function publicPath($path = ''): string
    {
        return Path::get('PUBLIC_PATH') . $path;
    }

    public function pluginPath($path = ''): string
    {
        return $this->publicPath(Path::get('PLUGIN_PATH') . $path);
    }

}
