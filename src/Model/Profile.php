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
        'user_image',
    ];

    protected int $user_id;
    protected ?string $user_image;

    private static ?Profile $currentProfile = null;

    public function user(): ?User
    {
        return $this->hasOne(User::class, 'id', 'user_id');
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