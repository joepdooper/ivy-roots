<?php

namespace Ivy\Template\Infrastructure\Renderer;

use Latte\Engine;
use Ivy\Template\Infrastructure\Manager\TemplateManager;

final class ButtonRenderer
{
    private array $templateCache = [];

    public function __construct(
        private Engine $latte,
        private TemplateManager $templates
    ) {}

    public function render(array $args): string
    {
        $type = $args['type'] ?? null;

        if (!$type) {
            throw new \InvalidArgumentException('Button type missing');
        }

        $file = $this->templateCache[$type]
            ??= $this->templates->file("buttons/button.$type.latte");

        if (!$file) {
            throw new \RuntimeException("Button '{$type}' not found");
        }

        return $this->latte->renderToString($file, $args);
    }
}