<?php

namespace Ivy\Policy;

use Ivy\Abstract\Policy;
use Ivy\Model\Profile;
use Ivy\Model\User;

class ProfilePolicy extends Policy
{
    public function index(Profile $profile): bool
    {
        return $this->isLoggedIn();
    }

    public function sync(Profile $profile): bool
    {
        return $this->isLoggedIn();
    }

    public function save(Profile $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(Profile $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(Profile $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(Profile $profile): bool
    {
        if (! $info->is_default && $this->canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
