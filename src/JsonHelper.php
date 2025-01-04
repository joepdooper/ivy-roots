<?php

namespace Ivy;

class JsonHelper
{
    public static function parse(string $filePath): ?array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $content = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $content;
    }
}
