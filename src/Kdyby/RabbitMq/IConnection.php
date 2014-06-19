<?php

namespace Kdyby\RabbitMq;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface IConnection
{

	/**
	 * @param string $name
	 * @return BaseConsumer
	 */
	function getConsumer($name);



	/**
	 * @param $name
	 * @return Producer
	 */
	function getProducer($name);



	/**
	 * @param $name
	 * @return RpcClient
	 */
	function getRpcClient($name);



	/**
	 * @param $name
	 * @return RpcServer
	 */
	function getRpcServer($name);

}
