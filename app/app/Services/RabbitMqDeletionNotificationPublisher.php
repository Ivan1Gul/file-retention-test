<?php

namespace App\Services;

use App\Contracts\PublishesDeletionNotifications;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class RabbitMqDeletionNotificationPublisher implements PublishesDeletionNotifications
{
    public function publish(array $payload): bool
    {
        $config = config('rabbitmq');
        $exchange = (string) $config['exchange'];
        $queue = (string) $config['queue'];
        $routingKey = (string) $config['routing_key'];

        try {
            $connection = new AMQPStreamConnection(
                host: (string) $config['host'],
                port: (int) $config['port'],
                user: (string) $config['user'],
                password: (string) $config['password'],
                vhost: (string) $config['vhost'],
            );

            $channel = $connection->channel();
            $channel->queue_declare($queue, false, true, false, false);

            if ($exchange !== '') {
                $channel->exchange_declare($exchange, 'direct', false, true, false);
                $channel->queue_bind($queue, $exchange, $routingKey);
            }

            $channel->basic_publish(
                new AMQPMessage(
                    json_encode($payload, JSON_THROW_ON_ERROR),
                    [
                        'content_type' => 'application/json',
                        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    ],
                ),
                $exchange,
                $exchange === '' ? $queue : $routingKey,
            );

            $channel->close();
            $connection->close();

            return true;
        } catch (AMQPIOException|AMQPRuntimeException|Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
