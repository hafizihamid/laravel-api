<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs;

    protected $status_code;
    protected $http_code;

    public function __construct()
    {
        $this->status_code = config('staticdata.status_codes');
        $this->http_code = config('staticdata.http_codes');
    }

    public function formatPaginatedDataResponse($message, $status_code, $http_code)
    {
        $response = [
            'status_code' => $status_code,
        ];
        $response = array_merge($response, $message->toArray());

        return response()->json($response, $http_code);
    }

    public function formatDataResponse($message, $status_code, $http_code)
    {
        $response = [
            'status_code' => $status_code,
            'data' => $message
        ];

        return response()->json($response, $http_code);
    }

    public function formatGeneralResponse($message, $status_code, $http_code)
    {
        $response = [
            'status_code' => $status_code,
            'message' => $message
        ];

        return response()->json($response, $http_code);
    }

    public function formatErrorResponse($data, $status_code = null, $http_response = null)
    {
        $status_code = $status_code ?? config('staticdata.status_codes.error');
        $http_response = $http_response ?? config('staticdata.http_codes.internal_server_error');

        $message = [
            'status_code' => $status_code,
            'errors' => $data,
        ];

        return response()->json($message, $http_response);
    }

    public function formatResourceResponse($data, $user_id = null, $http_response = null, $message = null)
    {
        // http response will be OK by default
        $status_code = $status_code ?? config('staticdata.status_codes.ok');
        $http_response = $http_response ?? config('staticdata.http_codes.success');

        $response = [
            'status_code' => $status_code,
            'data' => $data
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($user_id) {
            $response['user_id'] = $user_id;
        }

        return response()->json($response, $http_response);
    }

    public function formatValidationResponse($data, $status_code = null, $http_response = null)
    {
        $status_code = $status_code ?? config('staticdata.status_codes.validation_failed');
        $http_response = $http_response ?? config('staticdata.http_codes.unprocessable_entity');

        $message = [
            'status_code' => $status_code,
            'detail' => config('messages.general.validation_failed'),
            'field' => $data
        ];

        return response()->json($message, $http_response);
    }
}
