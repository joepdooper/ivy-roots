<?php

namespace Ivy\Plugin\Infrastructure\Metadata;

class PluginInfoFactory
{
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
