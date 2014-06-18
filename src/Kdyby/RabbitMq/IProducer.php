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
interface IProducer
{

	function setExchangeOptions(array $options = array());

	function setQueueOptions(array $options = array());

	function setRoutingKey($routingKey);

	function setContentType($contentType);

	function setDeliveryMode($deliveryMode);

	function publish($msgBody, $routingKey = '', $additionalProperties = array());

}
