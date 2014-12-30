<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface StallableConsumerInterface extends ConsumerInterface
{
    /**
     * Is Consumer stalled?
     *
     * @return bool
     */
    public function isStalled();
}
