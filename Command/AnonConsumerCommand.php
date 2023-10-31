<?php

namespace OldSound\RabbitMqBundle\Command;

class AnonConsumerCommand extends BaseConsumerCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('rabbitmq:anon-consumer');
        $this->setDescription('Executes an anonymous consumer');
        $this->getDefinition()->getOption('messages')->setDefault('1');
        $this->getDefinition()->getOption('route')->setDefault('#');
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_anon';
    }
}
