<?php

namespace Ivy\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\Domain\Entity\TemplateEntity;

class TemplatePolicy extends Policy
{
    public function index(TemplateEntity $template): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(TemplateEntity $template): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(TemplateEntity $template): bool
    {
        return $this->canEditAsAdmin();
    }
}
