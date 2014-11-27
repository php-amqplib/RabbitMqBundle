<?php

namespace OldSound\RabbitMqBundle\Provider;

/**
 * Queues provider interface
 *
 * @author Sergey Chernecov <sergey.chernecov@intexsys.lv>
 */
interface QueuesProviderInterface
{
    /**
     * Return array of queues
     *
     * @return array
     */
    public function getQueues();
}
