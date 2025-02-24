<?php

namespace Ivy;

use Delight\Auth\Administration;
use Delight\Auth\AttemptCancelledException;
use Delight\Auth\Auth;
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
use Exception;
use Hooks;
use HTMLPurifier_Config;
use HTMLPurifier;
use function urlencode;

class User extends Model
{

    protected string $table = 'users';
    protected string $path = 'admin/user';
    protected array $columns = [
        'email',
        'username',
        'users_image',
        'status',
        'verified',
        'resettable',
        'roles_mask',
        'registered',
        'last_login'
    ];

    private static Auth $auth;

    protected string $email;
    protected string $username;
    protected string $users_image;
    protected int $status;
    protected int $verified;
    protected int $resettable;
    protected int $roles_mask;
    protected int $registered;
    protected int $last_login;

    // Register

    /**
     * @throws UnknownIdException
     * @throws AuthError
     * @throws IntegrityConstraintViolationException
     */
    public function register(): void
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            try {
                $userId = self::$auth->register($purifier->purify($_POST['email']), $purifier->purify($_POST['password']), $purifier->purify($_POST['username']), function ($selector, $token) use ($purifier) {
                    $url = Path::get('BASE_PATH') . 'admin/login/' . urlencode($selector) . '/' . urlencode($token);
                    // send email
                    $mail = new Mail();
                    $mail->Address = $purifier->purify($_POST['email']);
                    $mail->Name = $purifier->purify($_POST['username']);
                    $mail->Subject = 'Activate account';
                    $mail->Body = 'Activate your account with this link: ' . $url;
                    $mail->AltBody = 'Activate your account with this link: ' . $url;
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

            Message::add('An email has been sent to ' . $_POST['email'] . ' with a link to activate your account', Path::get('BASE_PATH') . 'admin/login');

        }

    }

    // Login

    /**
     * @throws AuthError
     * @throws AttemptCancelledException
     */
    public function login(): void
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            try {
                self::$auth->login($purifier->purify($_POST['email']), $purifier->purify($_POST['password']));
                Message::add('Welcome ' . self::$auth->getUsername(), Path::get('BASE_PATH') . 'admin/profile');
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

    }

    // Logout

    /**
     * @throws AuthError
     */
    public function logout(): void
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            Template::hooks()->do_action('start_logout_action');

            self::$auth->logOut();
            self::$auth->destroySession();

            Template::hooks()->do_action('end_logout_action');

            Message::add('Logout successfully', Path::get('BASE_PATH'));

        }

    }

    // Reset

    /**
     * @throws AuthError
     */
    public function reset(): void
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            if (isset($_POST['email'])) {

                try {
                    self::$auth->forgotPassword($purifier->purify($_POST['email']), function ($selector, $token) use ($purifier) {
                        $url = Path::get('BASE_PATH') . 'admin/reset/' . urlencode($selector) . '/' . urlencode($token);
                        // send email
                        $mail = new Mail();
                        $mail->Address = $purifier->purify($_POST['email']);
                        $mail->Name = '';
                        $mail->Subject = 'Reset password';
                        $mail->Body = 'Reset password with this link: ' . $url;
                        $mail->AltBody = 'Reset password with this link: ' . $url;
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

                Message::add('An email has been sent to ' . $_POST['email'] . ' with a link to reset your password', Path::get('BASE_PATH') . 'admin/reset');

            }

            if (isset($_POST['password'])) {
                try {
                    self::$auth->resetPassword($_POST['selector'], $_POST['token'], $_POST['password']);
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

    static function canEditAsEditor(): bool
    {
        $roles = [
            Role::EDITOR,
            Role::ADMIN,
            Role::SUPER_ADMIN
        ];
        return self::$auth->hasAnyRole(...$roles);
    }

    static function canEditAsAdmin(): bool
    {
        $roles = [
            Role::ADMIN,
            Role::SUPER_ADMIN
        ];
        return self::$auth->hasAnyRole(...$roles);
    }

    static function canEditAsSuperAdmin(): bool
    {
        $roles = [
            Role::SUPER_ADMIN
        ];
        return self::$auth->hasAnyRole(...$roles);
    }

    /**
     * @throws UnknownIdException
     */
    static function userIsSuperAdmin($id): bool
    {
        return self::$auth->admin()->doesUserHaveRole($id, Role::SUPER_ADMIN);
    }

    /**
     * @throws UnknownIdException
     */
    static function userIsAdmin($id): bool
    {
        return self::$auth->admin()->doesUserHaveRole($id, Role::ADMIN);
    }

    /**
     * @throws UnknownIdException
     */
    static function userIsEditor($id): bool
    {
        return self::$auth->admin()->doesUserHaveRole($id, Role::EDITOR);
    }

    static function isLoggedIn(): bool
    {
        return self::$auth->isLoggedIn();
    }

    static function getRoles(): array
    {
        return self::$auth->getRoles();
    }

    static function admin(): Administration
    {
        return self::$auth->admin();
    }

    static function auth(): void
    {
        self::$auth = new Auth(DB::getConnection(), true);
    }

    public static function getUsername(): ?string
    {
        return self::$auth->getUsername();
    }

    public static function getEmail(): ?string
    {
        return self::$auth->getEmail();
    }

    public static function getUserId(): ?int
    {
        return self::$auth->getUserId();
    }

    /**
     * @throws AuthError
     * @throws NotLoggedInException
     */
    public static function logOutEverywhere(): void
    {
        self::$auth->logOutEverywhere();
    }

    /**
     * @throws InvalidEmailException
     * @throws TooManyRequestsException
     * @throws AuthError
     * @throws UserAlreadyExistsException
     * @throws EmailNotVerifiedException
     * @throws NotLoggedInException
     */
    public static function changeEmail($newEmail, $callback): void
    {
        self::$auth->changeEmail($newEmail, $callback);
    }

    /**
     * @throws TooManyRequestsException
     * @throws InvalidSelectorTokenPairException
     * @throws AuthError
     * @throws UserAlreadyExistsException
     * @throws TokenExpiredException
     */
    public static function confirmEmail($selector, $token): void
    {
        self::$auth->confirmEmail($selector, $token);
    }

    public static function canResetPasswordOrThrow($selector, $token): void
    {
        self::$auth->canResetPasswordOrThrow($selector, $token);
    }
}
