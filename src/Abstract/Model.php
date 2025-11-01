<?php

namespace Ivy\Abstract;

use Ivy\Trait\HasQueryBuilder;
use Ivy\Trait\CanPersist;
use Ivy\Trait\HasRelationships;
use Ivy\Trait\HasPolicies;
use Ivy\Trait\HasUtilities;
use Ivy\Trait\HasMagicProperties;

abstract class Model
{
    use HasQueryBuilder, CanPersist, HasRelationships, HasPolicies, HasUtilities, HasMagicProperties;

    protected string $table;
    protected string $path;
    protected array $columns = [];
    protected ?int $id = null;
    protected array $relationCache = [];

    public function __construct()
    {
        $this->initQueryBuilder($this->table);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
