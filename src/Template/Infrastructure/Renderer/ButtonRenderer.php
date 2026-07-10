<?php

namespace Ivy\Template\Infrastructure\Renderer;

use Ivy\Template\Infrastructure\Manager\TemplateManager;
use Latte\Engine;

final class ButtonRenderer
{
    /** @var array<string, string> */
    private array $templateCache = [];

    public function __construct(
        private readonly Engine $latte,
        private readonly TemplateManager $templates
    ) {}

    /**
     * @param  array<string, mixed>  $args
     */
    public function render(array $args): string
    {
        $type = $args['type'] ?? null;

        if (! $type) {
            throw new \InvalidArgumentException('Button type missing');
        }

        $file = $this->templateCache[$type]
            ??= $this->templates->file("buttons/button.$type.latte");

        if (! $file) {
            throw new \RuntimeException("Button '{$type}' not found");
        }

        return $this->latte->renderToString($file, $args);
    }
}
