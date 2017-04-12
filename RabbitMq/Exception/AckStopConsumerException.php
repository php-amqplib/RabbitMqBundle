<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Exception;


use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class AckStopConsumerException extends StopConsumerException
{
    public function getHandleCode()
    {
        return ConsumerInterface::MSG_ACK;
    }

}
