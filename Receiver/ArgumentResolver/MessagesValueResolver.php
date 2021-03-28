<?php


namespace OldSound\RabbitMqBundle\Receiver\ArgumentResolver;

use OldSound\RabbitMqBundle\Declarations\BatchConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\Receiver\ArgumentMetadata;
use OldSound\RabbitMqBundle\Receiver\ArgumentValueResolverInterface;

class MessagesValueResolver implements ArgumentValueResolverInterface
{
    public function supports(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): bool
    {
        if ($this->isBatch($options)) {
            if (count($messages) > 1) {
                throw new \InvalidArgumentException();
            }
            return in_array($argument->getType(), [null, 'iterable', 'array'], true)
                && 'messages' === $argument->getName();
        } else {
            return \AMQPMessage::class === $argument->getType() ||
                (null === $argument->getType() && 'message' === $argument->getName());
        }
    }

    public function resolve(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): iterable
    {
        if ($options instanceof BatchConsumeOptions) {
            yield $messages;
        } else {
            yield first($messages);
        }
    }

    private function isBatch(ConsumeOptions $options): bool
    {
        return $options instanceof BatchConsumeOptions;
    }
}