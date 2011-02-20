<?php

namespace OldSound\RabbitmqBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class RabbitmqBundle extends Bundle
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
