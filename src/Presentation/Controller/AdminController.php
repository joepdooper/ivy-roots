<?php

namespace Ivy\Presentation\Controller;

use Ivy\Domain\Entity\UserEntity;
use Ivy\Shared\Base\Controller;

class AdminController extends Controller
{
    public function before(): void
    {
        if (! $this->authService->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }
}
