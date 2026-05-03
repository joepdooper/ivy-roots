<?php

namespace Ivy\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ivy\Service\AuthService;
use Ivy\Trait\HasPolicies;

class Profile extends Model
{
    use HasPolicies;

    private static ?Profile $currentProfile = null;

    protected $fillable = [
        'user_id',
        'user_image',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Resolve profile for current authenticated user
     */
    public static function getUserProfile(AuthService $auth): ?self
    {
        $userId = $auth->auth()->getUserId();

        if (!$userId) {
            return null;
        }

        return self::where('user_id', $userId)->first();
    }

    public static function lastSeen(int $last_login): string
    {
        $seconds_ago = time() - $last_login;

        if ($seconds_ago >= 31536000) {
            return 'seen ' . intval($seconds_ago / 31536000) . ' years ago';
        }

        if ($seconds_ago >= 2419200) {
            return 'seen ' . intval($seconds_ago / 2419200) . ' months ago';
        }

        if ($seconds_ago >= 86400) {
            return 'seen ' . intval($seconds_ago / 86400) . ' days ago';
        }

        if ($seconds_ago >= 3600) {
            return 'seen ' . intval($seconds_ago / 3600) . ' hours ago';
        }

        if ($seconds_ago >= 60) {
            return 'seen ' . intval($seconds_ago / 60) . ' minutes ago';
        }

        return 'seen less than a minute ago';
    }
}