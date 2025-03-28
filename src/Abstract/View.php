<?php

namespace Ivy\Abstract;

use Ivy\App;

abstract class View
{
    protected static string $name = '';
    protected static array $params = [];
    protected static ?string $block;

    abstract public static function set(string $name, array $params = [], ?string $block = null): void;

    abstract public static function render(string $name, array $params = [], ?string $block = null): void;
}

