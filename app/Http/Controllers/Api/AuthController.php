<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Http\Requests\SetPasswordRequest;

class AuthController extends ApiController
{

    protected $auth_service;

    public function __construct()
    {
        $this->auth_service = new AuthService;
    }

    public function login(LoginRequest $request)
    {
        $data = $this->auth_service->login($request);

        if ($data['status'] != config('staticdata.status_codes.ok')) {
            return $this->formatErrorResponse(
                [$data['message']],
                $data['status'],
                $data['http_code']
            );
        }

        return $this->formatDataResponse(
            $data['message'],
            $data['status'],
            $data['http_code']
        );
    }

    public function logout()
    {
        if (auth()->check()) {
            auth()->user()->token()->revoke();
            return $this->formatGeneralResponse(
                config('messages.authentication.authentication_logout_success'),
                config('staticdata.status_codes.ok'),
                config('staticdata.http_codes.success')
            );
        }

        return $this->formatErrorResponse(
            [config('messages.authentication.authentication_error')],
            config('staticdata.status_codes.authentication_error'),
            config('staticdata.http_codes.unauthorized')
        );
    }

    public function forgot(ForgotPasswordRequest $request)
    {
        $data = $this->auth_service->forgot($request);

        if ($data['status'] != config('staticdata.status_codes.ok')) {
            return $this->formatErrorResponse(
                [$data['message']],
                $data['status'],
                $data['http_code']
            );
        }

        return $this->formatGeneralResponse(
            $data['message'],
            $data['status'],
            $data['http_code']
        );
    }

    public function reset(SetPasswordRequest $request)
    {
        $data = $this->auth_service->reset($request->all());

        if ($data['status'] != config('staticdata.status_codes.ok')) {
            return $this->formatErrorResponse(
                [$data['message']],
                $data['status'],
                $data['http_code']
            );
        }

        return $this->formatGeneralResponse(
            $data['message'],
            $data['status'],
            $data['http_code']
        );
    }

    public function authCheck()
    {
        $user = auth()->user();
        $data = $this->auth_service->authCheck($user);

        if ($data == config('messages.authentication.user_location_disabled')) {
            return $this->formatErrorResponse(
                [config('messages.authentication.user_location_disabled')],
                config('staticdata.status_codes.authentication_error'),
                config('staticdata.http_codes.unauthorized')
            );
        }

        //middleware should already performed necessary check
        return $this->formatDataResponse(
            ['user' => $data],
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }
}
