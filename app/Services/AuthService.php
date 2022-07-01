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
    
        $user->save();
        return $this->formatGeneralResponse(
            $data,
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }
}
