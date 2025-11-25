<?php

return [
    'default_driver' => '\\App\\Services\\Payments\\Drivers\\LocalDummyGateway',
    'default_currency' => 'NPR',
    'default_locale' => 'en_NP',
    'timeout' => 60,
    'drivers' => [
        '\\App\\Services\\Payments\\Drivers\\LocalDummyGateway' => [
            'label' => 'NutriNexus Local Sandbox',
            'fields' => ['token']
        ],
        'Stripe' => [
            'label' => 'Stripe',
            'fields' => ['apiKey', 'publishableKey'],
            'docs' => 'https://stripe.com/docs/keys'
        ],
        'PayPal_Rest' => [
            'label' => 'PayPal REST',
            'fields' => ['clientId', 'secret'],
            'docs' => 'https://developer.paypal.com/api/rest/'
        ],
        'Mollie' => [
            'label' => 'Mollie',
            'fields' => ['apiKey']
        ],
        'Square' => [
            'label' => 'Square',
            'fields' => ['accessToken', 'locationId']
        ]
    ]
];

