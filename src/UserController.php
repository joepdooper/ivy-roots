<?php

namespace Ivy;

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
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UnknownIdException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Db\Throwable\IntegrityConstraintViolationException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController extends Controller
{
    private User $user;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();
        $this->requireAdmin();

        $users_data = $this->request->get('user');

        foreach ($users_data as $user_data) {
            $this->user = (new User)->where('id', $user_data['id'])->fetchOne();

            if (isset($user_data['delete'])) {
                try {
                    $this->user::getAuth()->admin()->deleteUserById($this->user->id);
                } catch (UnknownIdException|AuthError $e) {
                    Message::add('Something went wrong: ' . $e);
                }
            } else {
                try {
                    if($this->user->id){
                        if ($user_data['editor']) {
                            $this->user::getAuth()->admin()->addRoleForUserById($this->user->id, Role::EDITOR);
                        } else {
                            $this->user::getAuth()->admin()->removeRoleForUserById($this->user->id, Role::EDITOR);
                        }
                        if ($user_data['admin']) {
                            $this->user::getAuth()->admin()->addRoleForUserById($this->user->id, Role::ADMIN);
                        } else {
                            $this->user::getAuth()->admin()->removeRoleForUserById($this->user->id, Role::ADMIN);
                        }
                        if ($user_data['super_admin']) {
                            $this->user::getAuth()->admin()->addRoleForUserById($this->user->id, Role::SUPER_ADMIN);
                        } else {
                            $this->user::getAuth()->admin()->removeRoleForUserById($this->user->id, Role::SUPER_ADMIN);
                        }
                    }
                } catch (UnknownIdException) {
                    Message::add('Unknown ID');
                }
            }
        }

        Message::add('Update successfully', Path::get('BASE_PATH') . 'admin/user');
    }

    public function index(): void
    {
        $this->requireGet();
        $this->requireLogin();
        $this->requireAdmin();

        $users = (new User)->fetchAll();
        Template::view('admin/user.latte', ['users' => $users]);
    }

    /**
     * @throws UnknownIdException
     * @throws AuthError
     * @throws IntegrityConstraintViolationException
     */
    public function register(): void
    {
        $this->requirePost();

        try {
            $userId = User::getAuth()->register($this->request->get('email'), $this->request->get('password'), $this->request->get('username'), function ($selector, $token) {
                $url = Path::get('BASE_PATH') . 'admin/login/' . urlencode($selector) . '/' . urlencode($token);
                // send email
                $mail = new Mail();
                $mail->addAddress($this->request->get('email'), $this->request->get('username'));
                $mail->setSubject('Activate account');
                $mail->setBody('Activate your account with this link: ' . $url);
                $mail->send();
            });
            DB::getConnection()->insert('profiles', ['user_id' => $userId]);
            // Set role to registered user
            if (Setting::getStash()['registration_role']->bool && Setting::getStash()['registration_role']->value) {
                $role = strtoupper(Setting::getStash()['registration_role']->value);
                $roleConstant = "\Delight\Auth\Role::$role";
                self::$auth->admin()->addRoleForUserById($userId, constant($roleConstant));
            }
        } catch (InvalidEmailException) {
            Message::add('Invalid email address', Path::get('BASE_PATH') . 'admin/register');
        } catch (InvalidPasswordException) {
            Message::add('Invalid password', Path::get('BASE_PATH') . 'admin/register');
        } catch (UserAlreadyExistsException) {
            Message::add('User already exists', Path::get('BASE_PATH') . 'admin/register');
        } catch (TooManyRequestsException) {
            Message::add('Too many requests', Path::get('BASE_PATH') . 'admin/register');
        }

        Message::add('An email has been sent to ' . $this->request->get('email') . ' with a link to activate your account', Path::get('BASE_PATH') . 'admin/login');
    }

    public function viewRegister(): void
    {
        Template::view('admin/register.latte');
    }


    /**
     * @throws AuthError
     * @throws AttemptCancelledException
     */
    public function login(): void
    {
        $this->requirePost();

        try {
            User::getAuth()->login($this->request->get('email'), $this->request->get('password'));
            Message::add('Welcome ' . User::getAuth()->getUsername(), Path::get('BASE_PATH') . 'admin/profile');
        } catch (InvalidEmailException) {
            Message::add('Wrong email address', Path::get('BASE_PATH') . 'admin/login');
        } catch (InvalidPasswordException) {
            Message::add('Wrong password', Path::get('BASE_PATH') . 'admin/login');
        } catch (EmailNotVerifiedException) {
            Message::add('Email not verified', Path::get('BASE_PATH') . 'admin/login');
        } catch (TooManyRequestsException) {
            Message::add('Too many requests', Path::get('BASE_PATH') . 'admin/login');
        }
    }

    public function viewLogin($selector = null, $token = null): void
    {
        if (isset($selector) && isset($token)) {
            try {
                if (User::getAuth()->isLoggedIn()) {
                    try {
                        User::getAuth()->logOutEverywhere();
                    } catch (NotLoggedInException) {
                        Message::add('Not logged in');
                    }
                }
                User::getAuth()->confirmEmail($selector, $token);
                Message::add('Email address has been verified', Path::get('BASE_PATH') . 'admin/login');
            } catch (InvalidSelectorTokenPairException) {
                Message::add('Invalid token');
            } catch (TokenExpiredException) {
                Message::add('Token expired');
            } catch (UserAlreadyExistsException) {
                Message::add('Email address already exists');
            } catch (TooManyRequestsException) {
                Message::add('Too many requests');
            } catch (AuthError) {
                Message::add('Auth error');
            }
        }
        Template::view('admin/login.latte');
    }

    /**
     * @throws AuthError
     */
    public function logout(): void
    {
        $this->requirePost();

        Template::hooks()->do_action('start_logout_action');

        User::getAuth()->logOut();
        User::getAuth()->destroySession();

        Template::hooks()->do_action('end_logout_action');

        Message::add('Logout successfully', Path::get('BASE_PATH'));
    }

    public function viewLogout(): void
    {
        Template::view('admin/logout.latte');
    }

    /**
     * @throws AuthError
     */
    public function reset(): void
    {
        $this->requirePost();

        if ($this->request->get('email')) {
            try {
                User::getAuth()->forgotPassword($this->request->get('email'), function ($selector, $token) {
                    $url = Path::get('BASE_PATH') . 'admin/reset/' . urlencode($selector) . '/' . urlencode($token);
                    // send email
                    $mail = new Mail();
                    $mail->addAddress($this->request->get('email'));
                    $mail->setSubject('Reset password');
                    $mail->setBody('Reset password with this link: ' . $url);
                    $mail->send();
                });
            } catch (InvalidEmailException) {
                Message::add('Invalid email address', Path::get('BASE_PATH') . 'admin/reset');
            } catch (EmailNotVerifiedException) {
                Message::add('Email not verified', Path::get('BASE_PATH') . 'admin/reset');
            } catch (ResetDisabledException) {
                Message::add('Password reset is disabled', Path::get('BASE_PATH') . 'admin/reset');
            } catch (TooManyRequestsException) {
                Message::add('Too many requests', Path::get('BASE_PATH') . 'admin/reset');
            }
            Message::add('An email has been sent to ' . $this->request->get('email') . ' with a link to reset your password', Path::get('BASE_PATH') . 'admin/reset');
        }

        if ($this->request->get('password')) {
            try {
                User::getAuth()->resetPassword($this->request->get('selector'), $this->request->get('token'), $this->request->get('password'));
                Message::add('Password has been reset', Path::get('BASE_PATH') . 'admin/login');
            } catch (InvalidSelectorTokenPairException) {
                Message::add('Invalid token', Path::get('BASE_PATH') . 'admin/reset');
            } catch (TokenExpiredException) {
                Message::add('Token expired', Path::get('BASE_PATH') . 'admin/reset');
            } catch (ResetDisabledException) {
                Message::add('Password reset is disabled', Path::get('BASE_PATH') . 'admin/reset');
            } catch (InvalidPasswordException) {
                Message::add('Invalid password', Path::get('BASE_PATH') . 'admin/reset');
            } catch (TooManyRequestsException) {
                Message::add('Too many requests', Path::get('BASE_PATH') . 'admin/reset');
            }
        }
    }

    public function viewReset($selector = null, $token = null): void
    {
        if (isset($selector) && isset($token)) {
            try {
                User::getAuth()->canResetPasswordOrThrow($selector, $token);
                Message::add('Create a new secure password');
            } catch (InvalidSelectorTokenPairException $e) {
                Message::add('Invalid token', Path::get('BASE_PATH') . 'admin/reset');
            } catch (TokenExpiredException $e) {
                Message::add('Token expired', Path::get('BASE_PATH') . 'admin/reset');
            } catch (ResetDisabledException $e) {
                Message::add('Password reset is disabled', Path::get('BASE_PATH') . 'admin/reset');
            } catch (TooManyRequestsException $e) {
                Message::add('Too many requests', Path::get('BASE_PATH') . 'admin/reset');
            } catch (AuthError) {
                Message::add('Auth error', Path::get('BASE_PATH') . 'admin/reset');
            }
        }
        Template::view('admin/reset.latte', ['selector' => $selector, 'token' => $token]);
    }

}
