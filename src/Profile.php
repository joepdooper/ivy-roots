<?php

namespace Ivy;

use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Exception;
use HTMLPurifier;
use HTMLPurifier_Config;
use Image\Image;
use function urlencode;

class Profile extends Model
{

    protected string $table = 'profiles';
    protected string $path = _BASE_PATH . 'admin/profile';
    protected array $columns = [
        'user_id',
        'username',
        'email',
        'users_image'
    ];

    public int $user_id;
    public string $username;
    public string $email;
    public ?string $users_image;

    private static ?Profile $currentProfile = null;

    public function __construct()
    {
        parent::__construct();
        $this->query = "
    SELECT `profiles`.`id`, `profiles`.`user_id`, `profiles`.`users_image`, `users`.`email`, `users`.`username`, `users`.`status`, `users`.`roles_mask`, `users`.`last_login` FROM `profiles`
    INNER JOIN `users` ON `users`.`id` = `profiles`.`user_id`
    ";
    }

    private static function getCurrentUserProfile(): ?self
    {
        if (self::$currentProfile === null) {
            self::$currentProfile = (new self())->where('user_id', User::getUserId())->fetchOne();
        }
        return self::$currentProfile;
    }

    public static function getUserImage(): ?string
    {
        return self::getCurrentUserProfile()?->users_image;
    }

    public static function getUserName(): ?string
    {
        return self::getCurrentUserProfile()?->username;
    }

    public static function getEmail(): ?string
    {
        return self::getCurrentUserProfile()?->email;
    }

    public static function lastSeen($last_login): string
    {
        $seconds_ago = time() - $last_login;
        if ($seconds_ago >= 31536000) {
            $value = "seen " . intval($seconds_ago / 31536000) . " years ago";
        } elseif ($seconds_ago >= 2419200) {
            $value = "seen " . intval($seconds_ago / 2419200) . " months ago";
        } elseif ($seconds_ago >= 86400) {
            $value = "seen " . intval($seconds_ago / 86400) . " days ago";
        } elseif ($seconds_ago >= 3600) {
            $value = "seen " . intval($seconds_ago / 3600) . " hours ago";
        } elseif ($seconds_ago >= 60) {
            $value = "seen " . intval($seconds_ago / 60) . " minutes ago";
        } else {
            $value = "seen less than a minute ago";
        }
        return $value;
    }

}
