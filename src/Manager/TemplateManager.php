<?php

namespace Ivy\Manager;

class TemplateManager
{
    private static string $file;

    public static function file(string $filename): ?string
    {
        $paths = [_TEMPLATE_SUB, _TEMPLATE_BASE];
        foreach ($paths as $path) {
            $fullPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        return $filename;
    }
}

