<?php

namespace Ivy\Setting\Domain\Policy;

use Ivy\Setting\Domain\Entity\Info;
use Ivy\Shared\Base\Policy;

class InfoPolicy extends Policy
{
    public function index(Info $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(Info $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(Info $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(Info $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(Info $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(Info $info): bool
    {
        if (! $info->is_default && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
