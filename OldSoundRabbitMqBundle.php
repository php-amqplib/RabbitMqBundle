<?php

namespace OldSound\RabbitMqBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use OldSound\RabbitMqBundle\DependencyInjection\Compiler\LoadDataCollectorCompilerPass;

class OldSoundRabbitMqBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
    }
}
