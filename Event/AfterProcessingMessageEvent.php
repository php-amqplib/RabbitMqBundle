<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\Event\MessageProcessor\AbstractProcessMessageEvent;

/**
 * Class AfterProcessingMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Event
 */
class AfterProcessingMessageEvent extends AbstractProcessMessageEvent
{
    const NAME = 'after_processing_message';

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }
}