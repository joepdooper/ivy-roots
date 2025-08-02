<?php

namespace Ivy\Manager;

use Ivy\Core\Path;

class TemplateManager
{
    private static string $file;

    public static function init(): void
    {
        $sql = "SELECT `value` FROM `templates` WHERE `type` = :type";

        $templateBase = DatabaseManager::connection()->selectValue($sql, ['base']);
        $templateSub = DatabaseManager::connection()->selectValue($sql, ['sub']);

        define('_TEMPLATE_BASE', Path::get('TEMPLATES_PATH') . $templateBase . DIRECTORY_SEPARATOR);
        define('_TEMPLATE_SUB', Path::get('TEMPLATES_PATH') . $templateSub . DIRECTORY_SEPARATOR);
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

