<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface StalledAwareConsumerInterface extends ConsumerInterface
{
    /**
     * Is Consumer stalled?
     *
     * @return bool
     */
    public function isStalled();
}
