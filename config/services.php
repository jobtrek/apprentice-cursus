<?php

use App\Enums\AzureGroup;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'azure' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => env('MICROSOFT_TENANT_ID'),
        'account_check_interval' => env('MICROSOFT_ACCOUNT_CHECK_INTERVAL', 900),

        // Graph group object ids, keyed by App\Enums\AzureGroup value.
        'groups' => [
            AzureGroup::ApprenticesIt->value => env('MICROSOFT_GROUP_APPRENTICES_IT'),
            AzureGroup::ApprenticesEc->value => env('MICROSOFT_GROUP_APPRENTICES_EC'),
            // MICROSOFT_GROUP_TRAINER predates the IT/EC split and mapped to IT.
            AzureGroup::TrainerIt->value => env('MICROSOFT_GROUP_TRAINER_IT', env('MICROSOFT_GROUP_TRAINER')),
            AzureGroup::TrainerEc->value => env('MICROSOFT_GROUP_TRAINER_EC'),
            AzureGroup::Coach->value => env('MICROSOFT_GROUP_COACH'),
        ],
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
