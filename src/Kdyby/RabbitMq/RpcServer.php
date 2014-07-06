<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onStart(RpcServer $self)
 * @method onConsume(RpcServer $self, AMQPMessage $msg)
 * @method onReply(RpcServer $self, $result)
 */
class RpcServer extends BaseConsumer
{

	/**
	 * @var array
	 */
	public $onConsume = array();

	/**
	 * @var array
	 */
	public $onReply = array();

	/**
	 * @var array
	 */
	public $onStart = array();

	/**
	 * @var array
	 */
	public $onStop = array();



	public function initServer($name)
	{
		$this->setExchangeOptions(array('name' => $name, 'type' => 'direct'));
		$this->setQueueOptions(array('name' => $name . '-queue'));
	}



	public function start($msgAmount = 0)
	{
		$this->target = $msgAmount;

		$this->setupConsumer();

		$this->onStart($this);
		while (count($this->getChannel()->callbacks)) {
			$this->maybeStopConsumer();

			try {
				$this->getChannel()->wait(NULL, FALSE, $this->getIdleTimeout());
			} catch (AMQPTimeoutException $e) {
				// nothing bad happened, right?
			}
		}
	}



	public function processMessage(AMQPMessage $msg)
	{
		try {
			$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
			$this->onConsume($this, $msg);
			$result = call_user_func($this->callback, $msg);
			$this->onReply($this, $result);
			$this->sendReply(serialize($result), $msg->get('reply_to'), $msg->get('correlation_id'));
			$this->consumed++;
			$this->maybeStopConsumer();

		} catch (\Exception $e) {
			$this->sendReply('error: ' . $e->getMessage(), $msg->get('reply_to'), $msg->get('correlation_id'));
		}
	}



	protected function sendReply($result, $client, $correlationId)
	{
		$this->getChannel()->basic_publish(
			new AMQPMessage($result, array(
				'content_type' => 'text/plain',
				'correlation_id' => $correlationId
			)),
			$exchange = '',
			$client
		);
	}

}
