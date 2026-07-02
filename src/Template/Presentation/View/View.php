<?php

namespace Ivy\Template\Presentation\View;

use Ivy\Shared\Infrastructure\Manager\SessionManager;
use Ivy\Template\Application\Contracts\ViewEngineInterface;
use Ivy\Template\Infrastructure\Manager\TemplateManager;

class View
{
    private static ViewEngineInterface $engine;

    public static function setEngine(ViewEngineInterface $engine): void
    {
        self::$engine = $engine;
        self::$engine->boot();
    }

    public static function render(string $name, array $params = [], ?string $block = null): void
    {
        $flashBag = SessionManager::getFlashBag();

        $params['errors'] = $flashBag->has('errors') ? $flashBag->get('errors') : [];
        $params['old'] = $flashBag->has('old') ? $flashBag->get('old') : [];
        $params['new'] = $flashBag->has('new') ? $flashBag->get('new') : [];
        $params['flashes'] = $flashBag->all();

        self::$engine->render(
            TemplateManager::file($name),
            $params,
            $block
        );
    }

    public static function engine(): ViewEngineInterface
    {
        return self::$engine;
    }
}
