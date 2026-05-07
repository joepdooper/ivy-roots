<?php

namespace Ivy\Shared\Contracts;

interface PluginInterface {
    public function register(): void;
    public function install(): void;
    public function uninstall(): void;
}
