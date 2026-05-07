<?php

namespace Ivy\Domain\Policy;

use Ivy\Domain\Model\InfoModel;
use Ivy\Shared\Base\Policy;

class InfoPolicy extends Policy
{
    public function index(InfoModel $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(InfoModel $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(InfoModel $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(InfoModel $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(InfoModel $info): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(InfoModel $info): bool
    {
        if (! $info->is_default && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
