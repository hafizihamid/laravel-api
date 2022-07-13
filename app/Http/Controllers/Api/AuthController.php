<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Services\AuthService;

class AuthController extends ApiController
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new AuthService;
    }

    public function login(LoginRequest $request)
    {
        $data = $this->authService->login($request);

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

    public function forgot(ForgotPasswordRequest $request)
    {
        $data = $this->authService->forgot($request);


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
}
