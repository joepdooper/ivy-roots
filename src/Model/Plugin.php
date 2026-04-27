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
        'interface',
        'version',
        'description',
        'type',
        'active',
        'url',
    ];

    public PluginInfo $info;
}