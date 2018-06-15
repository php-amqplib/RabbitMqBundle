<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Exception;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

/**
 * If this exception is thrown in consumer service the message
 * will not be ack and consumer will stop
 * if using demonized, ex: supervisor, the consumer will actually restart
 * Class StopConsumerException
 * @package OldSound\RabbitMqBundle\RabbitMq\Exception
 */
class StopConsumerException extends \RuntimeException
{
    public function getHandleCode()
    {
        return ConsumerInterface::MSG_SINGLE_NACK_REQUEUE;
    }

}

