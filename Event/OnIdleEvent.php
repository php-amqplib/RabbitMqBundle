<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;

/**
 * Class OnIdleEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class OnIdleEvent extends AMQPEvent
{
    const NAME = AMQPEvent::ON_IDLE;

    /**
     * @var bool
     */
    private $forceStop;

    /**
     * OnConsumeEvent constructor.
     *
     * @param Consumer $consumer
     */
    public function __construct(Consumer $consumer)
    {
        $this->setConsumer($consumer);

        $this->forceStop = true;
    }

    /**
     * @return boolean
     */
    public function isForceStop()
    {
        return $this->forceStop;
    }

    /**
     * @param boolean $forceStop
     */
    public function setForceStop($forceStop)
    {
        $this->forceStop = $forceStop;
    }
}
