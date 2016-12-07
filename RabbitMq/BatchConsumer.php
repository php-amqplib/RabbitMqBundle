<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class BatchConsumer extends Consumer
{
    /**
     * @var int
     */
    protected $prefetchCount;

    /**
     * @var int
     */
    protected $timeoutWait;

    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @var int
     */
    protected $batchCounter = 0;

    /**
     * @var \Closure
     */
    protected $batchCallback;

    /**
     * @inheritDoc
     */
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        $isConsuming = false;
        $timeoutWanted = $this->getTimeoutWait();
        while (count($this->getChannel()->callbacks)) {
            $this->dispatchEvent(OnConsumeEvent::NAME, new OnConsumeEvent($this));
            $this->maybeStopConsumer();
            if (!$this->forceStop) {
                try {
                    $this->consumeMessage($timeoutWanted);
                    $isConsuming = true;
                } catch (AMQPTimeoutException $e) {
                    $this->batchConsume();
                    if ($isConsuming) {
                        $isConsuming = false;
                    } elseif (null !== $this->getIdleTimeoutExitCode()) {
                        return $this->getIdleTimeoutExitCode();
                    } else {
                        throw $e;
                    }
                }
            }

            if ($this->isCompleteBatch($isConsuming)) {
                $this->batchConsume();
            }

            $timeoutWanted = ($isConsuming) ? $this->getTimeoutWait() : $this->getIdleTimeout();
        }
    }

    /**
     * @return  void
     *
     * @throws  \Exception
     */
    protected function batchConsume()
    {
        if ($this->batchCounter == 0) {
            return;
        }

        try {
            call_user_func($this->batchCallback);
            $this->resetBatch();
        } catch (\Exception $exception) {
            $this->resetBatch(true);
            throw $exception;
        }
    }

    /**
     * @param   bool    $isConsuming
     *
     * @return  bool
     */
    protected function isCompleteBatch($isConsuming)
    {
        return $isConsuming && $this->batchCounter != 0 && $this->batchCounter%$this->prefetchCount == 0;
    }

    /**
     * @inheritDoc
     */
    public function stopConsuming()
    {
        $this->batchConsume();

        parent::stopConsuming();
    }

    /**
     * @inheritDoc
     */
    protected function handleProcessMessage(AMQPMessage $msg, $processFlag)
    {
        $isRejectedOrReQueued = false;

        if ($processFlag === ConsumerInterface::MSG_REJECT_REQUEUE || false === $processFlag) {
            // Reject and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
            $isRejectedOrReQueued = true;
        } else if ($processFlag === ConsumerInterface::MSG_SINGLE_NACK_REQUEUE) {
            // NACK and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
            $isRejectedOrReQueued = true;
        } else if ($processFlag === ConsumerInterface::MSG_REJECT) {
            // Reject and drop
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
        }

        $this->consumed++;
        $this->maybeStopConsumer();
        if (!$isRejectedOrReQueued) {
            $this->addDeliveryTag($msg);
        }

        if (!is_null($this->getMemoryLimit()) && $this->isRamAlmostOverloaded()) {
            $this->stopConsuming();
        }
    }

    /**
     * @param   bool    $hasExceptions
     *
     * @return  void
     */
    private function resetBatch($hasExceptions = false)
    {
        if ($hasExceptions) {
            array_map(function(AMQPMessage $msg) {
                $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
            }, $this->messages);
        } else {
            array_map(function(AMQPMessage $msg) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }, $this->messages);
        }

        $this->messages = array();
        $this->batchCounter = 0;
    }

    /**
     * @param   AMQPMessage $message
     *
     * @return  void
     */
    private function addDeliveryTag(AMQPMessage $message)
    {
        $this->messages[$this->batchCounter++] = $message;
    }

    /**
     * @param   \Closure    $callback
     *
     * @return  $this
     */
    public function setBatchCallback($callback)
    {
        $this->batchCallback = $callback;

        return $this;
    }

    /**
     * @param   int     $timeout
     *
     * @return  $this
     */
    public function setTimeoutWait($timeout)
    {
        $this->timeoutWait = $timeout;

        return $this;
    }

    /**
     * @param   int $amount
     *
     * @return  $this
     */
    public function setPrefetchCount($amount)
    {
        $this->prefetchCount = $amount;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeoutWait()
    {
        return $this->timeoutWait;
    }

    /**
     * @return int
     */
    public function getPrefetchCount()
    {
        return $this->prefetchCount;
    }

    /**
     * @param   int     $timeout
     *
     * @return  void
     *
     * @throws  AMQPTimeoutException
     */
    private function consumeMessage($timeout)
    {
        $this->getChannel()->wait(null, false, $timeout);
    }
}