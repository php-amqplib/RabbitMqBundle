<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;



/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends AmqpMember
{

	/**
	 * @var string
	 */
	protected $contentType = 'text/plain';

	/**
	 * @var string
	 */
	protected $deliveryMode = 2;



	public function setContentType($contentType)
	{
		$this->contentType = $contentType;

		return $this;
	}



	public function setDeliveryMode($deliveryMode)
	{
		$this->deliveryMode = $deliveryMode;

		return $this;
	}



	protected function getBasicProperties()
	{
		return array('content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode);
	}



	/**
	 * Publishes the message and merges additional properties with basic properties
	 *
	 * @param string $msgBody
	 * @param string $routingKey
	 * @param array $additionalProperties
	 */
	public function publish($msgBody, $routingKey = '', $additionalProperties = array())
	{
		if ($this->autoSetupFabric) {
			$this->setupFabric();
		}

		$msg = new AMQPMessage((string) $msgBody, array_merge($this->getBasicProperties(), $additionalProperties));
		$this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], (string) $routingKey);
	}
}
