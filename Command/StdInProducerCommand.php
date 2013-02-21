<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StdInProducerCommand extends BaseRabbitMqCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:stdin-producer')
            ->addArgument('name', InputArgument::REQUIRED, 'Producer Name')
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('AMQP_DEBUG', (bool) $input->getOption('debug'));

        $producer = $this->getContainer()->get(sprintf('old_sound_rabbit_mq.%s_producer', $input->getArgument('name')));

        $data = '';
        while (!feof(STDIN)) {
            $data .= fread(STDIN, 8192);
        }

        $producer->publish(serialize($data));
    }
}
