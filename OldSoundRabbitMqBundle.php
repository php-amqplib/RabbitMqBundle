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
        $partHolder = $this->container->get('old_sound_rabbit_mq.parts_holder');
        $connections = $partHolder->getParts("old_sound_rabbit_mq.base_amqp");
        foreach ($connections as $connection) {
            $connection->close();
        }
    }
}
