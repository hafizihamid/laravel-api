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


        if ($routeName == 'api.login') {
            $rules['password'] = 'required';
            $rules['firebase_token'] = 'nullable|string|max:255';
        }

        return $rules;
    }
}
