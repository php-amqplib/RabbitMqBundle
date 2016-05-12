<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\RabbitMq;

use Kdyby;



class ConnectionMock extends Kdyby\RabbitMq\Connection
{

	protected function doCreateChannel($id)
	{
		return new ChannelMock($this, $id);
	}

}



class ChannelMock extends Kdyby\RabbitMq\Channel
{

	public $calls = array();



	protected function channel_alert($args)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
		parent::channel_alert($args);
	}



	protected function channel_close($args)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
		parent::channel_close($args);
	}



	protected function channel_flow($args)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
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
	) {
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::exchange_declare($exchange, $type, $passive, $durable, $auto_delete, $internal, $nowait, $arguments, $ticket);
	}



	public function exchange_delete(
		$exchange,
		$if_unused = FALSE,
		$nowait = FALSE,
		$ticket = NULL
	) {
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::exchange_delete($exchange, $if_unused, $nowait, $ticket);
	}



	public function exchange_bind(
		$destination,
		$source,
		$routing_key = "",
		$nowait = FALSE,
		$arguments = NULL,
		$ticket = NULL
	) {
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::exchange_bind($destination, $source, $routing_key, $nowait, $arguments, $ticket);
	}



	public function exchange_unbind($destination, $source, $routing_key = "", $arguments = NULL, $ticket = NULL)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::exchange_unbind($destination, $source, $routing_key, $arguments, $ticket);
	}



	public function queue_bind($queue, $exchange, $routing_key = "", $nowait = FALSE, $arguments = NULL, $ticket = NULL)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::queue_bind($queue, $exchange, $routing_key, $nowait, $arguments, $ticket);
	}



	public function queue_unbind($queue, $exchange, $routing_key = "", $arguments = NULL, $ticket = NULL)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

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
	) {
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::queue_declare($queue, $passive, $durable, $exclusive, $auto_delete, $nowait, $arguments, $ticket);
	}



	public function queue_delete($queue = "", $if_unused = FALSE, $if_empty = FALSE, $nowait = FALSE, $ticket = NULL)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::queue_delete($queue, $if_unused, $if_empty, $nowait, $ticket);
	}



	public function queue_purge($queue = "", $nowait = FALSE, $ticket = NULL)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::queue_purge($queue, $nowait, $ticket);
	}



	public function basic_ack($delivery_tag, $multiple = FALSE)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
		parent::basic_ack($delivery_tag, $multiple);
	}



	public function basic_nack($delivery_tag, $multiple = FALSE, $requeue = FALSE)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
		parent::basic_nack($delivery_tag, $multiple, $requeue);
	}



	public function basic_cancel($consumer_tag, $nowait = FALSE, $noreturn = false)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::basic_cancel($consumer_tag, $nowait);
	}



	public function basic_consume(
		$queue = "",
		$consumer_tag = "",
		$no_local = FALSE,
		$no_ack = FALSE,
		$exclusive = FALSE,
		$nowait = FALSE,
		$callback = NULL,
		$ticket = NULL,
		$arguments = array()
	) {
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::basic_consume($queue, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, $callback, $ticket, $arguments);
	}



	public function basic_get($queue = "", $no_ack = FALSE, $ticket = NULL)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::basic_get($queue, $no_ack, $ticket);
	}



	public function basic_qos($prefetch_size, $prefetch_count, $a_global)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::basic_qos($prefetch_size, $prefetch_count, $a_global);
	}



	public function basic_recover($requeue = FALSE)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::basic_recover($requeue);
	}



	public function basic_reject($delivery_tag, $requeue)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
		parent::basic_reject($delivery_tag, $requeue);
	}



	protected function basic_return($args, $msg)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
		parent::basic_return($args, $msg);
	}



	public function tx_commit()
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::tx_commit();
	}



	public function tx_rollback()
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::tx_rollback();
	}



	public function confirm_select($nowait = FALSE)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::confirm_select($nowait);
	}



	public function tx_select()
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::tx_select();
	}



	public function dispatch($method_sig, $args, $content)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();

		return parent::dispatch($method_sig, $args, $content);
	}



	public function basic_publish($msg, $exchange = '', $routingKey = '', $mandatory = FALSE, $immediate = FALSE, $ticket = NULL)
	{
		$this->calls[] = array(__FUNCTION__) + get_defined_vars();
		parent::basic_publish($msg, $exchange, $routingKey, $mandatory, $immediate, $ticket);
	}

}



class Callback
{

	public static $accepted = array();



	public function __invoke($message)
	{
		self::$accepted[] = func_get_args();
	}



	public function process($message)
	{
		self::$accepted[] = func_get_args();
	}



	public static function staticProcess($message)
	{
		self::$accepted[] = func_get_args();
	}

}
