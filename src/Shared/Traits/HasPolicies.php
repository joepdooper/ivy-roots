<?php

namespace Ivy\Shared\Traits;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Ivy\Domain\Exception\AuthorizationException;
use Ivy\Application\Service\AuthApplicationService;
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
            'Model',
            'Policy',
            new ReflectionClass($modelClass)->getNamespaceName()
        );

        $policyClassName = str_replace('Model', 'Policy', $modelName);
        $policyClass = "{$namespace}\\{$policyClassName}";

        if (!class_exists($policyClass)) {
            throw new AuthorizationException("Policy not found: {$policyClass}");
        }
        return new $policyClass(Container::getInstance()->make(AuthApplicationService::class)->auth());
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
        try {
            if (!$this->policy($action)) {
                throw new AuthorizationException(
                    "Not authorized to perform [{$action}] on " . static::class
                );
            }
        } catch (BindingResolutionException|AuthorizationException|ReflectionException $e) {

        }
    }
}
