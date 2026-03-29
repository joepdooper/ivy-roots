<?php

namespace Ivy\Helper;

class PluginInfo
{
    private ?string $name;

    private ?string $version;

    private ?string $description;

    private ?string $url;

    private ?string $type;

    private array $collection = [];

    private array $settings = [];

    private array $actions = [];

    private ?array $database;

    private ?array $dependencies;

    public function __construct(string $url)
    {
        $this->url = $url;

        $infoJsonContent = PluginHelper::parseJson($url.DIRECTORY_SEPARATOR.'info.json');

        $this->name = $infoJsonContent['name'] ?? null;
        $this->version = $infoJsonContent['version'] ?? null;
        $this->description = $infoJsonContent['description'] ?? null;
        $this->type = $infoJsonContent['type'] ?? null;
        $this->collection = $infoJsonContent['collection'] ?? [];
        $this->settings = $infoJsonContent['settings'] ?? [];
        $this->actions = $infoJsonContent['actions'] ?? [];
        $this->database = $infoJsonContent['database'] ?? null;
        $this->dependencies = $infoJsonContent['dependencies'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function hasCollection(): bool
    {
        return ! empty($this->collection);
    }

    public function getCollection(): array
    {
        return $this->collection;
    }

    public function hasSettings(): bool
    {
        return ! empty($this->settings);
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function hasActions(): bool
    {
        return ! empty($this->actions);
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getDatabase(): ?array
    {
        return $this->database;
    }

    public function getDependencies(): ?array
    {
        return $this->dependencies;
    }
}
