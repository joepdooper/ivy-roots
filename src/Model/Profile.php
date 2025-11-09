<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;
use Ivy\Manager\DatabaseManager;

class Profile extends Model
{

    protected string $table = 'profiles';
    protected string $path = 'admin/profile';
    protected array $columns = [
        'user_id',
        'username',
        'email',
        'user_image',
        'birthday'
    ];

    protected int $user_id;
    protected string $username;
    protected string $email;
    protected ?string $user_image;
    protected ?string $birthday;

    private static ?Profile $currentProfile = null;

    public function __construct()
    {
        parent::__construct();
        $this->query = "
    SELECT `profiles`.`id`, `profiles`.`user_id`, `profiles`.`user_image`, `profiles`.`birthday`, `users`.`email`, `users`.`username`, `users`.`status`, `users`.`roles_mask`, `users`.`last_login` FROM `profiles`
    INNER JOIN `users` ON `users`.`id` = `profiles`.`user_id`
    ";
    }

    public function update(): static
    {
        $set = $this->toAssocArray();

        unset($set['username']);
        unset($set['email']);

        if(empty($set)){
            return false;
        }

        if (!empty($this->columns)) {
            $set = array_intersect_key($set, array_flip($this->columns));
        }

        if (empty($this->bindings) && isset($this->id)) {
            $this->bindings['id'] = $this->id;
        }

        DatabaseManager::connection()->update($this->table, $set, $this->bindings);

        $this->resetQuery();

        return DatabaseManager::connection()->getLastInsertId();
    }

    public static function getUserProfile(): ?self
    {
        if (self::$currentProfile === null) {
            self::$currentProfile = (new self())->where('user_id', User::getAuth()->getUserId())->fetchOne();
        }
        return self::$currentProfile;
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