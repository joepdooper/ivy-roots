<?php

namespace Ivy\Tag;

use Latte\Compiler\PrintContext;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Tag;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;

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
            'echo ($this->global->customButtonRender)(%node);',
            $this->args
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->args;
    }
}
