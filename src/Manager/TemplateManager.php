<?php

namespace Ivy\Manager;

use Ivy\Path;

class TemplateManager
{
    private static string $file;

    public static function init(): void
    {
        $sql = "SELECT `value` FROM `template` WHERE `type` = :type";
        define('_TEMPLATE_BASE', Path::get('TEMPLATES_PATH') . DatabaseManager::connection()->selectValue($sql, ['base']) . DIRECTORY_SEPARATOR);
        define('_TEMPLATE_SUB', Path::get('TEMPLATES_PATH') . DatabaseManager::connection()->selectValue($sql, ['sub']) . DIRECTORY_SEPARATOR);
    }

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

