<?php

namespace Ivy\Plugin\Infrastructure\Metadata;

use Exception;
use Ivy\Plugin\Infrastructure\Service\PluginService;

class PluginInfoLoader
{
    /**
     * @param string $url
     * @return array|null
     *
     * @throws Exception
     */
    public function load(string $url): array|null
    {
        return PluginService::parseJson($url . DIRECTORY_SEPARATOR . 'info.json');
    }
}
