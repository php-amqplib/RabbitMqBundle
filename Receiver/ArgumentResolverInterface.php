<?php


namespace OldSound\RabbitMqBundle\Receiver;


use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;

interface ArgumentResolverInterface
{
    /**
     * @param \AMQPMessage[] $messages
     * @param ConsumeOptions $options
     * @return mixed
     */
    public function getArguments(array $messages, ConsumeOptions $options);
}