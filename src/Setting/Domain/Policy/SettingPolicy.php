<?php

namespace Ivy\Domain\Policy;

use Ivy\Plugin\Domain\Entity\SettingModel;
use Ivy\Shared\Base\Policy;

class SettingPolicy extends Policy
{
    public function index(SettingModel $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(SettingModel $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(SettingModel $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(SettingModel $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(SettingModel $setting): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(SettingModel $setting): bool
    {
        if (! $setting->is_default && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
