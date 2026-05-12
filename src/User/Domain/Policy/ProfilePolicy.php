<?php

namespace Ivy\User\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\User\Domain\Entity\Profile;

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
        return $this->canEditAsAdmin();
    }
}
