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
     * Example:
     * array(
     *    'queue_name' => array(
     *       'durable' => false,
     *       'exclusive' => false,
     *       'passive' => false,
     *       'nowait' => false,
     *       'auto_delete' => false,
     *       'routing_keys' => array('key.1', 'key.2'),
     *       'arguments' => array(),
     *       'ticket' => '',
     *       'callback' => array($callback, 'execute')
     *    )
     * );
     * @return array
     * 
     */
    public function getQueues();
}
