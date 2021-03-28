<?php


namespace OldSound\RabbitMqBundle\Receiver\ArgumentResolver;

use OldSound\RabbitMqBundle\Declarations\BatchConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\Receiver\ArgumentMetadata;
use OldSound\RabbitMqBundle\Receiver\ArgumentValueResolverInterface;

class ConsumeOptionsResolver implements ArgumentValueResolverInterface
{
    public function supports(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): bool
    {
        return get_class($options) === $argument->getType();
    }

    public function resolve(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): iterable
    {
        return $options;
    }
}