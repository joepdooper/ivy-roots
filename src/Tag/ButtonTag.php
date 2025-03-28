<?php

namespace Ivy\Tag;

use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Tag;
use Latte\Compiler\TagParser;
use Latte\Extension;

class ButtonTag extends Extension
{
    public function getTags(): array
    {
        return [
            'button' => [ButtonNode::class, 'create']
        ];
    }
}

