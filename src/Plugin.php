<?php

namespace Ivy;

class Plugin extends Model
{
    protected string $table = 'plugin';
    protected string $path = 'admin/plugin';
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
}
