<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use PhpAmqpLib\Exception\AMQPTimeoutException;

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
                    if ($isConsuming) {
                        $isConsuming = false;
                    } elseif (null !== $this->getIdleTimeoutExitCode()) {
                        return $this->getIdleTimeoutExitCode();
                    } else {
                        throw $e;
                    }
                }
            }

            $timeoutWanted = ($isConsuming) ? $this->getTimeoutWait() : $this->getIdleTimeout();
        }
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