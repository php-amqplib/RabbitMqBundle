<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

class Producer extends \Kdyby\RabbitMq\AmqpMember implements \Kdyby\RabbitMq\IProducer
{

	/**
	 * @var string
	 */
	protected $contentType = 'text/plain';

	/**
	 * @var int
	 */
	protected $deliveryMode = 2;

	public function setContentType(string $contentType): IProducer
	{
		$this->contentType = $contentType;

		return $this;
	}

	public function setDeliveryMode(int $deliveryMode): IProducer
	{
		$this->deliveryMode = $deliveryMode;

		return $this;
	}

	/**
	 * @return array<string, int>
	 */
	protected function getBasicProperties(): array
	{
		return [
			'content_type' => $this->contentType,
			'delivery_mode' => $this->deliveryMode,
		];
	}

	/**
	 * Publishes the message and merges additional properties with basic properties
	 *
	 * @param string $msgBody
	 * @param string|NULL $routingKey If not provided or set to null, used default routingKey from configuration of this producer
	 * @param array<mixed> $additionalProperties
	 */
	public function publish(string $msgBody, ?string $routingKey = '', array $additionalProperties = []): void
	{
		if ($this->autoSetupFabric) {
			$this->setupFabric();
		}

		if ($routingKey === '' || $routingKey === NULL) { // empty string or NULL
			$routingKey = $this->routingKey;
		}

		$msg = new AMQPMessage($msgBody, \array_merge($this->getBasicProperties(), $additionalProperties));
		$this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], (string) $routingKey);
	}

}
