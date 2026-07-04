<?php

namespace Ivy\User\Presentation\Controller;

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
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Path;
use Ivy\Shared\Infrastructure\Service\MailService;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Entity\Profile;
use Ivy\User\Domain\Entity\User;
use Ivy\User\Domain\Exception\AuthorizationException;
use Ivy\User\Presentation\Form\LoginForm;
use Ivy\User\Presentation\Form\RegisterForm;
use Ivy\User\Presentation\Form\ResetForm;
use Ivy\User\Presentation\Form\UserForm;
use JetBrains\PhpStorm\NoReturn;

class UserController extends Controller
{
    private User $user;
    private UserForm $userForm;

    public function __construct()
    {
        parent::__construct();
        $this->user = new User();
        $this->userForm = new UserForm();
    }

    public function before(): void
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('admin/profile');
        } else {
            if (Path::get('CURRENT_PAGE') != Path::get('BASE_PATH') . 'user/login') {
                $this->redirect('user/login');
            }
        }
    }

    public function index(): void
    {
        $this->user->authorize('index');

        $users = User::all();
        View::render('admin/user.latte', ['users' => $users]);
    }

    /**
     * @throws UnknownIdException|AuthorizationException
     */
    public function update(User|int $user, mixed $data): void
    {
        if (is_int($user)) {
            $user = User::find($user);
        }

        $user->authorize('update');

        if ($data['editor']) {
            $this->authService->auth()->admin()->addRoleForUserById($user->id, Role::EDITOR);
        } else {
            $this->authService->auth()->admin()->removeRoleForUserById($user->id, Role::EDITOR);
        }
        if ($data['admin']) {
            $this->authService->auth()->admin()->addRoleForUserById($user->id, Role::ADMIN);
        } else {
            $this->authService->auth()->admin()->removeRoleForUserById($user->id, Role::ADMIN);
        }
        if ($data['super_admin']) {
            $this->authService->auth()->admin()->addRoleForUserById($user->id, Role::SUPER_ADMIN);
        } else {
            $this->authService->auth()->admin()->removeRoleForUserById($user->id, Role::SUPER_ADMIN);
        }

        $this->flashBag->add(
            'success',
            'Info ' . $user->username . ' updated successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function delete(User|int $user): void
    {
        if (is_int($user)) {
            $user = User::find($user);
        }

        $user->authorize('delete');

        try {
            $this->authService->auth()->admin()->deleteUserById($user->id);
        } catch (UnknownIdException|AuthError $e) {
            $this->flashBag->add('error', 'Something went wrong: ' . $e);
        }

        Profile::where('user_id', $user->id)->delete();

        $this->flashBag->add(
            'success',
            'User ' . $user->username . ' deleted successfully.'
        );
    }

    /**
     * @throws UnknownIdException|AuthorizationException
     */
    public function sync(): void
    {
        $this->user->authorize('sync');

        foreach ($this->request->request->all('user') as $data) {

            $result = $this->userForm->validate($data);

            if ($result->valid) {
                if (isset($result->data['delete'])) {
                    $this->delete($result->data['id']);
                } else {
                    $this->update($result->data['id'], $result->data);
                }
            }
        }

        $this->redirect('admin/user');
    }

    public function beforeRegister(): void
    {
        if ($this->authService->isLoggedIn()) {
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
        $result = (new RegisterForm)->validate($this->request->request->all());

        if (!$result->valid) {
            $this->redirectToFormWithErrors($result);
        }

        try {
            $userId = $this->authService->auth()->register($result->data['email'], $result->data['password'], $result->data['username'], function ($selector, $token) use ($result) {
                $url = Path::get('PUBLIC_URL') . 'user/login/' . urlencode($selector) . '/' . urlencode($token);
                // send email
                $mail = new MailService;
                $mail->addAddress($result->data['email'], $result->data['username']);
                $mail->setSubject('Activate account');
                $mail->setBody('Activate your account with this link: ' . $url);
                $mail->send();
            });

            Profile::create(['user_id' => $userId]);

            // Set role to registered user
            if (Setting::stashGet('registration_role')->bool && isset(Setting::stashGet('registration_role')->value)) {
                $role = strtoupper(Setting::stashGet('registration_role')->value);
                $roleConstant = "\Delight\Auth\Role::$role";
                $this->authService->auth()->admin()->addRoleForUserById($userId, constant($roleConstant));
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

        $this->flashBag->add('success', 'An email has been sent to ' . $this->request->request->get('email') . ' with a link to activate your account');
        $this->redirect('user/login');
    }

    public function viewRegister(): void
    {
        View::render('user/register.latte');
    }

    public function beforeLogin(): void
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('admin/profile');
        }
    }

    /**
     * @throws AuthError
     * @throws AttemptCancelledException
     */
    public function login(): void
    {
        $form = new LoginForm();
        $result = $form->validate($this->request->request->all());

        if (!$result->valid) {
            $this->redirectToFormWithErrors($result);
        }

        try {
            $this->authService->auth()->login((string) $this->request->request->get('email'), (string) $this->request->request->get('password'));
            $this->flashBag->add('success', 'Welcome ' . $this->authService->auth()->getUsername());
            $this->redirect('admin/profile');
        } catch (InvalidEmailException|InvalidPasswordException) {
            $this->flashBag->add('error', 'Wrong login credentials');
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
                if ($this->authService->auth()->isLoggedIn()) {
                    try {
                        $this->authService->auth()->logOutEverywhere();
                    } catch (NotLoggedInException) {
                        $this->flashBag->add('error', 'Not logged in');
                    }
                }
                $this->authService->auth()->confirmEmail($selector, $token);
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
        View::render('user/login.latte');
    }

    public function beforeLogout(): void
    {
        if (!$this->authService->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }

    /**
     * @throws AuthError
     */
    public function logout(): void
    {
        $this->authService->auth()->logOut();

        $this->redirect();
    }

    public function viewLogout(): void
    {
        View::render('user/logout.latte');
    }

    public function beforeReset(): void
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('admin/profile');
        }
    }

    /**
     * @throws AuthError
     */
    public function reset(): void
    {
        $form = new ResetForm();
        $result = $form->validate($this->request->request->all());

        if (!$result->valid) {
            $this->redirectToFormWithErrors($result);
        }

        if ($result->data['email']) {
            try {
                $this->authService->auth()->forgotPassword($result->data['email'], function ($selector, $token) use($result) {
                    $url = Path::get('PUBLIC_URL') . 'user/reset/' . urlencode($selector) . '/' . urlencode($token);
                    // send email
                    $mail = new MailService;
                    $mail->addAddress($result->data['email']);
                    $mail->setSubject('Reset password');
                    $mail->setBody('Reset password with this link: ' . $url);
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
            $this->flashBag->add('success', 'An email has been sent to ' . $this->request->request->get('email') . ' with a link to reset your password');
            $this->redirect('user/reset');
        }
        if ($result->data['password']) {
            try {
                $this->authService->auth()->resetPassword((string) $this->request->request->get('selector'), (string) $this->request->request->get('token'), (string) $this->request->request->get('password'));
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
                $this->authService->auth()->canResetPasswordOrThrow($selector, $token);
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
        View::render('user/reset.latte', ['selector' => $selector, 'token' => $token]);
    }
}
