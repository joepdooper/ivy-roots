<?php

namespace Ivy\Shared\Infrastructure\Composer;

use Exception;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Shared\Presentation\Form\FilterPackagistRequestForm;
use Ivy\Template\Domain\Entity\Template;
use Symfony\Component\HttpFoundation\Request;

final class PackagistClient
{

    public static function getCatalog(string $type, ?Request $request = null): array
    {
        $validated = $request ? (new FilterPackagistRequestForm)->validate($request->query->all()) : null;

        $modelsByPackage = Plugin::all()
            ->concat(Template::all())
            ->keyBy('package');

        $query['type'] = $type;

        if(isset($validated->data['page']) && isset($validated->data['per_page'])){
            $query = [
                'page' => max(1, $validated->data['page']),
                'per_page' => max(1, $validated->data['per_page']),
            ];
        }

        if (isset($validated->data['search']) && trim($validated->data['search']) !== '') {
            $query['q'] = trim($validated->data['search']);
        }

        $listUrl = 'https://packagist.org/search.json?' . http_build_query($query);

        $namesJson = file_get_contents($listUrl);

        if ($namesJson === false) {
            throw new \RuntimeException("Failed to fetch Packagist list: " . $listUrl);
        }

        $namesData = json_decode($namesJson, true);
        $packages = $namesData['results'] ?? [];

        $p = [];

        foreach ($packages as $package) {

            $packageName = $package['name'];
            [$vendor, $package] = explode('/', $packageName, 2);

            $url = "https://repo.packagist.org/p2/".$vendor."/".$package.".json";
            $json = file_get_contents($url);
            if ($json === false) {
                continue;
            }

            $meta = json_decode($json, true);
            if (!$meta || !isset($meta['packages'][$packageName])) {
                continue;
            }

            $versions = $meta['packages'][$packageName];
            if (!is_array($versions)) {
                continue;
            }

            $v = [];

            foreach ($versions as $version) {
                $v[] = $version['version'];
            }

            $p[] = [
                'package' => $packageName,
                'versions' => $v,
                'description' => $meta['packages'][$packageName][0]['description'] ?? null,
                'extra' => $meta['packages'][$packageName][0]['extra'] ?? null,
                'model' => $modelsByPackage->get($packageName),
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

    public static function updateLatestVersions($models): void
    {
        foreach ($models as $model) {
            $package = $model->package ?? '';
            if (!preg_match('~^([^/]+)/([^/]+)$~', $package, $parts)) continue;

            $url = "https://repo.packagist.org/p2/{$parts[1]}/{$parts[2]}.json";
            $data = @json_decode(file_get_contents($url), true);
            $installed = ($model->version ?? '')
                . (!empty($model->version_channel) ? '-'.$model->version_channel : '');

            foreach ($data['packages'][$package] ?? [] as $item) {
                $v = self::splitComposerVersion($item['version'] ?? null);
                if (!$v['version']) continue;

                $candidate = $v['version'] . ($v['channel'] ? '-'.$v['channel'] : '');
                if ($installed && version_compare($candidate, $installed, '<=')) continue;

                $model->latest_version = $v['version'];
                $model->latest_version_channel = $v['channel'];
                $model->save();
                break;
            }
        }
    }
}