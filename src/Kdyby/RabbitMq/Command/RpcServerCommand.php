<?php

namespace Kdyby\RabbitMq\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class RpcServerCommand extends Command
{

	/**
	 * @inject
	 * @var \Kdyby\RabbitMq\Connection
	 */
	public $connection;



	protected function configure()
	{
		$this
			->setName('rabbitmq:rpc-server')
			->setDescription("Starts a configured RPC server")
			->addArgument('name', InputArgument::REQUIRED, 'Server Name')
			->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
			->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Debug mode', false);
	}



	/**
	 * Executes the current command.
	 *
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \InvalidArgumentException When the number of messages to consume is less than 0
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		define('AMQP_DEBUG', (bool) $input->getOption('debug'));

		if (($amount = $input->getOption('messages')) < 0) {
			throw new \InvalidArgumentException("The -m option should be null or greater than 0");
		}

		$rpcServer = $this->connection->getRpcServer($input->getArgument('name'));
		$rpcServer->start($amount);
	}

}
