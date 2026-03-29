<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Model\User;

class AdminController extends Controller
{
    public function before(): void
    {
        if (! User::getAuth()->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }
}
