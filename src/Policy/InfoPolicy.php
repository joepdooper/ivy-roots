<?php

namespace Ivy\Policy;

use Ivy\Abstract\Policy;
use Ivy\Model\Info;

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
