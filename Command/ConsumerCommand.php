<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerCommand extends BaseRabbitMqCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:consumer')
            ->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
            ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
            ->addOption('r_key', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '')
            ->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Enable Debugging', false)
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
        $amount = $input->getOption('messages');

        if (0 > $amount) {
            throw new InvalidArgumentException("The -m option should be null or greater than 0");
        }

        $consumer = $this->getContainer()
                         ->get(sprintf('old_sound_rabbit_mq.%s_consumer', $input->getArgument('name')));
        $consumer->setRoutingKey($input->getOption('r_key'));
        $consumer->consume($amount);
    }
}