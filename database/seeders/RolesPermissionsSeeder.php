<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{

    public function run()
    {

        $permission['users'] = [
            'guard' => 'web',
            'group_display_name' => 'User',
            'permissions' => [
                'view-users' => [
                    'display_name' => 'View Users',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'add-users' => [
                    'display_name' => 'Add Users',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'disable-users' => [
                    'display_name' => 'Disable Users',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'view-users-details' => [
                    'display_name' => 'View Users Details',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'update-users' => [
                    'display_name' => 'Update Users',
                    'role_access' => [
                        'Super Admin'
                    ]
                ]
            ]
        ];

        $permission['roles'] = [
            'guard' => 'web',
            'group_display_name' => 'Roles',
            'permissions' => [
                'list-roles' => [
                    'display_name' => 'List Roles',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'add-roles' => [
                    'display_name' => 'Add Roles',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'update-roles' => [
                    'display_name' => 'Update Roles',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'delete-roles' => [
                    'display_name' => 'Delete Roles',
                    'role_access' => [
                        'Super Admin'
                    ]
                ],
                'view-role-details' => [
                    'display_name' => 'View Role Details',
                    'role_access' => [
                        'Super Admin'
                    ]
                ]
            ],
        ];

        $permission['permissions'] = [
            'guard' => 'web',
            'group_display_name' => 'Permissions',
            'permissions' => [
                'list-permissions' => [
                    'display_name' => 'List Permissions',
                    'role_access' => [
                        'Super Admin'
                    ]
                ]
            ]
        ];

        $roles = [
            'Super Admin' => null,
        ];

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        //Create super admin role and assign first user as super admin
        if (!Role::where('name', 'Super Admin')->first()) {
            $role = Role::create(['name' => 'Super Admin']);
            $user = User::where('email', config("staticdata.user_credential.superadmin.email"))->first();
            if ($user) {
                $user->assignRole($role);
            }
        }

        foreach (array_keys($roles) as $key) {
            $roles[$key] = Role::findByName($key);
        }

        foreach ($permission as $group => $group_details) {
            foreach ($group_details['permissions'] as $permission_name => $permission_details) {
                $permission = Permission::updateOrCreate(
                    [
                        'name' => $permission_name
                    ],
                    [
                        'guard_name' => $group_details['guard'],
                        'display_name' => $permission_details['display_name'],
                        'group' => $group,
                        'group_display_name' => $group_details['group_display_name']
                    ]
                );

                foreach ($permission_details['role_access'] as $role_name) {
                    $roles[$role_name]->givePermissionTo($permission);
                }
            }
        }
    }
}
