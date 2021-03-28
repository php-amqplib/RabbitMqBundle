<?php

namespace OldSound\RabbitMqBundle\Serializer;

use OldSound\RabbitMqBundle\RabbitMq\Exception\RpcResponseException;
use OldSound\RabbitMqBundle\RabbitMq\RpcReponse;

interface MessageSerializerInterface
{
    /**
     * @param mixed|RpcReponse|RpcResponseException $body
     * @return mixed
     */
    public function serialize($body): string;

    /**
     * @param string $body
     * @return mixed
     */
    public function deserialize(string $body);
}