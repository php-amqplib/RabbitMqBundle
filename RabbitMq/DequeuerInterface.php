<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface DequeuerInterface
{
    /**
     * Stop dequeuing messages.
     *
     * @return void
     */
    public function forceStopConsumer();

    /**
     * Set idle timeout
     *
     * @param int $idleTimeout
     *
     * @return void
     */
    public function setIdleTimeout($idleTimeout);

    /**
     * Get current idle timeout
     *
     * @return int
     */
    public function getIdleTimeout();
}
