<?php

namespace Ivy;

use Bramus\Router\Router;

class App
{
    private static \Bramus\Router\Router $router;

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
                require $this->pluginPath($plugin->url . DIRECTORY_SEPARATOR . 'plugin.php');
            }
        } else {
            $_SESSION['plugin_actives'] = [];
        }
    }

    private function loadRoutes(): void
    {
        self::$router = new \Bramus\Router\Router();
        self::$router->setBasePath(Path::get('SUBFOLDER'));
        require $this->publicPath('routes/middleware.php');
        require Template::file('template.php');
        $this->loadPluginRoutesAssets();
        require $this->publicPath('routes/web.php');
        require $this->publicPath('routes/admin.php');
        require $this->publicPath('routes/error.php');
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

    private function realPath(string $path): string
    {
        $file = new \Symfony\Component\HttpFoundation\File\File($path);
        $file = $file->getRealPath();

        if ($file === false ||
            (!str_starts_with($file, Path::get('BASE_PATH')) && !str_starts_with($file, Path::get('PUBLIC_PATH')))
        ) {
            throw new \Exception('Invalid file path: ' . $path);
        }

        return $file;
    }

}
