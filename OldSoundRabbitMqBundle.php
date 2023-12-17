<?php

namespace OldSound\RabbitMqBundle;

use OldSound\RabbitMqBundle\DependencyInjection\Compiler\InjectEventDispatcherPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\ServiceContainerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OldSoundRabbitMqBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterPartsPass());
        $container->addCompilerPass(new InjectEventDispatcherPass());
        $container->addCompilerPass(new ServiceContainerPass());
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown(): void
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
