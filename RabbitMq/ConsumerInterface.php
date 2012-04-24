<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    function execute(AMQPMessage $msg);
}

