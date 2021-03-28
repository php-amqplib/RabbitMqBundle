<?php

namespace OldSound\RabbitMqBundle;

use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OldSoundRabbitMqBundle extends Bundle
{
    public function createContainerExtension()
    {
        return new OldSoundRabbitMqExtension(); // TODO pass alias from consutructor for allow include bundle multiple times
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown()
    {
        parent::shutdown();

        /* TODO $connections = $this->container->getParameter('old_sound_rabbit_mq.connection');
        foreach ($connections as $connection) {
            if ($this->container->initialized($connection)) {
                $this->container->get($connection)->close();
            }
        }*/
    }
}
