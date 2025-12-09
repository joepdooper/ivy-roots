<?php
namespace Ivy\Trait;

trait HasPolicies
{
    public function policy(string $action): bool
    {
        $modelClass = get_class($this);
        $modelName = (new \ReflectionClass($modelClass))->getShortName();
        $namespace = str_replace('Model', 'Policy', (new \ReflectionClass($modelClass))->getNamespaceName());
        $policyClass = "{$namespace}\\{$modelName}Policy";

        if (!class_exists($policyClass) || !method_exists($policyClass, $action)) {
            return false;
        }

        return (bool) $policyClass::$action($this);
    }

    public function authorize(string $action): void
    {
        if (!$this->policy($action)) {
            throw new \Ivy\Exceptions\AuthorizationException(
                "Not authorized to perform [{$action}] on " . static::class
            );
        }
    }
}
