<?php

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

        // Placeholders in {braces} are filled with str_replace / Str::swap by the caller.
        'endpoints' => [
            'token' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token',
            'scope' => 'https://graph.microsoft.com/.default',
            'user' => 'https://graph.microsoft.com/v1.0/users/{user}',
            'user_groups' => 'https://graph.microsoft.com/v1.0/users/{user}/transitiveMemberOf',
            'group' => 'https://graph.microsoft.com/v1.0/groups/{group}',
            'group_members' => 'https://graph.microsoft.com/v1.0/groups/{group}/transitiveMembers',
        ],

        'group_roles' => array_filter([
            env('MICROSOFT_GROUP_APPRENTICES_IT') => 'apprentices_IT',
            env('MICROSOFT_GROUP_APPRENTICES_EC') => 'apprentices_EC',
            env('MICROSOFT_GROUP_TRAINER') => 'trainer',
        ], fn ($role, $id) => filled($id), ARRAY_FILTER_USE_BOTH),
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
