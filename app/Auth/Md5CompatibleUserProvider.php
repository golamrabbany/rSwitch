<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class Md5CompatibleUserProvider extends EloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     * Supports MD5 passwords (prefixed with $md5$) with transparent bcrypt upgrade.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return static::checkPassword($credentials['password'], $user);
    }

    /**
     * Check password against stored hash (MD5 or bcrypt).
     * Can be called directly for Hash::check() replacements.
     */
    public static function checkPassword(string $plain, User $user): bool
    {
        $stored = $user->getAuthPassword();

        // Check if password is MD5 (prefixed with $md5$)
        if (str_starts_with($stored, '$md5$')) {
            $md5Hash = substr($stored, 5);

            if (md5($plain) === $md5Hash) {
                // MD5 matched — upgrade to bcrypt transparently
                $user->forceFill([
                    'password' => Hash::make($plain),
                ])->save();

                return true;
            }

            return false;
        }

        // Standard bcrypt check
        return Hash::check($plain, $stored);
    }
}
