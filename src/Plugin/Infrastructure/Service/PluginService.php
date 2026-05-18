<?php

namespace Ivy\Plugin\Infrastructure\Service;

use Exception;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Shared\Core\Path;
use Symfony\Component\HttpFoundation\File\File;

class PluginService
{
    /**
     * @return array<string, mixed>|null
     *
     * @throws Exception
     */
    public static function parseJson(string $path): ?array
    {
        $file = self::getRealPath(Path::get('PLUGINS_PATH').$path);

        if(!$file) {
            throw new Exception('No JSON file found: '.$path);
        }

        $content = file_get_contents($file);

        if(!$content) {
            throw new Exception('JSON file could not be read: '.$path);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (! $decoded) {
            throw new Exception('Invalid JSON: '.$path);
        }

        return $decoded;
    }

    /**
     * @throws Exception
     */
    public static function getRealPath(string $path): ?string
    {
        $file = new File($path);
        $file = $file->getRealPath();

        if ($file === false || ! str_starts_with($file, Path::get('PLUGINS_PATH'))) {
            throw new Exception('Invalid file path: '.$path);
        }

        return $file;
    }

    public static function getRelativePath(string $path): ?string
    {
        return str_replace(Path::get('PLUGINS_PATH'), '', $path);
    }

    /**
     * @throws Exception
     */
    public static function getCollectionDirectory(string $pluginUrl): ?string
    {
        if (! is_dir(Path::get('PLUGINS_PATH').basename($pluginUrl).DIRECTORY_SEPARATOR.'collection')) {
            throw new Exception('Invalid collection directory');
        }

        return Path::get('PLUGINS_PATH').basename($pluginUrl).DIRECTORY_SEPARATOR.'collection'.DIRECTORY_SEPARATOR;
    }

    /**
     * @param list<string> $dependencies
     *
     * @return array<int<0, max>, string>
     */
    public static function getMissingDependencies(array $dependencies): array
    {
        return array_filter($dependencies, function ($dependency) {
            return ! Plugin::where('name', $dependency)->pluck('id');
        });
    }
}
