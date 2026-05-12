<?php

namespace Ivy\Plugin\Infrastructure\Metadata;

final readonly class PluginInfo
{
    public function __construct(
        public string  $name,
        public string  $interface,
        public ?string $version,
        public ?string $description,
        public string  $url,
        public ?string $type,
        public array   $collection = [],
        public array   $settings = [],
        public array   $actions = [],
        public array   $dependencies = [],
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
