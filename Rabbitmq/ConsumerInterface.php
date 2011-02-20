<?php

namespace OldSound\RabbitmqBundle\Rabbitmq;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface ConsumerInterface extends ContainerAwareInterface
{
    function execute($msg);
}