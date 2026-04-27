<?php

namespace Ivy\Manager;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseManager
{
    private Capsule $capsule;

    public function __construct()
    {
        $this->capsule = new Capsule;
    }

    public function addConnection(array $config, string $name = 'default'): void
    {
        $this->capsule->addConnection($config, $name);
        $this->capsule->getDatabaseManager()->setDefaultConnection($name);
    }

    public function boot(): void
    {
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    public function getConnection(?string $name = null)
    {
        return $this->capsule->getConnection($name);
    }

    public function schema(?string $name = null)
    {
        return $this->getConnection($name)->getSchemaBuilder();
    }
}