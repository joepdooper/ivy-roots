<?php

namespace Ivy\Controller;

use Delight\Auth\AttemptCancelledException;
use Delight\Auth\Auth;
use Delight\Auth\AuthError;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\Role;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\UnknownIdException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Db\Throwable\IntegrityConstraintViolationException;
use Ivy\Abstract\Controller;
use Ivy\Mail;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Path;
use Ivy\View\View;

class AdminController extends Controller
{
    public function before(): void
    {
        if (!User::getAuth()->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }
}
