<?php

namespace Ivy\Domain\Policy;

use Ivy\Domain\Model\ProfileModel;
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
