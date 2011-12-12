<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ConsumerInterface
{
    function execute($msg);
}