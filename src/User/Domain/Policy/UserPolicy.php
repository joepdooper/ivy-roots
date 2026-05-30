<?php

namespace Ivy\User\Domain\Policy;

use Ivy\Shared\Base\Policy;
use Ivy\User\Domain\Entity\User;

class UserPolicy extends Policy
{
    public function index(User $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function sync(User $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function save(User $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function add(User $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function update(User $user): bool
    {
        return $this->canEditAsAdmin();
    }

    public function delete(User $user): bool
    {
        if(($user->id != $this->auth->getUserId()) && $this->canEditAsSuperAdmin()) {
            return true;
        }

        return false;
    }
}
