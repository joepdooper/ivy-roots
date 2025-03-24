<?php

namespace Ivy\Policies;

use Ivy\Template;
use Ivy\User;

class TemplatePolicy
{
    public static function post(Template $template): bool
    {
        return User::canEditAsAdmin();
    }

    public static function index(Template $template): bool
    {
        return User::canEditAsAdmin();
    }
}