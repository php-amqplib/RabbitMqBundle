<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class Fallback implements ProducerInterface
{
    /**
     * Publish a message
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null)
    {
        return false;
    }
}
