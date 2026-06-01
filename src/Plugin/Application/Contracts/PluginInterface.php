<?php

namespace Ivy\Plugin\Application\Contracts;

use Ivy\User\Application\Service\AuthService;

interface PluginInterface {
    public function register(AuthService $auth): void;
    public function install(): void;
    public function uninstall(): void;
}
