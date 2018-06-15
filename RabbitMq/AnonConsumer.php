<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Connection\AbstractConnection;

class AnonConsumer extends Consumer
{
    public function __construct(AbstractConnection $conn)
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
