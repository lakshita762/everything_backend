<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Data Loading Limits
    |--------------------------------------------------------------------------
    |
    | These values determine how many items are loaded for each user data type
    | when a user logs in or requests their profile data.
    |
    */

    'limits' => [
        'todos' => env('USER_TODOS_LIMIT', 100),
        'expenses' => env('USER_EXPENSES_LIMIT', 100),
        'location_entries' => env('USER_LOCATION_ENTRIES_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Data Loading on Login
    |--------------------------------------------------------------------------
    |
    | Set to true to automatically load user data when logging in.
    | Set to false to load data only when explicitly requested.
    |
    */

    'load_on_login' => env('LOAD_USER_DATA_ON_LOGIN', true),
];
