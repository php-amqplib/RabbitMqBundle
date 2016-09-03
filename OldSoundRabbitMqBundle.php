<?php

namespace OldSound\RabbitMqBundle;

use OldSound\RabbitMqBundle\DependencyInjection\Compiler\InjectEventDispatcherPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OldSoundRabbitMqBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterPartsPass());
        $container->addCompilerPass(new InjectEventDispatcherPass());
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown()
    {
        parent::shutdown();
        if (!$this->container->hasParameter('old_sound_rabbit_mq.base_amqp')) {
            return;
        }
        $connections = $this->container->getParameter('old_sound_rabbit_mq.base_amqp');
        foreach ($connections as $connection) {
            if ($this->container->initialized($connection)) {
                $this->container->get($connection)->close();
            }
        }
    }
}
