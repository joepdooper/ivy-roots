<?php

namespace Ivy\Controller;

use Delight\Auth\AttemptCancelledException;
use Delight\Auth\AuthError;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\Role;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UnknownIdException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Db\Throwable\IntegrityConstraintViolationException;
use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Form\UserForm;
use Ivy\Manager\DatabaseManager;
use Ivy\Model\Setting;
use Ivy\Model\User;
use Ivy\Service\Mail;
use Ivy\View\View;

class UserController extends Controller
{
    private User $user;
    private UserForm $userForm;

    public function __construct()
    {
        parent::__construct();
        $this->user = new User;
        $this->userForm = new UserForm;
    }

    public function before(): void
    {
        if (User::getAuth()->isLoggedIn()) {
            $this->redirect('admin/profile');
        } else {
            if (Path::get('CURRENT_PAGE') != Path::get('BASE_PATH').'user/login') {
                $this->redirect('user/login');
            }
        }
    }

    public function index(): void
    {
        $this->user->authorize('index');

        $users = (new User)->all();
        View::set('admin/user.latte', ['users' => $users]);
    }

    public function sync(): void
    {
        $this->user->authorize('sync');

        foreach ($this->request->get('user') as $index => $data) {

            $result = $this->userForm->validate($data);

            if($result->valid){

                $user = User::where('id', $result->data['id'])->first();

                if ($user) {
                    if (isset($result->data['delete'])) {
                        try {
                            $user::getAuth()->admin()->deleteUserById($user->id);
                        } catch (UnknownIdException|AuthError $e) {
                            $this->flashBag->add('error', 'Something went wrong: ' . $e);
                        }
                    } else {
                        if ($result->data['editor']) {
                            $user::getAuth()->admin()->addRoleForUserById($user->id, Role::EDITOR);
                        } else {
                            $user::getAuth()->admin()->removeRoleForUserById($user->id, Role::EDITOR);
                        }
                        if ($result->data['admin']) {
                            $user::getAuth()->admin()->addRoleForUserById($user->id, Role::ADMIN);
                        } else {
                            $user::getAuth()->admin()->removeRoleForUserById($user->id, Role::ADMIN);
                        }
                        if ($result->data['super_admin']) {
                            $user::getAuth()->admin()->addRoleForUserById($user->id, Role::SUPER_ADMIN);
                        } else {
                            $user::getAuth()->admin()->removeRoleForUserById($user->id, Role::SUPER_ADMIN);
                        }
                    }
                }

            }
        }

        $this->flashBag->add('success', 'Update successfully');
        $this->redirect('admin/user');
    }

    public function beforeRegister(): void
    {
        if (User::getAuth()->isLoggedIn()) {
            $this->redirect('admin/profile');
        }
    }

    /**
     * @throws UnknownIdException
     * @throws AuthError
     * @throws IntegrityConstraintViolationException
     */
    public function register(): void
    {
        try {
            $userId = User::getAuth()->register($this->request->get('email'), $this->request->get('password'), $this->request->get('username'), function ($selector, $token) {
                $url = Path::get('PUBLIC_URL').'user/login/'.urlencode($selector).'/'.urlencode($token);
                // send email
                $mail = new Mail;
                $mail->addAddress($this->request->get('email'), $this->request->get('username'));
                $mail->setSubject('Activate account');
                $mail->setBody('Activate your account with this link: '.$url);
                $mail->send();
            });
            DatabaseManager::connection()->insert('profiles', ['user_id' => $userId]);

            // Set role to registered user
            if (isset(Setting::stashGet('registration_role')->value)) {
                $role = strtoupper(Setting::stashGet('registration_role')->value);
                $roleConstant = "\Delight\Auth\Role::$role";
                User::getAuth()->admin()->addRoleForUserById($userId, constant($roleConstant));
            }

        } catch (InvalidEmailException) {
            $this->flashBag->add('error', 'Invalid email address');
            $this->redirect('user/register');
        } catch (InvalidPasswordException) {
            $this->flashBag->add('error', 'Invalid password');
            $this->redirect('user/register');
        } catch (UserAlreadyExistsException) {
            $this->flashBag->add('error', 'User already exists');
            $this->redirect('user/register');
        } catch (TooManyRequestsException) {
            $this->flashBag->add('error', 'Too many requests');
            $this->redirect('user/register');
        }

        $this->flashBag->add('success', 'An email has been sent to '.$this->request->get('email').' with a link to activate your account');
        $this->redirect('user/login');
    }

    public function viewRegister(): void
    {
        View::set('user/register.latte');
    }

    public function beforeLogin(): void
    {
        if (User::getAuth()->isLoggedIn()) {
            $this->redirect('admin/profile');
        }
    }

    /**
     * @throws AuthError
     * @throws AttemptCancelledException
     */
    public function login(): void
    {
        try {
            User::getAuth()->login($this->request->get('email'), $this->request->get('password'));
            $this->flashBag->add('success', 'Welcome '.User::getAuth()->getUsername());
            $this->redirect('admin/profile');
        } catch (InvalidEmailException) {
            $this->flashBag->add('error', 'Wrong email address');
            $this->redirect('user/login');
        } catch (InvalidPasswordException) {
            $this->flashBag->add('error', 'Wrong password');
            $this->redirect('user/login');
        } catch (EmailNotVerifiedException) {
            $this->flashBag->add('error', 'Email not verified');
            $this->redirect('user/login');
        } catch (TooManyRequestsException) {
            $this->flashBag->add('error', 'Too many requests');
            $this->redirect('user/login');
        }
    }

    public function viewLogin(?string $selector = null, ?string $token = null): void
    {
        if (isset($selector) && isset($token)) {
            try {
                if (User::getAuth()->isLoggedIn()) {
                    try {
                        User::getAuth()->logOutEverywhere();
                    } catch (NotLoggedInException) {
                        $this->flashBag->add('error', 'Not logged in');
                    }
                }
                User::getAuth()->confirmEmail($selector, $token);
                $this->flashBag->add('success', 'Email address has been verified');
            } catch (InvalidSelectorTokenPairException) {
                $this->flashBag->add('error', 'Invalid token');
            } catch (TokenExpiredException) {
                $this->flashBag->add('error', 'Token expired');
            } catch (UserAlreadyExistsException) {
                $this->flashBag->add('error', 'Email address already exists');
            } catch (TooManyRequestsException) {
                $this->flashBag->add('error', 'Too many requests');
            } catch (AuthError) {
                $this->flashBag->add('error', 'Auth error');
            }
        }
        View::set('user/login.latte');
    }

    public function beforeLogout(): void
    {
        if (! User::getAuth()->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }

    /**
     * @throws AuthError
     */
    public function logout(): void
    {
        User::getAuth()->logOut();

        $this->redirect();
    }

    public function viewLogout(): void
    {
        View::set('user/logout.latte');
    }

    public function beforeReset(): void
    {
        if (User::getAuth()->isLoggedIn()) {
            $this->redirect('admin/profile');
        }
    }

    /**
     * @throws AuthError
     */
    public function reset(): void
    {
        if ($this->request->get('email')) {
            try {
                User::getAuth()->forgotPassword($this->request->get('email'), function ($selector, $token) {
                    $url = Path::get('PUBLIC_URL').'user/reset/'.urlencode($selector).'/'.urlencode($token);
                    // send email
                    $mail = new Mail;
                    $mail->addAddress($this->request->get('email'));
                    $mail->setSubject('Reset password');
                    $mail->setBody('Reset password with this link: '.$url);
                    $mail->send();
                });
            } catch (InvalidEmailException) {
                $this->flashBag->add('error', 'Invalid email address');
                $this->redirect('user/reset');
            } catch (EmailNotVerifiedException) {
                $this->flashBag->add('error', 'Email not verified');
                $this->redirect('user/reset');
            } catch (ResetDisabledException) {
                $this->flashBag->add('error', 'Password reset is disabled');
                $this->redirect('user/reset');
            } catch (TooManyRequestsException) {
                $this->flashBag->add('error', 'Too many requests');
                $this->redirect('user/reset');
            }
            $this->flashBag->add('success', 'An email has been sent to '.$this->request->get('email').' with a link to reset your password');
            $this->redirect('user/reset');
        }

        if ($this->request->get('password')) {
            try {
                User::getAuth()->resetPassword($this->request->get('selector'), $this->request->get('token'), $this->request->get('password'));
                $this->flashBag->add('success', 'Password has been reset');
                $this->redirect('user/login');
            } catch (InvalidSelectorTokenPairException) {
                $this->flashBag->add('error', 'Invalid token');
                $this->redirect('user/reset');
            } catch (TokenExpiredException) {
                $this->flashBag->add('error', 'Token expired');
                $this->redirect('user/reset');
            } catch (ResetDisabledException) {
                $this->flashBag->add('error', 'Password reset is disabled');
                $this->redirect('user/reset');
            } catch (InvalidPasswordException) {
                $this->flashBag->add('error', 'Invalid password');
                $this->redirect('user/reset');
            } catch (TooManyRequestsException) {
                $this->flashBag->add('error', 'Too many requests');
                $this->redirect('user/reset');
            }
        }
    }

    public function viewReset(?string $selector = null, ?string $token = null): void
    {
        if (isset($selector) && isset($token)) {
            try {
                User::getAuth()->canResetPasswordOrThrow($selector, $token);
                $this->flashBag->add('success', 'Create a new secure password');
            } catch (InvalidSelectorTokenPairException $e) {
                $this->flashBag->add('error', 'Invalid token');
                $this->redirect('user/reset');
            } catch (TokenExpiredException $e) {
                $this->flashBag->add('error', 'Token expired');
                $this->redirect('user/reset');
            } catch (ResetDisabledException $e) {
                $this->flashBag->add('error', 'Password reset is disabled');
                $this->redirect('user/reset');
            } catch (TooManyRequestsException $e) {
                $this->flashBag->add('error', 'Too many requests');
                $this->redirect('user/reset');
            } catch (AuthError) {
                $this->flashBag->add('error', 'Auth error');
                $this->redirect('user/reset');
            }
        }
        View::set('user/reset.latte', ['selector' => $selector, 'token' => $token]);
    }
}
