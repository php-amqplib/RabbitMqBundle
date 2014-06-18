<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class Fallback
{
    public function publish()
    {
        return false;
    }
}
