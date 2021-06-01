<?php

declare(strict_types = 1);

namespace KdybyTests\RabbitMq\Mock;

class ChannelMock extends \Kdyby\RabbitMq\Channel
{

	/**
	 * @var array<mixed>
	 */
	public $calls = [];

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	protected function channel_alert($args)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		parent::channel_alert($args);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	protected function channel_close($args)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		parent::channel_close($args);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	protected function channel_flow($args)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		parent::channel_flow($args);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function exchange_declare(
		$exchange,
		$type,
		$passive = FALSE,
		$durable = FALSE,
		$autoDelete = TRUE,
		$internal = FALSE,
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::exchange_declare(
			$exchange,
			$type,
			$passive,
			$durable,
			$autoDelete,
			$internal,
			$nowait,
			$arguments,
			$ticket
		);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function exchange_delete(
		$exchange,
		$ifUnused = FALSE,
		$nowait = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::exchange_delete($exchange, $ifUnused, $nowait, $ticket);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function exchange_bind(
		$destination,
		$source,
		$routingKey = '',
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::exchange_bind($destination, $source, $routingKey, $nowait, $arguments, $ticket);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function exchange_unbind(
		$destination,
		$source,
		$routingKey = '',
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::exchange_unbind($destination, $source, $routingKey, $arguments, $ticket);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function queue_bind(
		$queue,
		$exchange,
		$routingKey = '',
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::queue_bind($queue, $exchange, $routingKey, $nowait, $arguments, $ticket);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function queue_unbind(
		$queue,
		$exchange,
		$routingKey = '',
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::queue_unbind($queue, $exchange, $routingKey, $arguments, $ticket);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function queue_declare(
		$queue = '',
		$passive = FALSE,
		$durable = FALSE,
		$exclusive = FALSE,
		$autoDelete = TRUE,
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::queue_declare(
			$queue,
			$passive,
			$durable,
			$exclusive,
			$autoDelete,
			$nowait,
			$arguments,
			$ticket
		);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function queue_delete(
		$queue = '',
		$ifUnused = FALSE,
		$ifEmpty = FALSE,
		$nowait = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::queue_delete($queue, $ifUnused, $ifEmpty, $nowait, $ticket);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function queue_purge(
		$queue = '',
		$nowait = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::queue_purge($queue, $nowait, $ticket);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_ack(
		$deliveryTag,
		$multiple = FALSE
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		parent::basic_ack($deliveryTag, $multiple);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_nack(
		$deliveryTag,
		$multiple = FALSE,
		$requeue = FALSE
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		parent::basic_nack($deliveryTag, $multiple, $requeue);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_cancel(
		$consumerTag,
		$nowait = FALSE,
		$noreturn = FALSE
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::basic_cancel($consumerTag, $nowait);
	}

	/**
	 * Starts a queue consumer
	 *
	 * @param string $queue
	 * @param string $consumerTag
	 * @param bool $noLocal
	 * @param bool $noAck
	 * @param bool $exclusive
	 * @param bool $nowait
	 * @param callable|null $callback
	 * @param int|null $ticket
	 * @param array $arguments
	 * @return mixed|string
	 */
	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_consume(
		$queue = '',
		$consumerTag = '',
		$noLocal = FALSE,
		$noAck = FALSE,
		$exclusive = FALSE,
		$nowait = FALSE,
		$callback = NULL,
		$ticket = NULL,
		$arguments = []
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::basic_consume(
			$queue,
			$consumerTag,
			$noLocal,
			$noAck,
			$exclusive,
			$nowait,
			$callback,
			$ticket,
			$arguments
		);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_get(
		$queue = '',
		$noAck = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::basic_get(
			$queue,
			$noAck,
			$ticket
		);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_qos(
		$prefetchSize,
		$prefetchCount,
		$AGlobal
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::basic_qos($prefetchSize, $prefetchCount, $AGlobal);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_recover($requeue = FALSE)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::basic_recover($requeue);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_reject(
		$deliveryTag,
		$requeue
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		parent::basic_reject($deliveryTag, $requeue);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	protected function basic_return(
		$args,
		$msg
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		return parent::basic_return($args, $msg);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function tx_commit()
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::tx_commit();
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function tx_rollback()
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::tx_rollback();
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function confirm_select($nowait = FALSE)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::confirm_select($nowait);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function tx_select()
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::tx_select();
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	public function dispatch(
		$methodSig,
		$args,
		$content
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();

		return parent::dispatch($methodSig, $args, $content);
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName
	public function basic_publish(
		$msg,
		$exchange = '',
		$routingKey = '',
		$mandatory = FALSE,
		$immediate = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + \get_defined_vars();
		parent::basic_publish($msg, $exchange, $routingKey, $mandatory, $immediate, $ticket);
	}

}
