<?php

return [
    'accounts' => [
        [
            'provider' => 'Airtel Money',
            'number' => env('AIRTEL_NUMBER', '0678 XXX XXX'),
            'instructions' => 'Send via Airtel Money to the number above',
        ],
        [
            'provider' => 'Mixx By Yas',
            'number' => env('MIXX_NUMBER', '0678 XXX XXX'),
            'instructions' => 'Send via Mixx to the number above',
        ],
        [
            'provider' => 'M-Pesa',
            'number' => env('MPESA_NUMBER', '0714 XXX XXX'),
            'instructions' => 'Send via M-Pesa to the number above',
        ],
        [
            'provider' => 'Halopesa',
            'number' => env('HALOPESA_NUMBER', '0622 XXX XXX'),
            'instructions' => 'Send via Halopesa to the number above',
        ],
        [
            'provider' => 'NMB',
            'number' => env('NMB_ACCOUNT', 'XXXXXXX'),
            'instructions' => 'Bank transfer to NMB account',
        ],
        [
            'provider' => 'CRDB',
            'number' => env('CRDB_ACCOUNT', 'XXXXXXX'),
            'instructions' => 'Bank transfer to CRDB account',
        ],
        [
            'provider' => 'NBC',
            'number' => env('NBC_ACCOUNT', 'XXXXXXX'),
            'instructions' => 'Bank transfer to NBC account',
        ],
    ],
];
