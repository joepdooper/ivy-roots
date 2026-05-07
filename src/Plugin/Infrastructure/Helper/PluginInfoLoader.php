<?php

namespace Ivy\Infrastructure\Helper;

class PluginInfoLoader
{
    /**
     * @throws \Exception
     */
    public function load(string $url): array
    {
        return PluginHelper::parseJson($url . DIRECTORY_SEPARATOR . 'info.json');
    }
}
