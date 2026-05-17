<?php

namespace Ivy\Shared\Infrastructure\Manager;

use Ivy\Setting\Domain\Entity\Info;
use Ivy\Shared\Core\Language;

class LanguageManager
{
    public static function init(): void
    {
        Language::setDefaultLang(substr(Info::stashGet('language')->value, 0, 2));
    }
}
