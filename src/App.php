<?php

namespace Ivy;

class App
{
    use Stash;

    private Router $router;

    private function includeAutoLoaders():void
    {
        require_once _PUBLIC_PATH . 'vendor/autoload.php';
        require_once _PUBLIC_PATH . 'core/include/autoloader.php';
    }

    private function includeGlobalFunctions(): void
    {
        require_once _PUBLIC_PATH . 'core/include/functions.php';
    }

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

        Template::addJS("core/js/helper.js");
    }

    private function loadPlugins(): void
    {
        $plugins = (new Plugin)->where('active', 1)->fetchAll();
        if (!empty($plugins)) {
            $_SESSION['plugin_actives'] = array_column($plugins, 'name');
            foreach ($plugins as $plugin) {
                $pluginPath = _PUBLIC_PATH . _PLUGIN_PATH . $plugin->getUrl() . DIRECTORY_SEPARATOR . 'plugin.php';
                if (file_exists($pluginPath)) {
                    include $pluginPath;
                }
            }
        }
    }

    private function loadRoutes(): void
    {
        include Template::file('template.php');

        Language::setDefaultLang(substr(Setting::getFromStashByKey('language')->value, 0, 2));

        $this->loadPlugins();

        include _PUBLIC_PATH . 'core/include/routes.php';
    }

    public function run() {
        $this->includeAutoloaders();
        $this->includeGlobalFunctions();
        $this->initialize();
        $this->stashSettings();
        $this->setTemplate();
        // start router
        $this->router = new Router();
        $this->router->setBasePath(_SUBFOLDER);
        // template, plugin and core assets and routes
        $this->loadRoutes();
        // run router
        $this->router->run();
    }

}