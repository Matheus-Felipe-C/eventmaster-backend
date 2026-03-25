<?php

$appUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/');
$defaultSuccessUrl = $appUrl.'/checkout/success';
$defaultFailureUrl = $appUrl.'/checkout/failure';
$defaultPendingUrl = $appUrl.'/checkout/pending';

return [

    /*
    |--------------------------------------------------------------------------
    | Access token
    |--------------------------------------------------------------------------
    |
    | Production or test access token from Mercado Pago (Credentials).
    | See: https://www.mercadopago.com.br/developers/pt/reference
    |
    */

    'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | SDK runtime
    |--------------------------------------------------------------------------
    |
    | Use "local" when testing with sandbox credentials on localhost.
    |
    */

    'runtime' => env('MERCADO_PAGO_RUNTIME', 'server'),

    /*
    |--------------------------------------------------------------------------
    | Checkout Pro return URLs
    |--------------------------------------------------------------------------
    |
    | Where the payer is redirected after Checkout Pro (frontend URLs).
    |
    */

    'back_urls' => [
        // If env var is empty, fallback to production-ready web endpoints in this backend.
        'success' => env('MERCADO_PAGO_BACK_SUCCESS_URL') ?: $defaultSuccessUrl,
        'failure' => env('MERCADO_PAGO_BACK_FAILURE_URL') ?: $defaultFailureUrl,
        'pending' => env('MERCADO_PAGO_BACK_PENDING_URL') ?: $defaultPendingUrl,
    ],

    'auto_return' => env('MERCADO_PAGO_AUTO_RETURN', 'approved'),

    'statement_descriptor' => env('MERCADO_PAGO_STATEMENT_DESCRIPTOR', 'EVENTMASTER'),

    'notification_url' => env('MERCADO_PAGO_NOTIFICATION_URL'),

];
