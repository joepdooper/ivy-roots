<?php

namespace Ivy\Domain\Policy;

use Ivy\Domain\Model\TemplateModel;
use Ivy\Shared\Base\Policy;

class TemplatePolicy extends Policy
{
    public function index(TemplateModel $template): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(TemplateModel $template): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(TemplateModel $template): bool
    {
        return $this->canEditAsAdmin();
    }
}
