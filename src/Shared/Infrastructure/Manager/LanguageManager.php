<?php

namespace Ivy\Shared\Infrastructure\Manager;

use Ivy\Plugin\Domain\Entity\InfoModel;
use Ivy\Shared\Core\Language;

class LanguageManager
{
    public static function init(): void
    {
        Language::setDefaultLang(substr(InfoModel::stashGet('language')->value, 0, 2));
    }
}
