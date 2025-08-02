<?php
namespace Ivy\Manager;

use Ivy\Model\Info;
use Ivy\Model\Setting;
use Ivy\Core\Language;

class LanguageManager
{
    public static function init(): void
    {
        Language::setDefaultLang(substr(Info::getStashItem('language')->value, 0, 2));
    }
}