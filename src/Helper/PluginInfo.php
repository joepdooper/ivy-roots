<?php

namespace Ivy\Helper;

final class PluginInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $interface,
        public readonly ?string $version,
        public readonly ?string $description,
        public readonly string $url,
        public readonly ?string $type,
        public readonly array $collection = [],
        public readonly array $settings = [],
        public readonly array $actions = [],
        public readonly array $dependencies = [],
    ) {}

    public function hasCollection(): bool
    {
        return $this->collection !== [];
    }

    public function hasSettings(): bool
    {
        return $this->settings !== [];
    }

    public function hasActions(): bool
    {
        return $this->actions !== [];
    }

    public function hasDependencies(): bool
    {
        return $this->dependencies !== [];
    }
}
