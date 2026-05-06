<?php

namespace Ivy\Infrastructure\Manager;

use Ivy\Shared\Core\Language;
use Ivy\Domain\Entity\InfoEntity;

class LanguageManager
{
    public static function init(): void
    {
        Language::setDefaultLang(substr(InfoEntity::stashGet('language')->value, 0, 2));
    }
}
