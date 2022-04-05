<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiRequest;

class CustomerAuthRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $routeName = $this->route()->getName();

        $rules['mobile'] = 'required|min:9|max:11|regex:/[0-9]/';

        if ($routeName == 'api.login') {
            $rules['password'] = 'required';
            $rules['firebase_token'] = 'nullable|string|max:255';
        }

        if ($routeName == 'api.register') {
            $rules['mobile'] .= "|unique:App\Models\UserCustomer";
            $rules['password'] = 'required';
            $rules['firebase_token'] = 'nullable|string|max:255';
        }

        if ($routeName == 'api.sendOTP') {
            $rules['mobile'] .= "|exists:App\Models\UserCustomer";
        }

        if ($routeName == 'api.verifyOTP') {
            $rules['mobile'] .= "|exists:App\Models\UserCustomer";
            $rules['otp'] = "required|size:6";
        }

        if ($routeName == 'api.reset') {
            $rules['password'] = 'required|string|confirmed|between:8,12';
            $rules['mobile'] .= "|exists:App\Models\UserCustomer";
            $rules['token'] = 'required|string';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'mobile.unique' => config('messages.authentication.mobile_user_exist'),
        ];
    }
}
