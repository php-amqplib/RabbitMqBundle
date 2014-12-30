<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

class BasicConsumer implements ConsumerInterface
{
    /** @var callable */
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function execute(AMQPMessage $message)
    {
        return call_user_func($this->callback, $message);
    }
}
