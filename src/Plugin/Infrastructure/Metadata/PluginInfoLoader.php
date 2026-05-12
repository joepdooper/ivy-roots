<?php

namespace Ivy\Plugin\Infrastructure\Metadata;

use Ivy\Plugin\Infrastructure\Service\PluginService;

class PluginInfoLoader
{
    /**
     * @throws \Exception
     */
    public function load(string $url): array
    {
        return PluginService::parseJson($url . DIRECTORY_SEPARATOR . 'info.json');
    }
}
