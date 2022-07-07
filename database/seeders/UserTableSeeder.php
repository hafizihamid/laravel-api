<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => config('staticdata.user_credential.superadmin.name'),
                'email' => config('staticdata.user_credential.superadmin.email'),
                'password' => bcrypt(config('staticdata.user_credential.superadmin.password'))
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                [
                    'email' => $user['email']
                ],
                [
                    'name' => $user['name'],
                    'password' => $user['password']
                ]
            );
        }
    }
}
