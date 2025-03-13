<?php

namespace Ivy;

class PluginInfo
{
    private ?string $name;
    private ?string $version;
    private ?string $description;
    private ?string $url;
    private ?string $type;
    private ?int $settings;
    private ?bool $collection;
    private ?array $database;
    private ?array $dependencies;

    public function __construct(string $url)
    {
        $this->url = $url;

        $infoJsonPath = Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $url . DIRECTORY_SEPARATOR . 'info.json';
        $infoJsonContent = JsonHelper::parse($infoJsonPath);

        $this->name = $infoJsonContent['name'] ?? null;
        $this->version = $infoJsonContent['version'] ?? null;
        $this->description = $infoJsonContent['description'] ?? null;
        $this->type = $infoJsonContent['type'] ?? null;
        $this->settings = isset($infoJsonContent['settings']) ? (int)$infoJsonContent['settings'] : 0;
        $this->database = $infoJsonContent['database'] ?? null;
        $this->dependencies = $infoJsonContent['dependencies'] ?? null;
        $this->collection = $infoJsonContent['collection'] ?? null;
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
     * @return int|null
     */
    public function getSettings(): ?int
    {
        return $this->settings;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return bool|null
     */
    public function getCollection(): ?bool
    {
        return $this->collection;
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
