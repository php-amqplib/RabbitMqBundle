<?php

namespace OldSound\RabbitMqBundle\Provider;

/**
 * Interface MemoryUsageProviderInterface
 *
 * @author Ilir Hoxha <ilirhxh@gmail.com>
 */
interface MemoryUsageProviderInterface
{
    /**
     * @return int
     */
    public function getMemoryUsage();
}
