<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Exception;
use OldSound\RabbitMqBundle\RabbitMq\ReceiverInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * If this exception is thrown in consumer service the message
 * will not be ack and consumer will stop
 * if using demonized, ex: supervisor, the consumer will actually restart
 * Class StopConsumerException
 * @package OldSound\RabbitMqBundle\RabbitMq\Exception
 */
class StopConsumerException extends \RuntimeException
{

}

