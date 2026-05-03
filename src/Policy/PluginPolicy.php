<?php

namespace Ivy\Policy;

use Ivy\Abstract\Policy;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\User;

class PluginPolicy extends Policy
{
    public function index(Plugin $plugin): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(Plugin $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function install(Plugin $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function uninstall(Plugin $plugin): bool
    {
        return $this->canEditAsSuperAdmin();
    }

    public function update(Plugin $plugin): bool
    {
        return $this->canEditAsAdmin();
    }

    public function collection(Plugin $plugin): bool
    {
        if ($plugin->info->hasCollection() && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }

    public function settings(Plugin $plugin): bool
    {
        if ($plugin->info->hasSettings() && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
