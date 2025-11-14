<?php

namespace Ivy\Livy;

use Latte\Extension;
use Latte\Compiler\Tag;
use Latte\Compiler\Nodes\Php\ExpressionNode;

class LivyExtension extends Extension
{
    public function getTags(): array
    {
        return [
            'livy' => [$this, 'parseLivyTag'],
        ];
    }

    public function parseLivyTag(Tag $tag): ExpressionNode
    {
        $tag->expectArguments();
        $componentName = $tag->parser->parseExpression();

        return new ExpressionNode(
            'echo (new \Ivy\Livy\LivyManager())->renderComponent(' . $componentName . ');'
        );
    }
}
