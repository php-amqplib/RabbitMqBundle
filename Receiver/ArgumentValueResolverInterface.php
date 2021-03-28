<?php


namespace OldSound\RabbitMqBundle\Receiver;


use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;

interface ArgumentValueResolverInterface
{
    public function supports(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): bool;
    public function resolve(array $messages, ConsumeOptions $options, ArgumentMetadata $argument): iterable;
}