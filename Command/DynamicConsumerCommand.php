<?php

/**
 * DynamicConsumerCommand
 * 
 * The context argument is passed to the consumer instance
 * which can decide about the queue and routings it uses.
 *
 * @author Tibor Barna <tibor.barna@wiredminds.de>
 */
namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

class DynamicConsumerCommand extends BaseConsumerCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:dynamic-consumer')
            ->setDescription('Executes context-aware consumer')
            ->addArgument('context', InputArgument::REQUIRED, 'Context the consumer runs in')
            ;
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_dynamic';
    }

    protected function initConsumer($input)
    {
        parent::initConsumer($input);
        $this->consumer->setContext($input->getArgument('context'));
    }
}
