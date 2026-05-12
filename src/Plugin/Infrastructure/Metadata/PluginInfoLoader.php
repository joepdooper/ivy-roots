<?php

namespace Ivy\Plugin\Infrastructure\Metadata;

use Exception;
use Ivy\Plugin\Infrastructure\Service\PluginService;

class PluginInfoLoader
{
    /**
     * @return array{
     *     name: string,
     *     interface: string,
     *     version: ?string,
     *     description: ?string,
     *     url: string,
     *     type: ?string,
     *     collection?: array<string, mixed>,
     *     settings?: array<string, mixed>,
     *     actions?: array<string, mixed>,
     *     dependencies?: array<string, mixed>
     * }
     *
     * @throws Exception
     */
    public function load(string $url): array
    {
        return PluginService::parseJson($url . DIRECTORY_SEPARATOR . 'info.json');
    }
}
