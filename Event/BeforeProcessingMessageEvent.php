<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\Event\MessageProcessor\AbstractProcessMessageEvent;

/**
 * Class BeforeProcessingMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class BeforeProcessingMessageEvent extends AbstractProcessMessageEvent
{
    const NAME = 'before_processing_message';

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }
}
