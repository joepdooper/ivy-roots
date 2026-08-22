<?php

namespace Ivy\Plugin\Infrastructure\Service;

use Exception;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfo;
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

        if (! $file) {
            throw new Exception('No JSON file found: '.$path);
        }

        $content = file_get_contents($file);

        if (! $content) {
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
     * @param  list<string>  $dependencies
     * @return array<int<0, max>, string>
     */
    public static function getMissingDependencies(array $dependencies): array
    {
        $existing = Plugin::whereIn('name', $dependencies)->pluck('name')->all()->toArray();

        return array_values(array_diff($dependencies, $existing));
    }

    /**
     * @throws Exception
     */
    public static function queuePackageMetadata(string $package): array
    {
        $parts = explode('/', $package, 2);
        if (count($parts) !== 2) {
            throw new Exception("Package must be in form vendor/name");
        }

        [$vendor, $name] = $parts;

        $url = "https://repo.packagist.org/p2/".$vendor."/".$name.".json";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => [
                    'Accept: application/json',
                    'User-Agent: IvySprout/1.0'
                ],
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            throw new Exception("Failed to fetch metadata from Packagist for $package");
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new Exception("Invalid JSON metadata returned for $package");
        }

        if (!(isset($json['packages'][$package][0]['type']) && $json['packages'][$package][0]['type'] === 'ivy-plugin')) {
            throw new Exception("Package $package is not a proper ivy-plugin");
        }

        $pkg0 = $json['packages'][$package][0];
        $v = self::splitComposerVersion($pkg0['version'] ?? null);

        return [
            'name' => $pkg0['extra']['ivy']['name'] ?? $name,
            'package' => $package,
            'interface' => $pkg0['extra']['ivy']['interface'] ?? null,
            'version' => $v['version'],
            'version_channel' => $v['channel'],
            'description' => $pkg0['description'] ?? null,
            'type' => $pkg0['extra']['ivy']['type'] ?? null,
            'license' => isset($pkg0['license']) && is_array($pkg0['license']) ? ($pkg0['license'][0] ?? null) : null,
            'homepage' => $pkg0['homepage'] ?? null,
            'keywords' => $pkg0['keywords'] ?? [],
            'url' => $name,
        ];
    }

    /**
     * @return array{string, string}
     */
    public static function splitComposerVersion(?string $rawVersion): array
    {
        $rawVersion = $rawVersion ?? '';
        $rawVersion = trim($rawVersion);

        if ($rawVersion === '') {
            return ['version' => null, 'channel' => null];
        }

        $rawVersion = ltrim($rawVersion, 'v');

        $m = [];
        if (!preg_match('/^(\d+\.\d+\.\d+)(?:-(.+))?$/', $rawVersion, $m)) {
            return ['version' => null, 'channel' => null];
        }

        $version = $m[1];
        $channel = $m[2] ?? null;

        return ['version' => $version, 'channel' => $channel];
    }
}
