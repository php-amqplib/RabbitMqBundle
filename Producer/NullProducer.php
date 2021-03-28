<?php

namespace OldSound\RabbitMqBundle\Producer;

class NullProducer extends Producer
{
    public function publish(string $body, string $routingKey = '', array $additionalProperties = [], ?array $headers = null): void
    {
    }
}
