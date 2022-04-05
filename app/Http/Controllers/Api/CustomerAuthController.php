<?php

namespace App\Http\Controllers\Api;

use App\Models\GeneralSetting;
use App\Services\CustomerAuthService;
use App\Http\Requests\CustomerAuthRequest;

class CustomerAuthController extends ApiController
{

    protected $auth_service;

    public function __construct()
    {
        $this->auth_service = new CustomerAuthService;
    }

    public function login(CustomerAuthRequest $request)
    {
        $data = $this->auth_service->login($request->validated());

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
        if (auth('customer')->check()) {
            auth('customer')->user()->token()->revoke();
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

    public function register(CustomerAuthRequest $request)
    {
        $data = $this->auth_service->register($request->validated());

        if ($data['status'] != config('staticdata.status_codes.ok')) {
            if ($data['status'] == config('staticdata.status_codes.validation_failed')) {
                if (request()->header('mobile')) {
                    return $this->formatErrorResponse(
                        [$data['message']],
                        $data['status'],
                        $data['http_code']
                    );
                } else {
                    return $this->formatValidationResponse(
                        [
                            'mobile' => $data['message']
                        ],
                        $data['status'],
                        $data['http_code']
                    );
                }
            } else {
                return $this->formatErrorResponse(
                    [$data['message']],
                    $data['status'],
                    $data['http_code']
                );
            }
        }
        return $this->formatDataResponse(
            $data['message'],
            $data['status'],
            $data['http_code']
        );
    }

    //use for account verification and forget password
    public function sendOTP(CustomerAuthRequest $request)
    {
        $data = $this->auth_service->sendOTP($request->validated());

        if ($data['status'] != config('staticdata.status_codes.ok')) {
            return $this->formatErrorResponse(
                [$data['message']],
                $data['status'],
                $data['http_code']
            );
        }

        //for verify password
        return $this->formatDataResponse(
            $data['message'],
            $data['status'],
            $data['http_code']
        );
    }

    //use for account verification and forget password
    public function verifyOTP(CustomerAuthRequest $request)
    {
        $data = $this->auth_service->verifyOTP($request->validated());

        if ($data['status'] != config('staticdata.status_codes.ok')) {
            if ($data['status'] == config('staticdata.status_codes.validation_failed')) {
                if (request()->header('mobile')) {
                    return $this->formatErrorResponse(
                        [$data['message']],
                        $data['status'],
                        $data['http_code']
                    );
                } else {
                    return $this->formatValidationResponse(
                        [
                            'otp' => $data['message']
                        ],
                        $data['status'],
                        $data['http_code']
                    );
                }
            } else {
                return $this->formatErrorResponse(
                    [$data['message']],
                    $data['status'],
                    $data['http_code']
                );
            }
        }

        //for forget password
        if (is_array($data['message'])) {
            return $this->formatDataResponse(
                $data['message'],
                $data['status'],
                $data['http_code']
            );
        }

        //for verify password
        return $this->formatGeneralResponse(
            $data['message'],
            $data['status'],
            $data['http_code']
        );
    }

    public function reset(CustomerAuthRequest $request)
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
        $user = request()->user('customer');
        $userArray = $user->toArray();

        $otpSettings = GeneralSetting::where('name', 'otp_bypass')->first();

        //middleware should already performed necessary check
        return $this->formatDataResponse(
            [
                'user' => $userArray,
                'otp_bypass' => (empty($otpSettings) || !empty($otpSettings) && $otpSettings->value == 'false') ? false : true
            ],
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }
}
