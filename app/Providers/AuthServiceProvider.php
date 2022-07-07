<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        Passport::routes();
        foreach (config('staticdata.token_scopes') as $scope) {
            $scopes[$scope] = '';
        }
        Passport::tokensCan($scopes);

        // modify the lifetime according to project needs
        Passport::tokensExpireIn(now()->addYears(1));
        Passport::refreshTokensExpireIn(now()->addYears(1));
        Passport::personalAccessTokensExpireIn(now()->addYears(1));
    }
}
