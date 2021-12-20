<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ProducerInterface
{
    /**
     * Set content type
     *
     * @param string $contentType
     * @return self
     */
    public function setContentType($contentType);

    /**
     * Set delivery mode
     *
     * @param string $deliveryMode
     * @return self
     */
    public function setDeliveryMode($deliveryMode);

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null);
}
