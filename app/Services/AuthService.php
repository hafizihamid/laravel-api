<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService extends BaseService
{
    public function login($credentials)
    {
        $user = User::where(['email' => $credentials['email']])->first();
        $now = Carbon::now();

        if (!$user) {
            return $this->formatGeneralResponse(
                config('messages.authentication.invalid_login_credentials'),
                config('staticdata.status_codes.authentication_error'),
                config('staticdata.http_codes.unauthorized')
            );
        }

        if ($user->blocked_until >= $now) {
            return $this->formatGeneralResponse(
                config('messages.authentication.authentication_user_blocked') . $user->blocked_until,
                config('staticdata.status_codes.forbidden'),
                config('staticdata.http_codes.forbidden')
            );
        } elseif ($user->is_disabled) {
            return $this->formatGeneralResponse(
                config('messages.authentication.authentication_user_disabled'),
                config('staticdata.status_codes.forbidden'),
                config('staticdata.http_codes.forbidden')
            );
        }

        if ($user->blocked_until < $now && $user->blocked_until != null) {
            $user->tries = 0;
            $user->blocked_until = null;
            $user->save();
        }
        
        if (auth()->attempt($credentials->all())) {
            $accessToken = $user->createToken('authToken', ['admin'])->accessToken;
            $userDetails = $this->getUserWithRolesPermissions($user);

            $user->tries = 0;

            $data = [
                'user' => $userDetails,
                'accessToken' => $accessToken,
            ];
        } else {
            if ($user->tries < 5) {
                $user->tries += 1;
                $user->save();
                if ($user->tries == 5) {
                    $user->blocked_until = $now->addHour();
                    $user->save();
                    return $this->formatGeneralResponse(
                        config('messages.authentication.authentication_user_blocked') . $user->blocked_until,
                        config('staticdata.status_codes.forbidden'),
                        config('staticdata.http_codes.forbidden')
                    );
                }

                return $this->formatGeneralResponse(
                    config('messages.authentication.invalid_login_credentials'),
                    config('staticdata.status_codes.authentication_error'),
                    config('staticdata.http_codes.unauthorized')
                );
            }
        }

        $user->save();
        return $this->formatGeneralResponse(
            $data,
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }

    public function forgot($credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if ($user) {
            if ($user->is_disabled) {
                return $this->formatGeneralResponse(
                    config('messages.authentication.user_disabled'),
                    config('staticdata.status_codes.forbidden'),
                    config('staticdata.http_codes.forbidden')
                );
            }
        }

        try {
            Password::sendResetLink($credentials->all());
        } catch (\Exception $e) {
            return $this->formatGeneralResponse(
                $e->getMessage(),
                config('staticdata.status_codes.error'),
                config('staticdata.http_codes.internal_server_error')
            );
        }

        return $this->formatGeneralResponse(
            config("messages.authentication.authentication_reset_email_successful"),
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }

    public function getUserWithRolesPermissions($user)
    {
        $userArray = $user->toArray();
        $roles = $user->roles->pluck('name');
        $permissions = ($user->getAllPermissions()->pluck('name'));
        $userArray['roles'] = $roles;
        $userArray['permissions'] = $permissions;

        return $userArray;
    }
}
