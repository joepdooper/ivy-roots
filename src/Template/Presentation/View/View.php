<?php

namespace Ivy\Presentation\View;

use Ivy\Shared\Contracts\ViewEngineInterface;
use Ivy\Infrastructure\Manager\SessionManager;
use Ivy\Infrastructure\Manager\TemplateManager;
use Ivy\Application\Service\AuthApplicationService;

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
