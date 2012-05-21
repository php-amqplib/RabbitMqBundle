<?php

namespace OldSound\RabbitMqBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadConsumerCompilerPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadConnectionCompilerPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadProducerCompilerPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadAnonConsumerCompilerPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadDataCollectorCompilerPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadRpcClientCompilerPass;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadRpcServerCompilerPass;

class OldSoundRabbitMqBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LoadConnectionCompilerPass());
        $container->addCompilerPass(new LoadConsumerCompilerPass());
        $container->addCompilerPass(new LoadProducerCompilerPass());
        $container->addCompilerPass(new LoadAnonConsumerCompilerPass());
        $container->addCompilerPass(new LoadDataCollectorCompilerPass());
        $container->addCompilerPass(new LoadRpcClientCompilerPass());
        $container->addCompilerPass(new LoadRpcServerCompilerPass());
    }
}
