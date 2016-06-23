<?php

namespace OldSound\RabbitMqBundle\Event;

/**
 * Class OnConsumeEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class OnConsumeEvent extends AMQPEvent
{
    const NAME = AMQPEvent::ON_CONSUME;
}
