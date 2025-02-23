<?php

namespace Ivy;

class Plugin extends Model
{
    protected string $table = 'plugin';
    protected string $path = _BASE_PATH . 'admin/plugin';
    protected array $columns = [
        'name',
        'url',
        'version',
        'description',
        'type',
        'active',
        'settings',
        'parent_id'
    ];

    protected string $name;
    protected string $url;
    protected string $version;
    protected ?string $description;
    protected ?string $type;
    protected ?int $active;
    protected ?int $settings;
    protected ?int $parent_id;
    protected ?bool $collection = false;
    protected PluginInfo $info;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getVersion(): string
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
    public function getActive(): ?int
    {
        return $this->active;
    }

    /**
     * @return int|null
     */
    public function getSettings(): ?int
    {
        return $this->settings;
    }

    /**
     * @return int|null
     */
    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    /**
     * @return bool|null
     */
    public function getCollection(): ?bool
    {
        return $this->collection;
    }

    /**
     * @return Plugin
     */
    public function setInfo(): Plugin
    {
        $this->info = new PluginInfo($this->url);
        foreach (get_object_vars($this->info) as $property => $value) {
            if (property_exists($this, $property) && !isset($this->{$property})) {
                $this->{$property} = $value;
            }
        }

        return $this;
    }

    /**
     * @return PluginInfo
     */
    public function getInfo(): PluginInfo
    {
        return $this->info;
    }
}
