<?php

namespace Ivy\Shared\Presentation\Form;

use Ivy\Shared\Base\Form;

class FilterPackagistRequestForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'sort' => ['nullable', 'string', 'in:desc,asc'],
            'page' => ['nullable', 'numeric', 'min:1'],
            'per_page' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ];
    }
}