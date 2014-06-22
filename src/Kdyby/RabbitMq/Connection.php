<?php

namespace Kdyby\RabbitMq;

use Kdyby;
use Nette;
use PhpAmqpLib;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Connection extends PhpAmqpLib\Connection\AMQPLazyConnection implements IConnection
{

	/**
	 * @var Nette\DI\Container
	 */
	private $serviceLocator;

	/**
	 * @var Diagnostics\Panel
	 */
	private $panel;

	/**
	 * @var array
	 */
	private $serviceMap = array();



	/**
	 * @param string $name
	 * @return BaseConsumer
	 */
	public function getConsumer($name)
	{
		if (!isset($this->serviceMap['consumer'][$name])) {
			throw new InvalidArgumentException("Unknown consumer {$name}");
		}

		return $this->serviceLocator->getService($this->serviceMap['consumer'][$name]);
	}



	/**
	 * @param $name
	 * @return Producer
	 */
	public function getProducer($name)
	{
		if (!isset($this->serviceMap['producer'][$name])) {
			throw new InvalidArgumentException("Unknown producer {$name}");
		}

		return $this->serviceLocator->getService($this->serviceMap['producer'][$name]);
	}



	/**
	 * @param $name
	 * @return RpcClient
	 */
	public function getRpcClient($name)
	{
		if (!isset($this->serviceMap['rpcClient'][$name])) {
			throw new InvalidArgumentException("Unknown RPC client {$name}");
		}

		return $this->serviceLocator->getService($this->serviceMap['rpcClient'][$name]);
	}



	/**
	 * @param $name
	 * @return RpcServer
	 */
	public function getRpcServer($name)
	{
		if (!isset($this->serviceMap['rpcServer'][$name])) {
			throw new InvalidArgumentException("Unknown RPC server {$name}");
		}

		return $this->serviceLocator->getService($this->serviceMap['rpcServer'][$name]);
	}



	/**
	 * @internal
	 */
	public function injectServiceMap(array $producers, array $consumers, array $rpcClients, array $rpcServers)
	{
		$this->serviceMap = array(
			'consumer' => $consumers,
			'producer' => $producers,
			'rpcClient' => $rpcClients,
			'rpcServer' => $rpcServers,
		);
	}



	/**
	 * @internal
	 * @param Nette\DI\Container $sl
	 */
	public function injectServiceLocator(Nette\DI\Container $sl)
	{
		$this->serviceLocator = $sl;
	}



	/**
	 * @internal
	 * @param Diagnostics\Panel $panel
	 */
	public function injectPanel(Diagnostics\Panel $panel)
	{
		$this->panel = $panel->register($this);
	}



	/**
	 * Fetch a Channel object identified by the numeric channel_id, or
	 * create that object if it doesn't already exist.
	 *
	 * @param string $id
	 * @return Channel
	 */
	public function channel($id = null)
	{
		if (isset($this->channels[$id])) {
			return $this->channels[$id];
		}

		$this->connect();
		$id = $id ? $id : $this->get_free_channel_id();

		return $this->channels[$id] = $this->doCreateChannel($id);
	}



	/**
	 * @param string $id
	 * @return Channel
	 */
	protected function doCreateChannel($id)
	{
		$channel = new Channel($this->connection, $id);

		if ($this->panel) {
			$channel->injectPanel($this->panel);
		}

		return $channel;
	}

}
