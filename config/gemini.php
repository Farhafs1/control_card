<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | Used to authenticate with the Gemini API. Find your key at
    | https://aistudio.google.com/app/apikey.
    */

    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Model Name
    |--------------------------------------------------------------------------
    |
    | We specify the model here. 'gemini-1.5-flash' is recommended for 
    | speed and high-volume reporting (like your 15-slide PPT).
    */

    'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite-preview'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Base URL
    |--------------------------------------------------------------------------
    */
    
    'base_url' => env('GEMINI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | For large 15-slide reports, we increase this to 60 seconds to ensure
    | the AI has enough time to generate the full text.
    */

    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 60),
];