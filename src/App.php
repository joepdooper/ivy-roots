<?php

namespace Ivy;

use Bramus\Router\Router;

class App
{
    private static \Bramus\Router\Router $router;

    private string $templateRoutesAssets = 'template.php';
    private string $pluginRoutesAssets = 'plugin.php';
    private string $coreMiddlewareRoutesAssets = 'routes/middleware.php';
    private string $coreAdminRoutesAssets = 'routes/admin.php';
    private string $coreErrorRoutesAssets = 'routes/error.php';

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
        Language::setDefaultLang(substr(Setting::getStashItem('language')->getValue(), 0, 2));
    }

    private function loadPluginRoutes(): void
    {
        $plugins = (new Plugin)->where('active', 1)->fetchAll();
        if (!empty($plugins)) {
            $_SESSION['plugin_actives'] = array_map(fn($plugin) => $plugin->getName(), $plugins);
            foreach ($plugins as $plugin) {
                $pluginPath = Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $plugin->getUrl() . DIRECTORY_SEPARATOR . $this->pluginRoutesAssets;
                if (file_exists($pluginPath)) {
                    include $pluginPath;
                }
            }
        }
    }

    private function loadTemplateRoutes(): void
    {
        include Template::file($this->templateRoutesAssets);
    }

    private function loadRoutes(): void
    {
        self::$router = new \Bramus\Router\Router();
        self::$router->setBasePath(Path::get('SUBFOLDER'));
        include Path::get('PUBLIC_PATH') . $this->coreMiddlewareRoutesAssets;
        $this->loadTemplateRoutes();
        $this->loadPluginRoutes();
        include Path::get('PUBLIC_PATH') . $this->coreAdminRoutesAssets;
        include Path::get('PUBLIC_PATH') . $this->coreErrorRoutesAssets;
        self::$router->run();
    }

    private function bootstrap(): void
    {
        DB::init();
        User::auth();
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
