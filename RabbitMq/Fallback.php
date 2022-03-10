<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class Fallback implements ProducerInterface
{
    public function publish($msgBody, $routingKey = '', $additionalProperties = [])
    {
        return false;
    }
}
