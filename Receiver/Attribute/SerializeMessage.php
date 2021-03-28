<?php

namespace OldSound\RabbitMqBundle\Receiver\Attribute;

#[Attribute]
class SerializeMessage
{
    public function __construct(public string $type, public string $format, public array $context = [])
    {

    }
}