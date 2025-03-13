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
    protected ?string $description = null;
    protected ?string $type = null;
    protected ?int $active;
    protected ?int $settings = null;
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
        if (!isset($this->url) && $this->id) {
            $plugin = $this->select(['url'])->where('id', $this->id)->fetchOne();
            if ($plugin) {
                $this->url = $plugin->url;
            }
        }

        if (empty($this->url)) {
            Message::add('Plugin URL is missing, cannot initialize PluginInfo.');
        }

        $this->info = new PluginInfo($this->url);

        foreach ($this->columns as $property) {
            $getter = 'get' . ucfirst($property);
            if (method_exists($this->info, $getter)) {
                $value = $this->info->$getter();
                if ($value !== null) {
                    $this->$property = $value;
                }
            }
        }

        return $this;
    }


    public function getInfo(): PluginInfo
    {
        return $this->info;
    }
}
