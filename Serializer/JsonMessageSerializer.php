<?php


namespace OldSound\RabbitMqBundle\Serializer;


use OldSound\RabbitMqBundle\RabbitMq\Exception\RpcResponseException;
use OldSound\RabbitMqBundle\RabbitMq\RpcReponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class JsonMessageSerializer implements MessageSerializerInterface
{
    /** @var SerializerInterface */
    private $serializer;
    /** @var string|null */
    private $deserializeType;

    public function __construct(string $deserializeType = null, SerializerInterface $serializer = null)
    {
        if ($this->deserializeType && !$this->serializer) {
            throw new \InvalidArgumentException('serializer is required if deserializerType specified');
        }
        $this->deserializeType = $deserializeType;
        $this->serializer = $serializer;
    }

    public function serialize($body): string
    {
        if ($body instanceof RpcResponseException) {
            return $this->serializer->serialize([
                'error_code' => $body->getCode(),
                'message' => $body->getMessage(),
            ], 'json');
        }

        return $this->serializer->serialize($body, 'json');
    }

    public function deserialize(string $body)
    {
        $data = $this->serializer->denormalize($body, 'json');
        if (isset($data['error_code'])) {
            return new RpcResponseException(new RpcResponseException($data['message'], $data['error_code']));
        } else if ($this->deserializeType) {
            return $this->serializer->deserialize($body, $this->deserializeType, 'json');
        }


        return $data;
    }

}