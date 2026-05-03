<?php

namespace Ivy\Policy;

use Ivy\Abstract\Policy;
use Ivy\Model\Setting;
use Ivy\Model\User;

class SettingPolicy extends Policy
{
    public function index(Setting $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(Setting $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(Setting $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(Setting $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(Setting $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(Setting $setting): bool
    {
        if (! $setting->is_default && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
