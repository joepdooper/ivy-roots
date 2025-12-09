<?php

namespace Ivy\Manager;

use Ivy\Config\Environment;
use Ivy\Core\Path;

class TemplateManager
{
    /** @var string|null */
    private static ?string $templateBase = null;

    /** @var string|null */
    private static ?string $templateSub = null;

    /** @var bool */
    private static bool $initialized = false;

    /** @var array<string, string> */
    private static array $cache = [];

    public static function getTemplateBase(): ?string
    {
        return self::$templateBase ? basename(self::$templateBase) : null;
    }

    public static function getTemplateSub(): ?string
    {
        return self::$templateSub ? basename(self::$templateSub) : null;
    }

    /**
     * Initialize template paths.
     *
     * @param bool $forceRefresh If true, reloads template paths from DB
     */
    public static function init(bool $forceRefresh = false): void
    {
        if (self::$initialized && !$forceRefresh) {
            return;
        }

        $sql = "SELECT `value` FROM `templates` WHERE `type` = :type";
        $db = DatabaseManager::connection();

        $templateBase = $db->selectValue($sql, ['base']);
        $templateSub = $db->selectValue($sql, ['sub']);

        self::$templateBase = Path::get('TEMPLATES_PATH') . $templateBase . DIRECTORY_SEPARATOR;
        self::$templateSub = Path::get('TEMPLATES_PATH') . $templateSub . DIRECTORY_SEPARATOR;

        if ($forceRefresh) {
            self::$cache = [];
        }

        self::$initialized = true;
    }

    /**
     * Get the full path of a template file
     *
     * @param string $filename
     * @return string
     */
    public static function file(string $filename): string
    {
        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }

        $paths = [self::$templateSub, self::$templateBase];
        foreach ($paths as $path) {

            if (!$path) continue;

            $fullPath = $path . $filename;

            if (file_exists($fullPath)) {
                return self::$cache[$filename] = $fullPath;
            }
        }

        $projectPath = Path::get('PROJECT_PATH') . $filename;
        if (file_exists($projectPath)) {
            return self::$cache[$filename] = $projectPath;
        }

        return self::$cache[$filename] = $filename;
    }
}
