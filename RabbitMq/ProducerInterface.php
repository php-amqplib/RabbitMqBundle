<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ProducerInterface
{
    /**
     * Publish a message
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null);

    /**
     * Add a message to a batch
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function addToBatch($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null);

    /**
     * @return void
     */
    public function publishBatch();
}
