<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

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
