<?php

namespace Ivy\Core;

use Ivy\Manager\TemplateManager;

class Language
{
    protected static string $defaultLang = 'en';

    /** @var mixed[] */
    protected static array $translations = [];

    /** @var mixed[] */
    protected static array $loadedFiles = [];

    public static function load(?string $lang = null): void
    {
        if (! $lang) {
            $lang = self::$defaultLang;
        }

        self::$defaultLang = $lang;
    }

    /**
     * @param array<string, string>|null $variables
     */
    public static function translate(string $key, ?array $variables = []): string
    {
        $translation = $key;

        $keys = explode('.', $key);
        $firstKey = array_shift($keys);

        if (! isset(self::$loadedFiles[$firstKey])) {
            self::loadFile($firstKey);
        }

        if (self::$translations[$firstKey]) {
            $translation = self::getNestedTranslation(self::$translations[$firstKey], $keys);
        } else {
            $secondKey = array_shift($keys);

            if($secondKey){
                if (! isset(self::$loadedFiles[$firstKey.'_'.$secondKey])) {
                    self::loadPluginFile($firstKey, $secondKey);
                }
                if (self::$translations[$firstKey.'_'.$secondKey]) {
                    $translation = self::getNestedTranslation(self::$translations[$firstKey.'_'.$secondKey], $keys);
                    d($translation);die;
                }
            }
        }

        if (! empty($translation) && is_string($translation) && ! empty($variables)) {
            foreach ($variables as $placeholder => $value) {
                $translation = str_replace(":{$placeholder}", $value, $translation);
            }
        }

        return $translation;
    }

    private static function loadFile(string $firstKey): void
    {
        $langPath = Path::get('PROJECT_PATH').'language'.DIRECTORY_SEPARATOR.self::$defaultLang.DIRECTORY_SEPARATOR.$firstKey.'.php';

        if (file_exists($langPath)) {
            self::$translations[$firstKey] = include $langPath;
            self::$loadedFiles[$firstKey] = true;
        } else {
            self::$translations[$firstKey] = [];
            self::$loadedFiles[$firstKey] = false;
        }
    }

    private static function loadPluginFile(string $firstKey, string $secondKey): void
    {
        $langPath = TemplateManager::file(Path::get('PLUGINS_FOLDER').$firstKey.DIRECTORY_SEPARATOR.'language'.DIRECTORY_SEPARATOR.self::$defaultLang.DIRECTORY_SEPARATOR.$secondKey.'.php');

        if (file_exists($langPath)) {
            self::$translations[$firstKey.'_'.$secondKey] = include $langPath;
            self::$loadedFiles[$firstKey.'_'.$secondKey] = true;
        } else {
            self::$translations[$firstKey.'_'.$secondKey] = [];
            self::$loadedFiles[$firstKey.'_'.$secondKey] = false;
        }
    }

    /**
     * @param string[] $translations
     * @param string[] $keys
     * @return string
     */
    private static function getNestedTranslation(array $translations, array $keys): string
    {
        foreach ($keys as $k) {
            if (! is_array($translations) || ! isset($translations[$k])) {
                return $translations;
            }
            $translations = $translations[$k];
        }

        return $translations;
    }

    public static function setDefaultLang(string $lang): void
    {
        self::$defaultLang = $lang;
    }
}
