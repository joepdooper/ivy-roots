<?php

namespace Ivy\Core;

use Ivy\Manager\TemplateManager;

class Language
{
    protected static string $defaultLang = 'en';
    protected static array $translations = [];
    protected static array $loadedFiles = [];

    public static function load($lang = null): void
    {
        if (!$lang) {
            $lang = self::$defaultLang;
        }

        self::$defaultLang = $lang;
    }

    public static function translate($key, $variables = [])
    {
        $keys = explode('.', $key);
        $firstKey = array_shift($keys);

        if (!isset(self::$loadedFiles[$firstKey])) {
            self::loadFile($firstKey);
        }

        if(self::$translations[$firstKey]){
            $translation = self::getNestedTranslation(self::$translations[$firstKey], $keys);
        } else {
            $secondKey = array_shift($keys);

            if (!isset(self::$loadedFiles[$firstKey.'_'.$secondKey])) {
                self::loadPluginFile($firstKey, $secondKey);
            }

            if(self::$translations[$firstKey.'_'.$secondKey]){
                $translation = self::getNestedTranslation(self::$translations[$firstKey.'_'.$secondKey], $keys);
            }
        }

        if (!empty($translation) && is_string($translation) && !empty($variables)) {
            foreach ($variables as $placeholder => $value) {
                $translation = str_replace(":{$placeholder}", $value, $translation);
            }
        }

        return $translation ?? ($key ?? '…');
    }

    private static function loadFile($firstKey): void
    {
        $langPath = Path::get('PROJECT_PATH') . 'language' . DIRECTORY_SEPARATOR . self::$defaultLang . DIRECTORY_SEPARATOR . $firstKey . '.php';

        if (file_exists($langPath)) {
            self::$translations[$firstKey] = include $langPath;
            self::$loadedFiles[$firstKey] = true;
        } else {
            self::$translations[$firstKey] = [];
            self::$loadedFiles[$firstKey] = false;
        }
    }

    private static function loadPluginFile($firstKey, $secondKey): void
    {
        $langPath = TemplateManager::file(Path::get('PLUGINS_FOLDER') . $firstKey . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . self::$defaultLang . DIRECTORY_SEPARATOR . $secondKey . '.php');

        if (file_exists($langPath)) {
            self::$translations[$firstKey.'_'.$secondKey] = include $langPath;
            self::$loadedFiles[$firstKey.'_'.$secondKey] = true;
        } else {
            self::$translations[$firstKey.'_'.$secondKey] = [];
            self::$loadedFiles[$firstKey.'_'.$secondKey] = false;
        }
    }

    private static function getNestedTranslation(array $translations, array $keys)
    {
        foreach ($keys as $k) {
            if (!is_array($translations) || !isset($translations[$k])) {
                return null;
            }
            $translations = $translations[$k];
        }
        return $translations;
    }

    public static function setDefaultLang($lang): void
    {
        self::$defaultLang = $lang;
    }
}
