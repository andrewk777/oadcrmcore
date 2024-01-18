<?php

return [
    'table' => [
        'editDelete' => [ //action buttons
            [
                'action' => 'edit',
                'text'   => 'Edit'
            ],
            [
                'action'    => 'delete',
                'text'      => 'Delete'
            ]
        ]
    ],
    'password_rules'  => [
        'min:8',             // must be at least 6 characters in length
        'regex:/[a-z]/',      // must contain at least one lowercase letter
        'regex:/[A-Z]/',      // must contain at least one uppercase letter
        'regex:/[0-9]/',      // must contain at least one digit
    ],
    'storageTree' =>
    [
        'tmp'                           => 'app/tmp/',
    ]
];
