<?php

namespace Ivy\Factory;

use Ivy\Form\PluginInfoForm;
use Ivy\Helper\PluginInfo;
use Ivy\Helper\PluginInfoLoader;

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
