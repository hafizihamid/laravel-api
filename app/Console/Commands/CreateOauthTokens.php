<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use App\Models\User;

class CreateOauthTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:token {scope?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Oauth token with scope.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $scopes = config('staticdata.token_scopes');

        // find admin user
        $user = User::where('email', config('staticdata.user_credential.superadmin.email'))->first();
        if (!$user) {
            $this->error('Could not find application user.');
            return;
        }

        // create personal access client for admin if there is none
        $client = Client::where('user_id', $user->id)->first();
        if (!$client) {
            $client_repo = new ClientRepository;
            $client = $client_repo->createPersonalAccessClient(
                $user->id,                             // user id
                $user->name,                          // name
                env('APP_URL') . '/callback'             // callback
            );
        }
        Passport::personalAccessClient($client->id);

        // if input is not valid
        $scope = $this->argument('scope');
        if ($scope && !in_array($scope, $scopes)) {
            $message = 'Invalid scope. Please enter one of the following: ';
            foreach ($scopes as $scope) {
                $message .= PHP_EOL . $scope;
            }
            $this->error($message);
            return;
        }

        if (in_array($scope, $scopes)) {
            \DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->where('client_id', $client->id)
                ->where('name', $scope)
                ->update(['revoked' => 1]);

            $token = $user->createToken($scope, [$scope])->accessToken;
            $this->info('Token for ' . $scope . ': ' . PHP_EOL . $token);
        } else { // re-create all tokens
            // revoke any old tokens
            \DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->where('client_id', $client->id)
                ->whereIn('name', $scopes)
                ->update(['revoked' => 1]);

            foreach ($scopes as $scope) {
                $token = $user->createToken($scope, [$scope])->accessToken;
                $this->info('Token for ' . $scope . ': ' . PHP_EOL . $token);
                $this->info(PHP_EOL);
            }
        }
    }
}
