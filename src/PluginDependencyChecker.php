<?php

namespace Ivy;

class PluginDependencyChecker
{
    public static function getMissingDependencies(?array $dependencies = []): array
    {
        return array_filter($dependencies ?? [], function ($dependency) {
            return !DB::getConnection()->selectValue('SELECT id FROM plugin WHERE name = :name', ['name' => $dependency]);
        });
    }
}