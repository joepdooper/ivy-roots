<?php

namespace Ivy;

use Bramus\Router\Router;

class App
{
    private static \Bramus\Router\Router $router;

    private string $templateRoutesAssets = 'template.php';
    private string $pluginRoutesAssets = 'plugin.php';
    private string $coreRoutesAssets = 'routes.php';

    public static function router(): Router
    {
        return self::$router;
    }

    private function setTemplate(): void
    {
        $sql = "SELECT `value` FROM `template` WHERE `type` = :type";
        define('_TEMPLATE_BASE', _TEMPLATES_PATH . DB::getConnection()->selectValue($sql, ['base']) . DIRECTORY_SEPARATOR);
        define('_TEMPLATE_SUB', _TEMPLATES_PATH . DB::getConnection()->selectValue($sql, ['sub']) . DIRECTORY_SEPARATOR);
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
            $_SESSION['plugin_actives'] = array_column($plugins, 'name');
            foreach ($plugins as $plugin) {
                $pluginPath = _PUBLIC_PATH . _PLUGIN_PATH . $plugin->getUrl() . DIRECTORY_SEPARATOR . $this->pluginRoutesAssets;
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

    private function loadCoreRoutes(): void
    {
        include _PUBLIC_PATH . $this->coreRoutesAssets;
    }

    private function loadRoutes(): void
    {
        self::$router = new \Bramus\Router\Router();
        self::$router->setBasePath(_SUBFOLDER);
        $this->loadTemplateRoutes();
        $this->loadPluginRoutes();
        $this->loadCoreRoutes();
        self::$router->run();
    }

    public function setCoreRoutesAssets(string $routes): void
    {
        $this->coreRoutesAssets = $routes;
    }

    public function setPluginRoutesAssets(string $routes): void
    {
        $this->pluginRoutesAssets = $routes;
    }

    public function setTemplateRoutesAssets(string $routes): void
    {
        $this->templateRoutesAssets = $routes;
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
        $this->bootstrap();
        $this->loadRoutes();
    }

}
