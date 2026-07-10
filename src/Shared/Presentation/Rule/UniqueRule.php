<?php

namespace Ivy\Shared\Presentation\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;
use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Core\Language;

class UniqueRule implements Rule
{
    protected string $field;

    public function __construct(
        /** @var array<int, class-string|mixed> $parameters */
        protected array $parameters = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function passes(string $field, string $value, array $data): bool
    {
        $this->field = $field;

        /** @var class-string<Model> $modelClass */
        $modelClass = $this->parameters[0] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException('UniqueRule requires a model class.');
        }

        $model = new $modelClass;

        $query = $model->newQuery()
            ->where($field, $value);

        if (! empty($data['id'])) {
            $query->where('id', '!=', $data['id']);
        }

        return ! $query->exists();
    }

    public function message(): string
    {
        return Language::translate('form.rules.unique', ['field' => $this->field]);
    }
}
