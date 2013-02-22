<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer as Consumer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;

abstract class BaseConsumerCommand extends BaseRabbitMqCommand
{
    protected $consumer;

    protected $amount;

    abstract protected function getConsumerService();

    public function stopConsumer()
    {
        if ($this->consumer instanceof Consumer) {
            $this->consumer->forceStopConsumer();
        } else {
            exit();
        }
    }

    public function restartConsumer()
    {
        // TODO: Implement restarting of consumer
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
            ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
            ->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging')
            ->addOption('without-signals', 'w', InputOption::VALUE_NONE, 'Disable catching of system signals')
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
     * @throws \InvalidArgumentException When the number of messages to consume is less than 0
     * @throws \InvalidArgumentException When the pcntl is not installed and option -s is true
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (defined('AMQP_WITHOUT_SIGNALS') === false) {
            define('AMQP_WITHOUT_SIGNALS', $input->getOption('without-signals'));
        }

        if (!AMQP_WITHOUT_SIGNALS && extension_loaded('pcntl')) {
            if (!function_exists('pcntl_signal')) {
                throw new \BadFunctionCallException("This function is referenced in the php.ini 'disable_functions' and can't be called.");
            }

            pcntl_signal(SIGTERM, array(&$this, 'stopConsumer'));
            pcntl_signal(SIGINT, array(&$this, 'stopConsumer'));
            pcntl_signal(SIGHUP, array(&$this, 'restartConsumer'));
        }

        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool) $input->getOption('debug'));
        }

        $this->amount = $input->getOption('messages');

        if (0 > $this->amount) {
            throw new \InvalidArgumentException("The -m option should be null or greater than 0");
        }

        $this->consumer = $this->getContainer()
            ->get(sprintf($this->getConsumerService(), $input->getArgument('name')));

        $this->consumer->setRoutingKey($input->getOption('route'));
        $this->consumer->consume($this->amount);
    }
}
