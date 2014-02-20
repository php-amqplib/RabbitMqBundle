<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

/**
 * Interface to add interaction between consumer and his callback.
 *
 * @author metfan
 *
 */
interface InteractiveConsumerInterface extends ConsumerInterface
{
    /**
     * Return if consumer must be stoped
     *
     * @return boolean
     */
    public function mustStopConsumer();
}