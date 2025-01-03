<?php

namespace Ivy;

use HTMLPurifier;
use HTMLPurifier_Config;

class PluginInfo
{
    public ?string $name;
    public ?string $version;
    public ?string $description;
    public ?string $url;
    public ?string $type;
    public ?int $settings;
    public ?bool $collection;
    public ?array $database;
    public ?array $dependencies;

    public function __construct(string $url)
    {
        $this->url = $url;

        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);

        $dir = _PUBLIC_PATH . _PLUGIN_PATH . $purifier->purify($url) . DIRECTORY_SEPARATOR;
        $info_path = realpath($dir . 'info.json');

        if ($info_path) {
            if (str_starts_with($info_path, $dir)) {
                $data = json_decode(file_get_contents($info_path), true);
                $this->name = isset($data['name']) ? $purifier->purify($data['name']) : null;
                $this->version = isset($data['version']) ? $purifier->purify($data['version']) : null;
                $this->description = isset($data['description']) ? $purifier->purify($data['description']) : null;
                $this->type = isset($data['type']) ? $purifier->purify($data['type']) : null;
                $this->settings = isset($data['settings']) ? (int)$data['settings'] : 0;
                $this->database = $data['database'] ?? null;
                $this->dependencies = $data['dependencies'] ?? null;
                $this->collection = $data['collection'] ?? null;
            }
        }
    }
}
