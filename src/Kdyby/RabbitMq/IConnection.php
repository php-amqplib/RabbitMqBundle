<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

interface IConnection
{

	public function getConsumer(string $name): \Kdyby\RabbitMq\Consumer;

	public function getProducer(string $name): \Kdyby\RabbitMq\Producer;

	public function getRpcClient(string $name): \Kdyby\RabbitMq\RpcClient;

	public function getRpcServer(string $name): \Kdyby\RabbitMq\RpcServer;

}
