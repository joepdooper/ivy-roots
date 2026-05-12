<?php

namespace Ivy\Setting\Domain\Policy;

use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Shared\Base\Policy;

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
