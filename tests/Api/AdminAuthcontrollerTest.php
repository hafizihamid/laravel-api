<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;

class AdminAuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->user = User::factory()->make();
    }

    public function testLoginSuccess()
    {
        $data = [
            'email' => config('staticdata.user_credential.superadmin.email'),
            'password' => config('staticdata.user_credential.superadmin.password'),
        ];


        $response = $this->post(route('api.admin.login'), $data);

        $response->assertJson(
            [
                'status_code' => config('staticdata.status_codes.ok'),
            ]
        )->assertJsonStructure(
            [
                'status_code',
                'data' => [
                    'user',
                    'accessToken',
                ]
            ]
        );
    }

    public function testLoginValidationFail()
    {
        $response = $this->post(route('api.admin.login'));
        $response->assertJson(
            [
                'status_code' => config('staticdata.status_codes.validation_failed'),
            ]
        );
    }

    public function testLoginFail()
    {
        $data = [
            'email' => "login_failed@gmail.com",
            'password' => "Test1234!",
        ];

        $response = $this->post(route('api.admin.login'), $data);
        $response->assertJson(
            [
                'status_code' => config('staticdata.status_codes.authentication_error'),
                'errors' => [config('messages.authentication.invalid_login_credentials')]
            ]
        );
    }
}
