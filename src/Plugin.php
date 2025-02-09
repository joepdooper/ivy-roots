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

    public string $name;
    protected string $url;
    public string $version;
    public ?string $description;
    public ?string $type;
    public ?int $active;
    public ?int $settings;
    public ?int $parent_id;

    public ?bool $collection = false;
    private PluginInfo $info;

    public function setUrl(string $url): Plugin
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setParentId(int $id): Plugin
    {
        $this->parent_id = $id;
        return $this;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

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

    public function getInfo(): PluginInfo
    {
        return $this->info;
    }
}
