<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

interface IProducer
{

	/**
	 * @param array<mixed> $options
	 */
	public function setExchangeOptions(array $options = []): void;

	/**
	 * @param array<mixed> $options
	 */
	public function setQueueOptions(array $options = []): void;

	public function setRoutingKey(string $routingKey): void;

	public function setContentType(string $contentType): IProducer;

	public function setDeliveryMode(int $deliveryMode): IProducer;

	/**
	 * @param string $msgBody
	 * @param string|NULL $routingKey
	 * @param array<mixed> $additionalProperties
	 */
	public function publish(string $msgBody, ?string $routingKey = '', array $additionalProperties = []): void;

}
