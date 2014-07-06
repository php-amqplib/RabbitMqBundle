<?php

namespace Kdyby\RabbitMq\Command;

use Kdyby\RabbitMq\BaseConsumer as Consumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class BaseConsumerCommand extends Command
{

	/**
	 * @inject
	 * @var \Kdyby\RabbitMq\Connection
	 */
	public $connection;

	/**
	 * @var Consumer|\Kdyby\RabbitMq\Consumer
	 */
	protected $consumer;

	/**
	 * @var int
	 */
	protected $amount;



	protected function configure()
	{
		$this
			->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
			->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
			->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '')
			->addOption('memory-limit', 'l', InputOption::VALUE_OPTIONAL, 'Allowed memory for this process', null)
			->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging')
			->addOption('without-signals', 'w', InputOption::VALUE_NONE, 'Disable catching of system signals');
	}



	/**
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @throws \InvalidArgumentException When the number of messages to consume is less than 0
	 * @throws \BadFunctionCallException When the pcntl is not installed and option -s is true
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		if (defined('AMQP_WITHOUT_SIGNALS') === false) {
			define('AMQP_WITHOUT_SIGNALS', $input->getOption('without-signals'));
		}

		if (!AMQP_WITHOUT_SIGNALS && extension_loaded('pcntl')) {
			if (!function_exists('pcntl_signal')) {
				throw new \BadFunctionCallException("Function 'pcntl_signal' is referenced in the php.ini 'disable_functions' and can't be called.");
			}

			pcntl_signal(SIGTERM, function () {
				if ($this->consumer) {
					pcntl_signal(SIGTERM, SIG_DFL);
					$this->consumer->forceStopConsumer();
				}
			});
			pcntl_signal(SIGINT, function () {
				if ($this->consumer) {
					pcntl_signal(SIGINT, SIG_DFL);
					$this->consumer->forceStopConsumer();
				}
			});
			pcntl_signal(SIGHUP, function () {
				if ($this->consumer) {
					pcntl_signal(SIGHUP, SIG_DFL);
					$this->consumer->forceStopConsumer();
				}

				// TODO: Implement restarting of consumer
			});
		}

		if (defined('AMQP_DEBUG') === false) {
			define('AMQP_DEBUG', (bool) $input->getOption('debug'));
		}

		if (($this->amount = $input->getOption('messages')) < 0) {
			throw new \InvalidArgumentException("The -m option should be null or greater than 0");
		}

		$this->consumer = $this->connection->getConsumer($input->getArgument('name'));

		if (!is_null($input->getOption('memory-limit')) && ctype_digit((string) $input->getOption('memory-limit')) && $input->getOption('memory-limit') > 0) {
			$this->consumer->setMemoryLimit($input->getOption('memory-limit'));
		}

		if ($routingKey = $input->getOption('route')) {
			$this->consumer->setRoutingKey($routingKey);
		}
	}



	/**
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->consumer->consume($this->amount);
	}

}
