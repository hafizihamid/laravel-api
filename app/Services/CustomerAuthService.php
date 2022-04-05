<?php

namespace App\Services;

use App\Models\SMSLog;
use App\Models\UserCustomer;
use App\Models\ApplicantDetails;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Services\BookingService;
use Illuminate\Support\Facades\Http;

class CustomerAuthService extends BaseService
{
    public function login($credentials)
    {
        $phoneValidator = new PhoneValidationService;
        $phoneValidation = $phoneValidator->validate('+' . config('staticdata.country_code') . $credentials['mobile']);

        if ($phoneValidation['valid'] != 'True') {
            return $this->formatGeneralResponse(
                config('messages.general.phone_validation_failed'),
                config('staticdata.status_codes.phone_validation_failed'),
                config('staticdata.http_codes.unprocessable_entity')
            );
        }

        //use formatted number
        $credentials['mobile'] =  str_replace('+' . config('staticdata.country_code'), '', $phoneValidation['formattedNo']);

        $user = UserCustomer::where(['mobile' => $credentials['mobile']])->first();

        $firebase_token = $credentials['firebase_token'] ?? null;

        $now = Carbon::now();

        if (!$user) {
            if (request()->header('mobile')) {
                return $this->formatGeneralResponse(
                    config('messages.authentication.invalid_login_credentials'),
                    config('staticdata.status_codes.authentication_error'),
                    config('staticdata.http_codes.forbidden')
                );
            } else {
                return $this->formatGeneralResponse(
                    config('messages.authentication.invalid_login_credentials'),
                    config('staticdata.status_codes.authentication_error'),
                    config('staticdata.http_codes.unauthorized')
                );
            }
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

        $authTokens = ['customer'];

        if (Hash::check($credentials['password'], $user->password)) {
            $accessToken = $user->createToken('authToken', ['customer'])->accessToken;
            $userDetails = $user->toArray();
            unset($userDetails['firebase_token']);

            $user->tries = 0;
            $user->firebase_token = $firebase_token;

            $otpSettings = GeneralSetting::where('name', 'otp_bypass')->first();

            $data = [
                'user' => $userDetails,
                'accessToken' => $accessToken,
                'otp_bypass' => (empty($otpSettings) || !empty($otpSettings) && $otpSettings->value == 'false') ? false : true
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

                if (request()->header('mobile')) {
                    return $this->formatGeneralResponse(
                        config('messages.authentication.invalid_login_credentials'),
                        config('staticdata.status_codes.authentication_error'),
                        config('staticdata.http_codes.forbidden')
                    );
                } else {
                    return $this->formatGeneralResponse(
                        config('messages.authentication.invalid_login_credentials'),
                        config('staticdata.status_codes.authentication_error'),
                        config('staticdata.http_codes.unauthorized')
                    );
                }
            }
        }

        $user->save();
        return $this->formatGeneralResponse(
            $data,
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }

    public function register($credentials)
    {
        $phoneValidator = new PhoneValidationService;
        $phoneValidation = $phoneValidator->validate('+' . config('staticdata.country_code') . $credentials['mobile']);

        if ($phoneValidation['valid'] != 'True') {
            return $this->formatGeneralResponse(
                config('messages.general.phone_validation_failed'),
                config('staticdata.status_codes.phone_validation_failed'),
                config('staticdata.http_codes.unprocessable_entity')
            );
        }

        //use formatted number
        $credentials['mobile'] =  str_replace('+' . config('staticdata.country_code'), '', $phoneValidation['formattedNo']);

        if (UserCustomer::where('mobile', $credentials['mobile'])->first()) {
            return $this->formatGeneralResponse(
                config('messages.general.mobile_has_already_registered'),
                config('staticdata.status_codes.validation_failed'),
                config('staticdata.http_codes.unprocessable_entity')
            );
        }

        $credentials['password'] = Hash::make($credentials['password']);

        $user = UserCustomer::create($credentials);
        unset($user['firebase_token']);

        $otpSettings = GeneralSetting::where('name', 'otp_bypass')->first();

        $data = [
            'user' => $user->toArray(),
            'accessToken' => $user->createToken('authToken', ['customer'])->accessToken,
            'otp_bypass' => (empty($otpSettings) || !empty($otpSettings) && $otpSettings->value == 'false') ? false : true
        ];

        return $this->formatGeneralResponse(
            $data,
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }

    public function sendOTP($credentials)
    {
        $phoneValidator = new PhoneValidationService;
        $phoneValidation = $phoneValidator->validate('+' . config('staticdata.country_code') . $credentials['mobile']);

        if ($phoneValidation['valid'] != 'True') {
            return $this->formatGeneralResponse(
                config('messages.general.phone_validation_failed'),
                config('staticdata.status_codes.phone_validation_failed'),
                config('staticdata.http_codes.unprocessable_entity')
            );
        }
        $smsService = new SMSService;
        $smsLog = SMSLog::where('destination', str_replace('+', '', $phoneValidation['formattedNo']))
            ->where('type', 'otp')
            ->where('created_at', '>', Carbon::now()->subSeconds(config('staticdata.throttle.otp.decay')))
            ->orderBy('created_at', 'desc')
            ->get();

        $count = $smsLog->count();
        $attemptMessage = "";

        //if user, comes from verify account.
        $user = request()->user('customer');
        if ($user) {
            if ($user->mobile != $credentials['mobile']) {
                return $this->formatGeneralResponse(
                    config('messages.general.validation_failed'),
                    config('staticdata.status_codes.validation_failed'),
                    config('staticdata.http_codes.bad_request')
                );
            }
            if ($user->verified) {
                return $this->formatGeneralResponse(
                    config('messages.authentication.user_already_verified'),
                    config('staticdata.status_codes.validation_failed'),
                    config('staticdata.http_codes.bad_request')
                );
            }
        } else {
            $user = UserCustomer::where('mobile', $credentials['mobile'])->first();
        }

        if ($count) {
            //attempt limit exceeded
            if ($count >= config("staticdata.throttle.otp.attempt")) {
                return $this->formatGeneralResponse(
                    config('messages.authentication.otp_limit_exceeded'),
                    config('staticdata.status_codes.limit_exceeded'),
                    config('staticdata.http_codes.bad_request')
                );
            }

            //request too fast...
            $diff = Carbon::now()->diffInSeconds(Carbon::parse($smsLog->first()->created_at));
            if ($diff < config("staticdata.throttle.otp.wait_interval")) {
                return $this->formatGeneralResponse(
                    config('messages.authentication.otp_limit_interval'),
                    config('staticdata.status_codes.limit_exceeded'),
                    config('staticdata.http_codes.bad_request')
                );
            }

            $attemptMessage = $count > 0 ? " Resend $count of " . (config("staticdata.throttle.otp.attempt") - 1) : '';
        }

        $otpNumber = substr(mt_rand(1000000, 9999999), 1);

        if (config('staticdata.overwrite_sms_otp')) {
            $otpNumber = config('staticdata.overwrite_sms_otp');
        }

        //store OTP
        $user->otp = $otpNumber;
        $user->save();
        if (!config('staticdata.overwrite_sms_otp')) {
            $smsService->sendOTPSMS($phoneValidation['formattedNo'], $otpNumber, $attemptMessage);
        }

        return $this->formatGeneralResponse(
            ['attempt' => $count + 1],
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }

    public function verifyOTP($credentials)
    {
        //if user, comes from verify account.
        $isForgot = false;
        $user = request()->user('customer');
        if ($user) {
            if ($user->mobile != $credentials['mobile']) {
                return $this->formatGeneralResponse(
                    config('messages.general.validation_failed'),
                    config('staticdata.status_codes.validation_failed'),
                    config('staticdata.http_codes.bad_request')
                );
            }
        } else {
            $user = UserCustomer::where('mobile', $credentials['mobile'])->first();
            $isForgot = true;
        }

        $userOtp = $user->otp;
        //clear otp
        $user->otp = null;
        $user->save();

        if ($credentials['otp'] != $userOtp || !$userOtp) {
            return $this->formatGeneralResponse(
                config('messages.authentication.otp_invalid'),
                config('staticdata.status_codes.validation_failed'),
                config('staticdata.http_codes.bad_request')
            );
        }

        $user->markEmailAsVerified();

        if ($isForgot) {
            $data = [
                'resetToken' => Password::createToken($user),
                'mobile' => $user->mobile
            ];
            return $this->formatGeneralResponse(
                $data,
                config('staticdata.status_codes.ok'),
                config('staticdata.http_codes.success')
            );
        }

        return $this->formatGeneralResponse(
            config('messages.authentication.otp_verified_success'),
            config('staticdata.status_codes.ok'),
            config('staticdata.http_codes.success')
        );
    }

    public function reset($credentials)
    {
        $userInstance = null;

        $passwordBroker = Password::broker('customer');

        $resetPasswordStatus = $passwordBroker->reset(
            $credentials,
            function ($user, $password) use (&$userInstance) {
                $user->password = Hash::make($password);
                $user->save();
                $userInstance = $user;
            }
        );

        if ($resetPasswordStatus == Password::PASSWORD_RESET) {
            //revoke all token for safety reason
            if ($userInstance instanceof \App\Models\UserCustomer) {
                $userTokens = $userInstance->tokens;
                foreach ($userTokens as $token) {
                    $token->revoke();
                }
            }

            return $this->formatGeneralResponse(
                config("messages.authentication.authentication_reset_successful"),
                config('staticdata.status_codes.ok'),
                config('staticdata.http_codes.success')
            );
        } else {
            return $this->formatGeneralResponse(
                config("messages.authentication.authentication_reset_invalid_token"),
                config('staticdata.status_codes.authentication_error'),
                config('staticdata.http_codes.bad_request')
            );
        }
    }
}
