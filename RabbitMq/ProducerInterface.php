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
     * @param array $headers
     *
     * @return mixed
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = [], array $headers = []);
}
