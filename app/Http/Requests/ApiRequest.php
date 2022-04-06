<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;

abstract class ApiRequest extends LaravelFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    abstract public function authorize();
    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();

        foreach ($errors as $key => $val) {
            $error_list[$key] = $val[0];
        }

        if (request()->header('mobile')) {
            $errors = collect($errors)->flatten()->values();
            $response = [
                'status_code' => config('staticdata.status_codes.validation_failed'),
                'errors' => $errors
            ];
        } else {
            $response = [
                'status_code' => config('staticdata.status_codes.validation_failed'),
                'detail' => config('messages.general.validation_failed'),
                'field' => $error_list
            ];
        }

        throw new HttpResponseException(
            response()->json($response, config('staticdata.http_codes.unprocessable_entity'))
        );
    }
}
