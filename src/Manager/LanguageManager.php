<?php

namespace Ivy\Manager;

use Ivy\Core\Language;
use Ivy\Model\Info;

class LanguageManager
{
    public static function init(): void
    {
        Language::setDefaultLang(substr(Info::stashGet('language')->value, 0, 2));
    }
}
