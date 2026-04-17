<?php

namespace App\Contracts;

interface PublishesDeletionNotifications
{
    public function publish(array $payload): bool;
}
