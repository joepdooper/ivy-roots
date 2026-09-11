<?php

namespace Ivy\Shared\Infrastructure\Composer;

use Exception;

final class PackagistClient
{
    public static function getCatalog(string $type, int $page = 1, int $per_page = 25): array
    {
        $listUrl = "https://packagist.org/packages/list.json?type=" . rawurlencode($type) . "&page=" . $page . "&per_page=" . $per_page;
        $namesJson = file_get_contents($listUrl);
        if ($namesJson === false) {
            throw new \RuntimeException("Failed to fetch Packagist list: " . $listUrl);
        }

        $namesData = json_decode($namesJson, true);
        $packageNames = $namesData['packageNames'] ?? [];

        $p = [];

        foreach ($packageNames as $fullName) {
            [$vendor, $package] = explode('/', $fullName, 2);

            $url = "https://repo.packagist.org/p2/".$vendor."/".$package.".json";
            $json = file_get_contents($url);
            if ($json === false) {
                continue;
            }

            $meta = json_decode($json, true);
            if (!$meta || !isset($meta['packages'][$fullName])) {
                continue;
            }

            $versions = $meta['packages'][$fullName];
            if (!is_array($versions)) {
                continue;
            }

            $v = [];

            foreach ($versions as $version) {
                $v[] = $version['version'];
            }

            $p[] = [
                'package' => $fullName,
                'versions' => $v,
                'description' => $meta['packages'][$fullName][0]['description'] ?? null,
                'extra' => $meta['packages'][$fullName][0]['extra'] ?? null,
            ];
        }

        return $p;
    }


    /**
     * @throws Exception
     */
    public static function getPackageData(string $package): array
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
                    'User-Agent: Ivy/1.0'
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

        if (!(isset($json['packages'][$package][0]['type']) && in_array($json['packages'][$package][0]['type'], ['ivy-plugin', 'ivy-template']))) {
            throw new Exception("Package $package is not for ivy");
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
            'url' => $name,
//            'license' => isset($pkg0['license']) && is_array($pkg0['license']) ? ($pkg0['license'][0] ?? null) : null,
//            'homepage' => $pkg0['homepage'] ?? null,
//            'keywords' => $pkg0['keywords'] ?? [],
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