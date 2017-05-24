<?php

namespace OldSound\RabbitMqBundle\MemoryChecker;

/**
 * Returns the current memory PHP is using (mainly used to allow mocking).
 *
 * @author Jonas Haouzi <jonas@viscaweb.com>
 */
class NativeMemoryUsageProvider
{
    /**
     * @return int
     */
    public function getMemoryUsage()
    {
        return memory_get_usage(true);
    }
}
