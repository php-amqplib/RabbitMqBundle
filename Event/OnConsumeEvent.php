<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\Event\Consumer\AbstractConsumerEvent;

/**
 * Class OnConsumeEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class OnConsumeEvent extends AbstractConsumerEvent
{
    const NAME = 'on_consume';

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }
}
