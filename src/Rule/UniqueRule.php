<?php

namespace Ivy\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;

class UniqueRule implements Rule
{
    protected string $field;

    public function __construct(protected array $parameters = []) {}

    public function passes(string $field, $value, array $data): bool
    {
        $this->field = $field;

        $modelClass = $this->parameters[0] ?? null;

        if (!$modelClass) {
            throw new \InvalidArgumentException('UniqueRule requires a model class.');
        }

        $model = new $modelClass;

        $query = $model->newQuery()
            ->where($field, $value);

        if (!empty($data['id'])) {
            $query->where('id', '!=', $data['id']);
        }

        return !$query->exists();
    }

    public function message(): string
    {
        return "The field '{$this->field}' must be unique.";
    }
}