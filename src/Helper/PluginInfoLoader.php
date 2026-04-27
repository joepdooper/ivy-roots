<?php

namespace Ivy\Helper;

class PluginInfoLoader
{
    public function load(string $url): array
    {
        return PluginHelper::parseJson($url . DIRECTORY_SEPARATOR . 'info.json');
    }
}
