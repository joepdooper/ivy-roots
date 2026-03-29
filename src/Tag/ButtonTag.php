<?php

namespace Ivy\Tag;

use Latte\Extension;

class ButtonTag extends Extension
{
    public function getTags(): array
    {
        return [
            'button' => [ButtonNode::class, 'create'],
        ];
    }
}
