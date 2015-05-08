<?php

namespace OldSound\RabbitMqBundle\Provider;

/**
 * Queue provider interface
 *
 * @author Tibor Barna <tibor.barna@wiredminds.de>
 */
interface QueueOptionsProviderInterface
{
    /**
     * Return queue options
     * 
     * Example:
     * array(
     *   'name' => 'example_context',
     *   'durable' => true,
     *   'routing_keys' => array('key.*')
     * )
     * 
     * @return array
     * 
     */
    public function getQueueOptions($context = null);
}
