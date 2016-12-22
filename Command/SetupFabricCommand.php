<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupFabricCommand extends BaseRabbitMqCommand
{
    protected function configure()
    {
        $this
            ->setName('rabbitmq:setup-fabric')
            ->setDescription('Sets up the Rabbit MQ fabric')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool) $input->getOption('debug'));
        }

        $output->writeln('Setting up the Rabbit MQ fabric');

        $partsHolder = $this->getContainer()->get('old_sound_rabbit_mq.parts_holder');

        foreach (array('base_amqp', 'binding') as $key) {
            foreach ($partsHolder->getParts('old_sound_rabbit_mq.' . $key) as $baseAmqp) {
                $baseAmqp->setupFabric();
            }
        }

    }
}
