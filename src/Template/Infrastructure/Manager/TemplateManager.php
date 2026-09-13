<?php

namespace Ivy\Template\Infrastructure\Manager;

use Ivy\Shared\Core\Path;
use Ivy\Template\Application\Contracts\TemplateInterface;
use Ivy\Template\Domain\Entity\Template;
use Ivy\User\Application\Service\AuthService;

class TemplateManager
{
    private static ?Template $baseTemplate = null;

    private static ?Template $subTemplate = null;

    private static bool $initialized = false;

    /** @var array<string, string> */
    private static array $cache = [];

    /** @var array<string, string> */
    private static array $registered = [];


    public static function getTemplateBase(): ?string
    {
        return self::$baseTemplate->url ? basename(Path::get('TEMPLATES_PATH') . self::$baseTemplate->url) : null;
    }

    public static function getTemplateSub(): ?string
    {
        return self::$subTemplate->url ? basename(Path::get('TEMPLATES_PATH') . self::$subTemplate->url) : null;
    }

    public static function init(bool $forceRefresh = false): void
    {
        if (self::$initialized && ! $forceRefresh) {
            return;
        }

        self::$baseTemplate = Template::where('type', 'base')->first();
        self::$subTemplate = Template::where('type', 'sub')->first();

        if ($forceRefresh) {
            self::$cache = [];
            self::$registered = [];
        }

        self::$initialized = true;
    }

    public static function register(AuthService $auth): void
    {
        foreach ([self::$baseTemplate, self::$subTemplate] as $template) {
            if (! $template || ! $template->interface) {
                continue;
            }

            $class = $template->interface;

            if (isset(self::$registered[$class])) {
                continue;
            }

            if (! class_exists($class)) {
                throw new \RuntimeException(
                    "Template class [$class] does not exist."
                );
            }

            if (! is_a($class, TemplateInterface::class, true)) {
                throw new \RuntimeException(
                    "Template class [$class] must implement ".TemplateInterface::class.'.'
                );
            }

            /** @var TemplateInterface $template */
            $template = new $class();

            $template->register($auth);

            self::$registered[$class] = true;
        }
    }

    public static function file(string $filename): string
    {
        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }

        $paths = [Path::get('TEMPLATES_PATH') . self::$subTemplate->url, Path::get('TEMPLATES_PATH') . self::$baseTemplate->url];

        foreach ($paths as $path) {
            if (! $path) {
                continue;
            }

            $fullPath = $path.DIRECTORY_SEPARATOR.$filename;

            if (file_exists($fullPath)) {
                return self::$cache[$filename] = $fullPath;
            }
        }

        $projectPath = Path::get('PROJECT_PATH').$filename;

        if (file_exists($projectPath)) {
            return self::$cache[$filename] = $projectPath;
        }

        return self::$cache[$filename] = $filename;
    }

    public static function asset(string $filename): string
    {
        $file = self::file($filename);

        $templatesPath = Path::get('TEMPLATES_PATH');

        if (str_starts_with($file, $templatesPath)) {
            return '/' . Path::get('TEMPLATES_FOLDER') . ltrim(substr($file, strlen($templatesPath)), '/');
        }

        return ltrim($filename, '/');
    }

    public static function require(string $filename): void
    {
        require self::file($filename);
    }
}
