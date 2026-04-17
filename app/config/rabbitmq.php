<?php

return [
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'file-events'),
    'queue' => env('RABBITMQ_QUEUE', 'file-deletion-notifications'),
    'routing_key' => env('RABBITMQ_ROUTING_KEY', 'file.deleted'),
    'notification_email' => env('FILE_DELETION_NOTIFICATION_EMAIL', 'notifications@example.com'),
];
