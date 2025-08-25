<?php

return [
    'GET' => [
        'ok' => ['ok' => true],
        'dynamic' => fn () => ['dyn' => 'ok'],
        'jsonString' => '{"a":1}',
        'textString' => 'hello',
        // Error profile for file-based error simulation
        'errorProfile' => [
            'error_rate' => 100,
            'errors' => [
                422 => ['message' => 'Unprocessable'],
            ],
        ],
    ],
];
