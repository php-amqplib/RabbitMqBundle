<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseConsumerCommand extends \Symfony\Component\Console\Command\Command
{

	/**
	 * @inject
	 * @var \Kdyby\RabbitMq\Connection
	 */
	public $connection;

	/**
	 * @var \Kdyby\RabbitMq\Consumer
	 */
	protected $consumer;

	/**
	 * @var int
	 */
	protected $amount;

	protected function configure(): void
	{
		$this
			->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
			->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
			->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '')
			->addOption('memory-limit', 'l', InputOption::VALUE_OPTIONAL, 'Allowed memory for this process', NULL)
			->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging')
			->addOption('without-signals', 'w', InputOption::VALUE_NONE, 'Disable catching of system signals');
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input An InputInterface instance
	 * @param \Symfony\Component\Console\Output\OutputInterface $output An OutputInterface instance
	 * @throws \InvalidArgumentException When the number of messages to consume is less than 0
	 * @throws \BadFunctionCallException When the pcntl is not installed and option -s is true
	 */
	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		parent::initialize($input, $output);

		if (\defined('AMQP_WITHOUT_SIGNALS') === FALSE) {
			\define('AMQP_WITHOUT_SIGNALS', $input->getOption('without-signals'));
		}

		if (!AMQP_WITHOUT_SIGNALS && \extension_loaded('pcntl')) {
			if (!\function_exists('pcntl_signal')) {
				throw new \BadFunctionCallException("Function 'pcntl_signal' is referenced in the php.ini 'disable_functions' and can't be called.");
			}

			\pcntl_signal(SIGTERM, [$this, 'signalTerm']);
			\pcntl_signal(SIGINT, [$this, 'signalInt']);
			\pcntl_signal(SIGHUP, [$this, 'signalHup']);
		}

		if (\defined('AMQP_DEBUG') === FALSE) {
			\define('AMQP_DEBUG', (bool) $input->getOption('debug'));
		}

		$this->amount = (int) $input->getOption('messages');
		if ($this->amount < 0) {
			throw new \InvalidArgumentException('The -m option should be null or greater than 0');
		}

		$this->consumer = $this->connection->getConsumer($input->getArgument('name'));

		/** @var int|NULL $memoryLimit */
		$memoryLimit = $input->getOption('memory-limit');
		if ($memoryLimit !== NULL && \ctype_digit((string) $memoryLimit) && $memoryLimit > 0) {
			$this->consumer->setMemoryLimit($memoryLimit);
		}

		$routingKey = $input->getOption('route');
		if ($routingKey) {
			$this->consumer->setRoutingKey($routingKey);
		}
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input An InputInterface instance
	 * @param \Symfony\Component\Console\Output\OutputInterface $output An OutputInterface instance
	 */
	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->consumer->consume($this->amount);
	}

	/**
	 * @internal for pcntl only
	 */
	public function signalTerm(): void
	{
		if ($this->consumer) {
			\pcntl_signal(SIGTERM, SIG_DFL);
			$this->consumer->forceStopConsumer();
		}
	}

	/**
	 * @internal for pcntl only
	 */
	public function signalInt(): void
	{
		if ($this->consumer) {
			\pcntl_signal(SIGINT, SIG_DFL);
			$this->consumer->forceStopConsumer();
		}
	}

	/**
	 * @internal for pcntl only
	 */
	public function signalHup(): void
	{
		if ($this->consumer) {
			\pcntl_signal(SIGHUP, SIG_DFL);
			$this->consumer->forceStopConsumer();
		}
		// TODO: Implement restarting of consumer
	}

}
