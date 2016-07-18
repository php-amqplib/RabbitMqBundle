<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface HeartbeatAwareConsumerInterface
{
    /**
     * @param int $consumeDuration
     * @return void
     */
    public function heartbeat($consumeDuration);
}