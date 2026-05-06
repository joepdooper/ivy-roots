<?php

namespace Ivy\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\Domain\Entity\PluginEntity;

class PluginPolicy extends Policy
{
    public function index(PluginEntity $plugin): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(PluginEntity $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function install(PluginEntity $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function uninstall(PluginEntity $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function update(PluginEntity $plugin): bool
    {
        return $this->canEditAsAdmin();
    }

    public function collection(PluginEntity $plugin): bool
    {
        if ($plugin->info->hasCollection() && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }

    public function settings(PluginEntity $plugin): bool
    {
        if ($plugin->info->hasSettings() && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
