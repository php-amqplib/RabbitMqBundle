<?php

declare(strict_types=1);

namespace OldSound\RabbitMqBundle\Tests;

use OldSound\RabbitMqBundle\OldSoundRabbitMqBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new OldSoundRabbitMqBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
    }
}
