<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class AmqpPartsHolder
{
    protected $parts;

    public function __construct()
    {
        $this->parts = [];
    }

    public function addPart($type, BaseAmqp $part)
    {
        $this->parts[$type][] = $part;
    }

    public function getParts($type)
    {
        $type = (string) $type;
        return $this->parts[$type] ?? [];
    }
}
