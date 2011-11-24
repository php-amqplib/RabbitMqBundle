<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnonConsumerCommand extends BaseRabbitMqCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:anon-consumer')
            ->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
            ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 1)
            ->addOption('r_key', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '#')
            ->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Debug mode', false)
        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract class is not implemented
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('AMQP_DEBUG', (bool) $input->getOption('debug'));

        $consumer = $this->getContainer()
                         ->get(sprintf('old_sound_rabbit_mq.%s_anon', $input->getArgument('name')));
        $consumer->setRoutingKey($input->getOption('r_key'));
        $consumer->consume($input->getOption('messages'));
    }
}