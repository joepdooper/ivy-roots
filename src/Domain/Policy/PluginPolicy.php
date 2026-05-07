<?php

namespace Ivy\Domain\Policy;

use Ivy\Domain\Model\PluginModel;
use Ivy\Shared\Base\Policy;

class PluginPolicy extends Policy
{
    public function index(PluginModel $plugin): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(PluginModel $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function install(PluginModel $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function uninstall(PluginModel $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function update(PluginModel $plugin): bool
    {
        return $this->canEditAsAdmin();
    }

    public function collection(PluginModel $plugin): bool
    {
        if ($plugin->info->hasCollection() && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }

    public function settings(PluginModel $plugin): bool
    {
        if ($plugin->info->hasSettings() && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
