<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiRequest;

class SetPasswordRequest extends ApiRequest
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
        $currentRoute = $this->route()->getName();

        if ($currentRoute == 'api.admin.password.set' || $currentRoute == 'api.admin.password.reset') {
            $rules['password'] = 'required|string|confirmed|between:8,12';
            $rules['email'] = 'required|email';
            $rules['token'] = 'required|string';
        }

        return $rules;
    }

    //overide function to add in id for validation
    public function all($keys = null)
    {
        $data = parent::all();
        if ($this->route('id')) {
            $data['id'] = $this->route('id');
        }

        return $data;
    }
}
