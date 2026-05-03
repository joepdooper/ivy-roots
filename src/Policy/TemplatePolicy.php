<?php

namespace Ivy\Policy;

use Ivy\Abstract\Policy;
use Ivy\Model\Template;
use Ivy\Model\User;

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
