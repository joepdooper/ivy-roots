<?php

namespace Ivy\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Helper\PluginInfo;
use Ivy\Trait\HasPolicies;

class Plugin extends Model
{
    use HasPolicies;
    
    protected $fillable = [
        'parent_id',
        'name',
        'url',
        'namespace',
        'version',
        'description',
        'type',
        'active',
    ];

    protected PluginInfo $info;

    public function setInfo(): Plugin
    {
        $this->url ??= self::whereKey($this->id)->value('url');

        if (empty($this->url)) {
            throw new \Exception('Plugin URL is missing, cannot initialize PluginInfo.');
        }

        $this->info = new PluginInfo($this->url);

        foreach ($this->getFillable() as $property) {

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