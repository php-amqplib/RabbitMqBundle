<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RpcServerCommand extends BaseConsumerCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:rpc-server')
            ->setDescription('Start an RPC server');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \InvalidArgumentException When the number of messages to consume is less than 0
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleSignals($input);
        $this->enableDebug($input);

        $amount = $input->getOption('messages');

        if (0 > $amount) {
            throw new \InvalidArgumentException("The -m option should be null or greater than 0");
        }

        $this->initConsumer($input);
        $this->consumer->consume($amount);
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_server';
    }
}
