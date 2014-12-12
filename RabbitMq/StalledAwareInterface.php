<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface StalledAwareInterface
{
    /**
     * Is Consumer stalled?
     *
     * @return bool
     */
    public function isStalled();
}
