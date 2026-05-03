<?php

namespace Ivy\Trait;

use Delight\Auth\Auth;
use Illuminate\Container\Container;
use Ivy\Exceptions\AuthorizationException;
use Ivy\Service\AuthService;
use Symfony\Component\HttpFoundation\Request;

trait HasPolicies
{
    protected function policyInstance(): object
    {
        $modelClass = get_class($this);
        $modelName = (new \ReflectionClass($modelClass))->getShortName();

        $namespace = str_replace(
            'Model',
            'Policy',
            (new \ReflectionClass($modelClass))->getNamespaceName()
        );

        $policyClass = "{$namespace}\\{$modelName}Policy";

        if (!class_exists($policyClass)) {
            throw new AuthorizationException("Policy not found: {$policyClass}");
        }
        return new $policyClass(Container::getInstance()->make(AuthService::class)->auth());
    }

    public function policy(string $action): bool
    {
        $policy = $this->policyInstance();

        if (!method_exists($policy, $action)) {
            return false;
        }

        return (bool) $policy->$action($this);
    }

    public function authorize(string $action): void
    {
        if (! $this->policy($action)) {
            throw new AuthorizationException(
                "Not authorized to perform [{$action}] on " . static::class
            );
        }
    }
}