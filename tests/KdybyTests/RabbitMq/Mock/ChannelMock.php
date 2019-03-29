<?php declare(strict_types = 1);

namespace KdybyTests\RabbitMq\Mock;


class ChannelMock extends \Kdyby\RabbitMq\Channel
{

	public $calls = [];


	protected function channel_alert($args)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::channel_alert($args);
	}


	protected function channel_close($args)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::channel_close($args);
	}


	protected function channel_flow($args)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::channel_flow($args);
	}


	public function exchange_declare(
		$exchange,
		$type,
		$passive = FALSE,
		$durable = FALSE,
		$auto_delete = TRUE,
		$internal = FALSE,
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::exchange_declare(
			$exchange, $type, $passive, $durable, $auto_delete, $internal, $nowait, $arguments, $ticket
		);
	}


	public function exchange_delete(
		$exchange,
		$if_unused = FALSE,
		$nowait = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::exchange_delete($exchange, $if_unused, $nowait, $ticket);
	}


	public function exchange_bind(
		$destination,
		$source,
		$routing_key = "",
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::exchange_bind($destination, $source, $routing_key, $nowait, $arguments, $ticket);
	}


	public function exchange_unbind(
		$destination,
		$source,
		$routing_key = "",
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::exchange_unbind($destination, $source, $routing_key, $arguments, $ticket);
	}


	public function queue_bind(
		$queue,
		$exchange,
		$routing_key = "",
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::queue_bind($queue, $exchange, $routing_key, $nowait, $arguments, $ticket);
	}


	public function queue_unbind(
		$queue,
		$exchange,
		$routing_key = "",
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::queue_unbind($queue, $exchange, $routing_key, $arguments, $ticket);
	}


	public function queue_declare(
		$queue = "",
		$passive = FALSE,
		$durable = FALSE,
		$exclusive = FALSE,
		$auto_delete = TRUE,
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::queue_declare(
			$queue, $passive, $durable, $exclusive, $auto_delete, $nowait, $arguments, $ticket
		);
	}


	public function queue_delete(
		$queue = "",
		$if_unused = FALSE,
		$if_empty = FALSE,
		$nowait = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::queue_delete($queue, $if_unused, $if_empty, $nowait, $ticket);
	}


	public function queue_purge(
		$queue = "",
		$nowait = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::queue_purge($queue, $nowait, $ticket);
	}


	public function basic_ack(
		$delivery_tag,
		$multiple = FALSE
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::basic_ack($delivery_tag, $multiple);
	}


	public function basic_nack(
		$delivery_tag,
		$multiple = FALSE,
		$requeue = FALSE
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::basic_nack($delivery_tag, $multiple, $requeue);
	}


	public function basic_cancel(
		$consumer_tag,
		$nowait = FALSE,
		$noreturn = FALSE
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::basic_cancel($consumer_tag, $nowait);
	}


	/**
	 * Starts a queue consumer
	 *
	 * @param string $queue
	 * @param string $consumer_tag
	 * @param bool $no_local
	 * @param bool $no_ack
	 * @param bool $exclusive
	 * @param bool $nowait
	 * @param callable|null $callback
	 * @param int|null $ticket
	 * @param array $arguments
	 * @return mixed|string
	 */
	public function basic_consume(
		$queue = "",
		$consumer_tag = "",
		$no_local = FALSE,
		$no_ack = FALSE,
		$exclusive = FALSE,
		$nowait = FALSE,
		$callback = NULL,
		$ticket = NULL,
		$arguments = []
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::basic_consume(
			$queue, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, $callback, $ticket, $arguments
		);
	}


	public function basic_get(
		$queue = "",
		$no_ack = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::basic_get($queue, $no_ack, $ticket);
	}


	public function basic_qos(
		$prefetch_size,
		$prefetch_count,
		$a_global
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::basic_qos($prefetch_size, $prefetch_count, $a_global);
	}


	public function basic_recover($requeue = FALSE)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::basic_recover($requeue);
	}


	public function basic_reject(
		$delivery_tag,
		$requeue
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::basic_reject($delivery_tag, $requeue);
	}


	protected function basic_return(
		$args,
		$msg
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::basic_return($args, $msg);
	}


	public function tx_commit()
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::tx_commit();
	}


	public function tx_rollback()
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::tx_rollback();
	}


	public function confirm_select($nowait = FALSE)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::confirm_select($nowait);
	}


	public function tx_select()
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::tx_select();
	}


	public function dispatch(
		$method_sig,
		$args,
		$content
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();

		return parent::dispatch($method_sig, $args, $content);
	}


	public function basic_publish(
		$msg,
		$exchange = '',
		$routingKey = '',
		$mandatory = FALSE,
		$immediate = FALSE,
		$ticket = NULL
	)
	{
		$this->calls[] = [__FUNCTION__] + get_defined_vars();
		parent::basic_publish($msg, $exchange, $routingKey, $mandatory, $immediate, $ticket);
	}

}