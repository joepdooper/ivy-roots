<?php

namespace Ivy\Helper;

use Ivy\Manager\DatabaseManager;
use Ivy\Path;

class PluginHelper
{
    public static function parseJson(string $path): ?array
    {
        $file = self::getRealPath(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $path);
        $content = json_decode(file_get_contents($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!$content) {
            throw new \Exception('Invalid JSON: ' . $path);
        }

        return $content;
    }

    public static function getRealPath(string $path): ?string
    {
        $file = new \Symfony\Component\HttpFoundation\File\File($path);
        $file = $file->getRealPath();

        if ($file === false || !str_starts_with($file, Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH'))) {
            throw new \Exception('Invalid file path: ' . $path);
        }

        return $file;
    }

    public static function getRelativePath(string $path): ?string
    {
        return str_replace(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH'),'', $path);
    }

    public static function getCollectionDirectory(string $pluginUrl): ?string
    {
        if(!is_dir(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . basename($pluginUrl) . DIRECTORY_SEPARATOR . 'collection')){
            throw new \Exception('Invalid collection directory');
        }

        return Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . basename($pluginUrl) . DIRECTORY_SEPARATOR . 'collection' . DIRECTORY_SEPARATOR;
    }

    public static function getMissingDependencies(?array $dependencies = []): array
    {
        return array_filter($dependencies ?? [], function ($dependency) {
            return !DatabaseManager::connection()->selectValue('SELECT id FROM plugin WHERE name = :name', ['name' => $dependency]);
        });
    }
}
