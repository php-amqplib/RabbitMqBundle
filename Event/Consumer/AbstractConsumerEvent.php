<?php

namespace OldSound\RabbitMqBundle\Event\Consumer;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class AbstractConsumerEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
abstract class AbstractConsumerEvent extends Event
{
    /**
     * Event Name
     *
     * @return string
     */
    abstract public function getName();
}
