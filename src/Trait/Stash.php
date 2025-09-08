<?php
namespace Ivy\Trait;

use Ivy\Manager\SessionManager;

trait Stash
{
    private static ?array $currentData = null;

    /**
     * Initialize stashing for the calling class.
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function stash(): static
    {
        $type = static::class;

        if (!method_exists($type, 'fetchAll')) {
            throw new \InvalidArgumentException("Class '$type' does not have a fetchAll method.");
        }

        $instance = new $type();
        self::$currentData = $instance->fetchAll();

        return $instance;
    }

    /**
     * Stash objects by a specific column key.
     *
     * @param string $column The column name to use as the key.
     * @return void
     * @throws \InvalidArgumentException
     */
    public function keyByColumn(string $column): void
    {
        if (self::$currentData === null) {
            throw new \RuntimeException("No data available to stash. Call stash() first.");
        }

        $getter = 'get' . str_replace('_', '', ucwords($column, '_'));

        $stashData = [];
        foreach (self::$currentData as $instance) {
            if (method_exists($instance, $getter)) {
                $key = strtolower(str_replace(' ', '_', $instance->$getter()));
            } elseif (property_exists($instance, $column)) {
                $key = strtolower(str_replace(' ', '_', $instance->$column));
            } else {
                throw new \InvalidArgumentException("Column '$column' is not valid for this model.");
            }

            $stashData[$key] = $instance;
        }

        SessionManager::set('stash_' . static::class, $stashData);
        self::$currentData = null;
    }

    /**
     * Retrieve stashed objects for the calling class.
     *
     * @return array|null
     */
    public static function getStash($key = null): ?array
    {
        if($key){
            return SessionManager::get('stash_' . static::class, null)[$key];
        }
        return SessionManager::get('stash_' . static::class, null);
    }

    /**
     * Retrieve a specific item from the stash.
     *
     * @param string $key
     * @return mixed|null
     */
    public static function getStashItem(string $key)
    {
        $stash = SessionManager::get('stash_' . static::class, []);
        return $stash[$key] ?? null;
    }
}

