<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;

class TraceableAMQPChannel extends AMQPChannel
{
    private $tracedPublications = [];

    public function basic_publish($msg, $exchange = '', $routingKey = '', $mandatory = false, $immediate = false, $ticket = NULL)
    {
        $this->tracedPublications[] = [
            'msg' => $msg,
            'exchange' => $exchange,
            'routing_key' => $routingKey,
            'mandatory' => $mandatory,
            'immediate' => $immediate,
            'ticket' => $ticket
        ];

        parent::basic_publish($msg, $exchange, $routingKey, $mandatory, $immediate, $ticket);
    }

    public function getTracedPublications(): array
    {
        return $this->tracedPublications;
    }
}
