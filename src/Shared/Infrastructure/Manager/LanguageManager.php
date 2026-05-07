<?php

namespace Ivy\Infrastructure\Manager;

use Ivy\Domain\Model\InfoModel;
use Ivy\Shared\Core\Language;

class LanguageManager
{
    public static function init(): void
    {
        Language::setDefaultLang(substr(InfoModel::stashGet('language')->value, 0, 2));
    }
}
