<?php

namespace Ivy\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\Domain\Entity\ProfileEntity;

class ProfilePolicy extends Policy
{
    public function index(ProfileEntity $profile): bool
    {
        return $this->isLoggedIn();
    }

    public function sync(ProfileEntity $profile): bool
    {
        return $this->isLoggedIn();
    }

    public function save(ProfileEntity $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(ProfileEntity $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(ProfileEntity $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(ProfileEntity $profile): bool
    {
        return $this->canEditAsAdmin();
    }
}
