<?php

namespace Kdyby\RabbitMq;

use Nette\Utils\Callback;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onStop(BaseConsumer $self)
 */
abstract class BaseConsumer extends AmqpMember
{

	/**
	 * @var array
	 */
	public $onStop = array();

	/**
	 * @var int
	 */
	protected $target;

	/**
	 * @var int
	 */
	protected $consumed = 0;

	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * @var int
	 */
	protected $forceStop = FALSE;

	/**
	 * @var int
	 */
	protected $idleTimeout = 0;

	/**
	 * @var array
	 */
	protected $qosOptions = array(
		'prefetchSize' => 0,
		'prefetchCount' => 0,
		'global' => FALSE
	);

	/**
	 * @var bool
	 */
	protected $qosDeclared = FALSE;



	public function setCallback($callback)
	{
		Callback::check($callback);
		$this->callback = $callback;
	}



	public function stopConsuming()
	{
		$this->getChannel()->basic_cancel($this->getConsumerTag());
		$this->onStop($this);
	}



	protected function setupConsumer()
	{
		if ($this->autoSetupFabric) {
			$this->setupFabric();
		}

		if ( ! $this->qosDeclared) {
			$this->qosDeclare();
		}

		$this->getChannel()->basic_consume(
			$this->queueOptions['name'],
			$this->getConsumerTag(),
			$noLocal = false,
			$noAck = false,
			$exclusive = false,
			$nowait = false,
			array($this, 'processMessage')
		);
	}



	protected function maybeStopConsumer()
	{
		if (extension_loaded('pcntl') && (defined('AMQP_WITHOUT_SIGNALS') ? !AMQP_WITHOUT_SIGNALS : true)) {
			if (!function_exists('pcntl_signal_dispatch')) {
				throw new \BadFunctionCallException("Function 'pcntl_signal_dispatch' is referenced in the php.ini 'disable_functions' and can't be called.");
			}

			pcntl_signal_dispatch();
		}

		if ($this->forceStop || ($this->consumed == $this->target && $this->target > 0)) {
			$this->stopConsuming();

		} else {
			return;
		}
	}



	public function setConsumerTag($tag)
	{
		$this->consumerTag = $tag;
	}



	public function getConsumerTag()
	{
		return $this->consumerTag;
	}



	public function forceStopConsumer()
	{
		$this->forceStop = TRUE;
	}



	/**
	 * Sets the qos settings for the current channel
	 * Consider that prefetchSize and global do not work with rabbitMQ version <= 8.0
	 *
	 * @param int $prefetchSize
	 * @param int $prefetchCount
	 * @param bool $global
	 */
	public function setQosOptions($prefetchSize = 0, $prefetchCount = 0, $global = FALSE)
	{
		$this->qosOptions = array(
			'prefetchSize' => $prefetchSize,
			'prefetchCount' => $prefetchCount,
			'global' => $global,
		);
	}



	protected function qosDeclare()
	{
		if (!array_filter($this->qosOptions)) {
			return;
		}

		$this->getChannel()->basic_qos(
			$this->qosOptions['prefetchSize'],
			$this->qosOptions['prefetchCount'],
			$this->qosOptions['global']
		);

		$this->qosDeclared = TRUE;
	}



	public function setIdleTimeout($seconds)
	{
		$this->idleTimeout = $seconds;
	}



	public function getIdleTimeout()
	{
		return $this->idleTimeout;
	}

}
