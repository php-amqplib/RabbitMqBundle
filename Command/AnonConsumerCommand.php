<?php

namespace OldSound\RabbitmqBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OldSound\RabbitmqBundle\Rabbitmq\ConsumerInterface;

class AnonConsumerCommand extends Command
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:anon-consumer')
            ->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
            ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 1)
            ->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Enable Debugging', false)
            ->addOption('r_key', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '#')
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
        
        $name = $input->getArgument('name');
        
        $callbackObj = $this->container->get(sprintf('%s_service', $name));

        if($callbackObj instanceof ConsumerInterface)
        {
            $consumer = $this->container->get(sprintf('rabbitmq.%s_anon_consumer', $name));
            $consumer->setRoutingKey($input->getOption('r_key'));
            
            $callbackObj->setContainer($this->container);
            
            $consumer->setCallback(array($callbackObj, 'execute'));
            $consumer->consume($input->getOption('messages'));
        }
        else
        {
            throw new InvalidConfigurationException(sprintf('the callback %s must implement the ConsumerInterface', 
                                                    sprintf('%s_service', $name)));
        }
    }
}