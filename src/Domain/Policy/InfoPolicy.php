<?php

namespace Ivy\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\Domain\Entity\InfoEntity;

class InfoPolicy extends Policy
{
    public function index(InfoEntity $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(InfoEntity $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(InfoEntity $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(InfoEntity $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(InfoEntity $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(InfoEntity $info): bool
    {
        if (! $info->is_default && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
