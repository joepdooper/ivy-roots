<?php

namespace Ivy;

class JsonHelper
{
    public static function parse(string $infoJsonPath): ?array
    {
        $infoJsonFile = new \Symfony\Component\HttpFoundation\File\File($infoJsonPath);
        $infoJsonFile = $infoJsonFile->getRealPath();

        if ($infoJsonFile === false || !str_starts_with($infoJsonFile, Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH'))) {
            throw new \Exception('Invalid file path: ' . $infoJsonPath);
        }

        $content = json_decode(file_get_contents($infoJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!$content) {
            throw new \Exception('Invalid JSON: ' . $infoJsonPath);
        }

        return $content;
    }
}
