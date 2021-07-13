<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

/**
 * Fallback producer for sandbox mode.
 */
class Fallback implements ProducerInterface
{
    /**
     * {@inheritDoc}
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setContentType($contentType)
    {
    }
}

