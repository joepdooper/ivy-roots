<?php

namespace Ivy\Core\Contracts;

interface PluginInterface {
    public function register(): void;
    public function install(): void;
    public function uninstall(): void;
}