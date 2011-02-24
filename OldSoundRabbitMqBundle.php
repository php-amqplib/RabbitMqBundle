<?php

namespace OldSound\RabbitMqBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OldSoundRabbitMqBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }
}
