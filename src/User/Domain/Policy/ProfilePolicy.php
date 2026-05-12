<?php

namespace Ivy\User\Domain\Policy;

use Ivy\Plugin\Domain\Entity\ProfileModel;
use Ivy\Shared\Base\Policy;

class ProfilePolicy extends Policy
{
    public function index(ProfileModel $profile): bool
    {
        return $this->isLoggedIn();
    }

    public function sync(ProfileModel $profile): bool
    {
        return $this->isLoggedIn();
    }

    public function save(ProfileModel $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(ProfileModel $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(ProfileModel $profile): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(ProfileModel $profile): bool
    {
        return $this->canEditAsAdmin();
    }
}
