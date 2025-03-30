<?php
namespace Ivy\Manager;

use Ivy\Model\Setting;
use Ivy\Language;

class LanguageManager
{
    public static function init(): void
    {
        Setting::stash()->keyByColumn('name');
        Language::setDefaultLang(substr(Setting::getStashItem('language')->value, 0, 2));
    }
}