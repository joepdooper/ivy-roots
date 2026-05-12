<?php

namespace Ivy\Plugin\Domain\Policy;

use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Shared\Base\Policy;

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
