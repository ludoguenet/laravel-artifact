<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Model Class
    |--------------------------------------------------------------------------
    |
    | This option allows you to customize the model class used for artifacts.
    | You can extend the default Artifact model and use your own class here.
    |
    */

    'model' => LaravelJutsu\Artifact\Artifact::class,

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | This option allows you to customize the table name used for storing
    | artifacts. By default, it uses 'artifacts'.
    |
    */

    'table_name' => 'artifacts',

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | These options control the routing behavior for artifact access.
    |
    */

    'routes' => [
        /*
         * The prefix for all artifact routes.
         */
        'prefix' => env('ARTIFACT_ROUTE_PREFIX', 'artifacts'),

        /*
         * The middleware to apply to artifact routes.
         */
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed URL Configuration
    |--------------------------------------------------------------------------
    |
    | These options control the behavior of signed URLs for artifact access.
    |
    */

    'signed_url' => [
        /*
         * Default expiration time in minutes for temporary signed URLs.
         */
        'expiration_minutes' => env('ARTIFACT_SIGNED_URL_EXPIRATION', 60),
    ],

];
