<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Exception;

/**
 * If this exception is thrown in consumer service the message
 * will not be ack and consumer will stop
 * if using demonized, ex: supervisor, the consumer will actually restart
 * Class RestartConsumerException
 * @package OldSound\RabbitMqBundle\RabbitMq\Exception
 */
class StopConsumerException extends \RuntimeException
{

}

