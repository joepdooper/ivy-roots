<?php

namespace Ivy\Policy;

use Ivy\Model\Template;
use Ivy\Model\User;

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