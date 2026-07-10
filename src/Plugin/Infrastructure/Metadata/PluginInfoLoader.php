<?php

namespace Ivy\Plugin\Infrastructure\Metadata;

use Exception;
use Ivy\Plugin\Infrastructure\Service\PluginService;

class PluginInfoLoader
{
    /**
     * @return array<string, mixed>|null
     *
     * @throws Exception
     */
    public function load(string $url): ?array
    {
        return PluginService::parseJson($url.DIRECTORY_SEPARATOR.'info.json');
    }
}
