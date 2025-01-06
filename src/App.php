<?php

namespace Ivy;

class App
{
    use Stash;

    private static \Bramus\Router\Router $router;
    private string $templateRoutesAssets = 'template.php';
    private string $pluginRoutesAssets = 'plugin.php';
    private string $coreRoutesAssets = 'routes.php';

    private function initialize(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(_PUBLIC_PATH);
        $dotenv->load();
        new DB();
        User::auth();
    }

    private function stashSettings(): void
    {
        Setting::stashByColumnKey('name');
    }

    private function setTemplate(): void
    {
        $sql = "SELECT `value` FROM `template` WHERE `type` = :type";
        define('_TEMPLATE_BASE', _TEMPLATES_PATH . DB::$connection->selectValue($sql, ['base']) . DIRECTORY_SEPARATOR);
        define('_TEMPLATE_SUB', _TEMPLATES_PATH . DB::$connection->selectValue($sql, ['sub']) . DIRECTORY_SEPARATOR);
    }

    private function setLanguage(): void
    {
        Language::setDefaultLang(substr(Setting::getFromStashByKey('language')->value, 0, 2));
    }

    private function loadPlugins(): void
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

    private function loadTemplate() {
        include Template::file($this->templateRoutesAssets);
    }

    private function loadCore() {
        include _PUBLIC_PATH . $this->coreRoutesAssets;
    }

    private function loadRoutes(): void
    {
        $this->loadTemplate();
        $this->loadPlugins();
        $this->loadCore();
    }

    public static function router() {
        return self::$router;
    }

    public function loadCoreRoutesAssets(string $routes){
        $this->coreRoutesAssets = $routes;
    }

    public function loadPluginRoutesAssets(string $routes){
        $this->pluginRoutesAssets = $routes;
    }

    public function loadTemplateRoutesAssets(string $routes){
        $this->templateRoutesAssets = $routes;
    }

    public function run() {
        $this->initialize();
        $this->stashSettings();
        $this->setTemplate();
        $this->setLanguage();
        self::$router = new \Bramus\Router\Router();
        self::$router->setBasePath(_SUBFOLDER);
        $this->loadRoutes();
        self::$router->run();
    }

}