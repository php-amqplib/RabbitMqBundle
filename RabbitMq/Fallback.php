<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class Fallback implements ProducerInterface
{
    public function publish($msgBody, $routingKey = '', $additionalProperties = [], array $headers = [])
    {
        return false;
    }
}
