<?php

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
