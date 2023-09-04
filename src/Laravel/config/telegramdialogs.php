<?php declare(strict_types=1);

return [
    /**
     * Stores to store Dialog states.
     */
    'stores' => [
        'redis' => [
            'connection' => env('TELEGRAM_DIALOGS_REDIS_CONNECTION', 'default'),
        ],
    ],
];
