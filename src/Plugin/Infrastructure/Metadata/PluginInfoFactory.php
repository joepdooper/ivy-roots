<?php

namespace Ivy\Plugin\Infrastructure\Metadata;

class PluginInfoFactory
{
    /**
     * @param array{
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
     * } $data
      * @return PluginInfo
     */
    public function make(array $data): PluginInfo
    {
        return new PluginInfo(
            name: $data['name'],
            interface: $data['interface'],
            version: $data['version'],
            description: $data['description'],
            url: $data['url'],
            type: $data['type'],
            collection: $data['collection'] ?? [],
            settings: $data['settings'] ?? [],
            actions: $data['actions'] ?? [],
            dependencies: $data['dependencies'] ?? [],
        );
    }
}
