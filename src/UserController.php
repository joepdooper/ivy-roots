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

        $users_data = $this->request->input('user') ?? '';

        foreach ($users_data as $key => $user_data) {
            $this->user = new User;

            if (isset($user_data['delete'])) {
                try {
                    $this->user::getAuth()->admin()->deleteUserById($key);
                } catch (UnknownIdException|AuthError $e) {
                    Message::add('Something went wrong: ' . $e);
                }
            } else {
                try {
                    if ($user_data['editor']) {
                        $this->user::getAuth()->admin()->addRoleForUserById($key, Role::EDITOR);
                    } else {
                        $this->user::getAuth()->admin()->removeRoleForUserById($key, Role::EDITOR);
                    }
                    if ($user_data['admin']) {
                        $this->user::getAuth()->admin()->addRoleForUserById($key, Role::ADMIN);
                    } else {
                        $this->user::getAuth()->admin()->removeRoleForUserById($key, Role::ADMIN);
                    }
                    if ($user_data['super_admin']) {
                        $this->user::getAuth()->admin()->addRoleForUserById($key, Role::SUPER_ADMIN);
                    } else {
                        $this->user::getAuth()->admin()->removeRoleForUserById($key, Role::SUPER_ADMIN);
                    }
                } catch (UnknownIdException) {
                    Message::add('Unknown ID');
                }
            }
        }

        Message::add('Update successfully', _BASE_PATH . 'admin/user');
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
            $userId = User::getAuth()->register($this->request->input('email'), $this->request->input('password'), $this->request->input('username'), function ($selector, $token) {
                $url = Path::get('BASE_PATH') . 'admin/login/' . urlencode($selector) . '/' . urlencode($token);
                // send email
                $mail = new Mail();
                $mail->addAddress($this->request->input('email'), $this->request->input('username'));
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

        Message::add('An email has been sent to ' . $this->request->input('email') . ' with a link to activate your account', Path::get('BASE_PATH') . 'admin/login');
    }


    /**
     * @throws AuthError
     * @throws AttemptCancelledException
     */
    public function login(): void
    {
        $this->requirePost();

        try {
            User::getAuth()->login($this->request->input('email'), $this->request->input('password'));
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

    /**
     * @throws AuthError
     */
    public function reset(): void
    {
        $this->requirePost();

        if ($this->request->input('email')) {
            try {
                User::getAuth()->forgotPassword($this->request->input('email'), function ($selector, $token) {
                    $url = Path::get('BASE_PATH') . 'admin/reset/' . urlencode($selector) . '/' . urlencode($token);
                    // send email
                    $mail = new Mail();
                    $mail->addAddress($this->request->input('email'));
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
            Message::add('An email has been sent to ' . $this->request->input('email') . ' with a link to reset your password', Path::get('BASE_PATH') . 'admin/reset');
        }

        if ($this->request->input('password')) {
            try {
                User::getAuth()->resetPassword($this->request->input('selector'), $this->request->input('token'), $this->request->input('password'));
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

}
