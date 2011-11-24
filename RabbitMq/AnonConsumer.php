<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Connection\AMQPConnection;

class AnonConsumer extends Consumer
{
    public function __construct(AMQPConnection $conn)
    {
        parent::__construct($conn);

        $this->setQueueOptions(array(
            'name' => '',
            'passive' => false,
            'durable' => false,
            'exclusive' => true,
            'auto_delete' => true,
            'nowait' => false,
            'arguments' => null,
            'ticket' => null
        ));
    }
}
