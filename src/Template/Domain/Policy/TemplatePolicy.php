<?php

namespace Ivy\Template\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\Template\Domain\Entity\Template;

class TemplatePolicy extends Policy
{
    public function index(Template $template): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(Template $template): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(Template $template): bool
    {
        return $this->canEditAsAdmin();
    }
}
