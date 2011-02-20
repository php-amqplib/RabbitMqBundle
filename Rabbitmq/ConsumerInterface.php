<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface ConsumerInterface extends ContainerAwareInterface
{
    function execute($msg);
}