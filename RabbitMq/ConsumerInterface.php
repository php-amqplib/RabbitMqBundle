<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    public function execute(AMQPMessage $msg);
}
