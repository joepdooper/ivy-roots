<?php

namespace Ivy\Domain\Policy;

use Ivy\Domain\Model\UserModel;
use Ivy\Shared\Base\Policy;

class UserPolicy extends Policy
{
    public function index(UserModel $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(UserModel $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(UserModel $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(UserModel $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(UserModel $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(UserModel $user): bool
    {
        if(($user->id != $this->auth->getUserId()) && $this->canEditAsSuperAdmin()) {
            return true;
        }

        return false;
    }
}
