<?php

namespace Ivy\Template\Domain\Policy;

use Ivy\Plugin\Domain\Entity\TemplateModel;
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
