<?php


namespace OldSound\RabbitMqBundle\Receiver\ArgumentResolver;

use OldSound\RabbitMqBundle\Declarations\BatchConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\RabbitMq\Exception\ReceiverException;
use OldSound\RabbitMqBundle\Receiver\ArgumentMetadata;
use OldSound\RabbitMqBundle\Receiver\ArgumentValueResolverInterface;
use OldSound\RabbitMqBundle\Receiver\Attribute\SerializeMessage;
use OldSound\RabbitMqBundle\Receiver\ReceiverInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerResolver implements ArgumentValueResolverInterface
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer ?? new Serializer();
    }

    public function supports(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): bool
    {
        return null !== $this->getAttribute($options);
    }

    public function resolve(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): iterable
    {
        $attr = $this->getAttribute($options);
        try {
            if ($this->isBatch($options)) {
                return $this->serializer->deserialize($messages, $attr->type, $attr->format, $attr->context);
            } else {
                return $this->serializer->deserialize(first($messages, $attr->type, $attr->format, $attr->context));
            }
        } catch (ExceptionInterface $exception) {
            throw new ReceiverException(ReceiverInterface::MSG_REJECT, $exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function getAttribute(): ?SerializeMessage
    {
        if (is_array($options->receiver)) {
            $reflection = new \ReflectionMethod($options->receiver[0], $options->receiver[1]);
            foreach ($reflection->getAttributes() as $attribute) {
                if (SerializeMessage::class === $attribute->getName()) {
                    /** @var SerializeMessage $attr */
                    return $attribute->newInstance();
                }
            }
        }

        return null;
    }

    private function isBatch(ConsumeOptions $options): bool
    {
        return $options instanceof BatchConsumeOptions;
    }
}