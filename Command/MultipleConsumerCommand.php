<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class MultipleConsumerCommand extends BaseConsumerCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Executes a consumer that uses multiple queues')
                ->setName('rabbitmq:multiple-consumer')
                ->addArgument('context', InputArgument::OPTIONAL, 'Context the consumer runs in')
        ;
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_multiple';
    }

    protected function initConsumer(InputInterface $input)
    {
        parent::initConsumer($input);
        $this->consumer->setContext($input->getArgument('context'));
    }
}
