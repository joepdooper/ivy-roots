<?php

namespace Ivy\Template\Presentation\Tag;

use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

class ButtonNode extends StatementNode
{
    public ArrayNode $args;

    public static function create(Tag $tag): self
    {
        $node = new self;
        $node->args = $tag->parser->parseArguments();

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            <<<'PHP'
$args = %node;

$file = \Ivy\Template\Infrastructure\Manager\TemplateManager::file(
    'buttons/button.' . ($args['type'] ?? 'default') . '.latte'
);

if ($file) {
    $this->createTemplate($file, $args, 'include')->render();
}
PHP,
            $this->args
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->args;
    }
}
