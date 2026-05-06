<?php

namespace Ivy\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\Domain\Entity\UserEntity;

class UserPolicy extends Policy
{
    public function index(UserEntity $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(UserEntity $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(UserEntity $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(UserEntity $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(UserEntity $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(UserEntity $user): bool
    {
        if(($user->id != $this->auth->getUserId()) && $this->canEditAsSuperAdmin()) {
            return true;
        }

        return false;
    }
}
