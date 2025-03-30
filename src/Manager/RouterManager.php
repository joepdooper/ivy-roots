<?php
namespace Ivy\Manager;

use Bramus\Router\Router;

class RouterManager
{
    private static ?Router $router = null;

    public static function instance(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();
        }
        return self::$router;
    }
}