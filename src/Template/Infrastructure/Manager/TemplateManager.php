<?php

namespace Ivy\Infrastructure\Manager;

use Ivy\Domain\Model\TemplateModel;
use Ivy\Shared\Core\Path;

class TemplateManager
{
    private static ?string $templateBase = null;
    private static ?string $templateSub = null;
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

    public static function init(bool $forceRefresh = false): void
    {
        if (self::$initialized && ! $forceRefresh) {
            return;
        }

        $templateBase = TemplateModel::where('type', 'base')
            ->value('value');

        $templateSub = TemplateModel::where('type', 'sub')
            ->value('value');

        self::$templateBase = Path::get('TEMPLATES_PATH') . $templateBase . DIRECTORY_SEPARATOR;
        self::$templateSub = Path::get('TEMPLATES_PATH') . $templateSub . DIRECTORY_SEPARATOR;

        if ($forceRefresh) {
            self::$cache = [];
        }

        self::$initialized = true;
    }

    public static function file(string $filename): string
    {
        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }

        $paths = [self::$templateSub, self::$templateBase];

        foreach ($paths as $path) {
            if (! $path) {
                continue;
            }

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

    public static function require(string $filename):void
    {
        require self::file($filename);
    }
}
