<?php

declare(strict_types=1);

return [

    'models' => [

        /*
        |--------------------------------------------------------------------------
        | Permission Model
        |--------------------------------------------------------------------------
        |
        | The Eloquent model that should be used for permissions.
        |
        */

        'permission' => Spatie\Permission\Models\Permission::class,

        /*
        |--------------------------------------------------------------------------
        | Role Model
        |--------------------------------------------------------------------------
        |
        | The Eloquent model that should be used for roles.
        |
        */

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [

        /*
        |--------------------------------------------------------------------------
        | Table Names
        |--------------------------------------------------------------------------
        |
        | The database table names used by Spatie Permission.
        |
        */

        'roles' => 'roles',

        'permissions' => 'permissions',

        'model_has_permissions' => 'model_has_permissions',

        'model_has_roles' => 'model_has_roles',

        'role_has_permissions' => 'role_has_permissions',

    ],

    'column_names' => [

        /*
        |--------------------------------------------------------------------------
        | Model Morph Key
        |--------------------------------------------------------------------------
        |
        | The primary key column name of your model when using morph relations.
        |
        */

        'model_morph_key' => 'model_id',

        'team_foreign_key' => 'team_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register Permission Check Method
    |--------------------------------------------------------------------------
    |
    | Whether to register the "can" check method on the Gate.
    |
    */

    'register_permission_check_method' => true,

    /*
    |--------------------------------------------------------------------------
    | Teams Feature
    |--------------------------------------------------------------------------
    |
    | Set to true to enable team-based permissions.
    |
    */

    'teams' => false,

    /*
    |--------------------------------------------------------------------------
    | Passport/Sanctum Client Credentials Support
    |--------------------------------------------------------------------------
    |
    | Set to true if you are using Passport Client Credentials Grant.
    |
    */

    'passport_client_credentials' => false,

    /*
    |--------------------------------------------------------------------------
    | Display Permission in Exception
    |--------------------------------------------------------------------------
    |
    | Set to true to display the missing permission name in exceptions.
    |
    */

    'display_permission_in_exception' => false,

    /*
    |--------------------------------------------------------------------------
    | Display Role in Exception
    |--------------------------------------------------------------------------
    |
    | Set to true to display the missing role name in exceptions.
    |
    */

    'display_role_in_exception' => false,

    /*
    |--------------------------------------------------------------------------
    | Cache configuration
    |--------------------------------------------------------------------------
    |
    | Configure how and where permissions and roles are cached.
    |
    */

    'cache' => [

        'expiration_time' => \DateInterval::createFromDateString('24 hours'),

        'key' => 'spatie.permission.cache',

        'store' => 'default',

    ],
];
