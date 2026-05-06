<?php

namespace Ivy\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\Domain\Entity\SettingEntity;

class SettingPolicy extends Policy
{
    public function index(SettingEntity $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(SettingEntity $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(SettingEntity $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(SettingEntity $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(SettingEntity $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(SettingEntity $setting): bool
    {
        if (! $setting->is_default && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
