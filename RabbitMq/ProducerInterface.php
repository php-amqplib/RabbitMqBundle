<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ProducerInterface
{
    /**
     * Publish a message
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @return bool
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array()): bool;
}
