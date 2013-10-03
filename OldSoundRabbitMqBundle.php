<?php

namespace OldSound\RabbitMqBundle;

use OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OldSoundRabbitMqBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterPartsPass());
    }
}
