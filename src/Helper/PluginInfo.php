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

        $infoJsonContent = PluginHelper::parseJson($url . DIRECTORY_SEPARATOR . 'info.json');

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

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function hasCollection(): bool
    {
        return !empty($this->collection);
    }

    /**
     * @return array
     */
    public function getCollection(): array
    {
        return $this->collection;
    }

    /**
     * @return bool
     */
    public function hasSettings(): bool
    {
        return !empty($this->settings);
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return bool
     */
    public function hasActions(): bool
    {
        return !empty($this->actions);
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return array|null
     */
    public function getDatabase(): ?array
    {
        return $this->database;
    }

    /**
     * @return array|null
     */
    public function getDependencies(): ?array
    {
        return $this->dependencies;
    }
}
