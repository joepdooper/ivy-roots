<?php

namespace Ivy\Template\Infrastructure\Service;

use Ivy\Template\Domain\Entity\Template;

class TemplateService
{
    /**
     * @param  list<string>  $dependencies
     * @return array<int<0, max>, string>
     */
    public static function getMissingDependencies(array $dependencies): array
    {
        $existing = Template::whereIn('name', $dependencies)->pluck('name')->all()->toArray();

        return array_values(array_diff($dependencies, $existing));
    }
}
