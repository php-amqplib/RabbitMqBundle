<?php

namespace OldSound\RabbitMqBundle\Event;


/**
 * Class OnIdleEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class OnIdleEvent extends AbstractAMQPEvent
{
    const NAME = 'old_sound_rabbit_mq.on_idle';
}
