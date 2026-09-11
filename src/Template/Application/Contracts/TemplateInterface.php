<?php

namespace Ivy\Template\Application\Contracts;

use Ivy\User\Application\Service\AuthService;

interface TemplateInterface
{
    public function register(AuthService $auth): void;

    public function install(): void;

    public function uninstall(): void;
}
