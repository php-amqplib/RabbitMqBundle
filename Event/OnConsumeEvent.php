<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;

/**
 * Class OnConsumeEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class OnConsumeEvent extends AMQPEvent
{
    const NAME = AMQPEvent::ON_CONSUME;

    /**
     * OnConsumeEvent constructor.
     *
     * @param BaseConsumer $consumer
     */
    public function __construct(BaseConsumer $consumer)
    {
        $this->setConsumer($consumer);
    }
}
