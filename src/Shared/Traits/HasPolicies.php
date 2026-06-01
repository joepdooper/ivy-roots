<?php

namespace Ivy\Shared\Traits;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Ivy\User\Application\Service\AuthService;
use Ivy\User\Domain\Exception\AuthorizationException;
use ReflectionClass;
use ReflectionException;

trait HasPolicies
{
    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws AuthorizationException
     */
    protected function policyInstance(): object
    {
        $modelClass = get_class($this);
        $modelName = new ReflectionClass($modelClass)->getShortName();

        $namespace = str_replace(
            'Entity',
            'Policy',
            new ReflectionClass($modelClass)->getNamespaceName()
        );

        $policyClassName = $modelName . 'Policy';
        $policyClass = "{$namespace}\\{$policyClassName}";

        if (!class_exists($policyClass)) {
            throw new AuthorizationException("Policy not found: {$policyClass}");
        }

        return new $policyClass(Container::getInstance()->make(AuthService::class));
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws AuthorizationException
     */
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
        if (!$this->policy($action)) {
            throw new AuthorizationException(
                "Not authorized to perform [{$action}] on " . static::class
            );
        }
    }
}
