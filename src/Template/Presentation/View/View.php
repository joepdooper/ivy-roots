<?php

namespace Ivy\Template\Presentation\View;

use Ivy\Plugin\Contracts\ViewEngineInterface;
use Ivy\Plugin\Infrastructure\Manager\SessionManager;
use Ivy\Plugin\Infrastructure\Manager\TemplateManager;
use Ivy\Template\Application\Asset\AuthApplicationService;

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
