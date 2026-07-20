<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\MemoryChecker\MemoryConsumptionChecker;
use OldSound\RabbitMqBundle\MemoryChecker\NativeMemoryUsageProvider;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

abstract class BaseConsumer extends BaseAmqp implements DequeuerInterface
{
    /** @var int */
    protected $target;

    /** @var int */
    protected $consumed = 0;

    /** @var callable */
    protected $callback;

    /** @var bool */
    protected $forceStop = false;

    /** @var int */
    protected $idleTimeout = 0;

    /** @var int */
    protected $idleTimeoutExitCode;

    /**
     * @var int|null $memoryLimit
     */
    protected $memoryLimit = null;

    /**
     * @var \DateTime|null DateTime after which the consumer will gracefully exit. "Gracefully" means, that
     *      any currently running consumption will not be interrupted.
     */
    protected $gracefulMaxExecutionDateTime;

    /**
     * @var int Exit code used, when consumer is closed by the Graceful Max Execution Timeout feature.
     */
    protected $gracefulMaxExecutionTimeoutExitCode = 0;

    /**
     * @var int|null
     */
    protected $timeoutWait;

    /**
     * @var \DateTime|null
     */
    protected $lastActivityDateTime;

    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Set the memory limit
     *
     * @param int $memoryLimit
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * Get the memory limit
     *
     * @return int|null
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * @param int $msgAmount
     * @throws \ErrorException
     */
    public function start($msgAmount = 0)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        while ($this->getChannel()->is_consuming()) {
            $this->getChannel()->wait();
        }
    }

    /**
     * Consume the message
     *
     * @param   int     $msgAmount
     *
     * @return  int
     *
     * @throws  AMQPTimeoutException
     */
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        $this->setLastActivityDateTime(new \DateTime());
        while ($this->getChannel()->is_consuming()) {
            $this->dispatchEvent(OnConsumeEvent::NAME, new OnConsumeEvent($this));
            $this->maybeStopConsumer();

            /*
             * Be careful not to trigger ::wait() with 0 or less seconds, when
             * graceful max execution timeout is being used.
             */

            $waitTimeout = $this->chooseWaitTimeout();
            if ($this->gracefulMaxExecutionDateTime
                && $waitTimeout < 1
            ) {
                return $this->gracefulMaxExecutionTimeoutExitCode;
            }

            if (!$this->forceStop) {
                try {
                    $this->getChannel()->wait(null, false, $waitTimeout);
                    $this->setLastActivityDateTime(new \DateTime());
                } catch (AMQPTimeoutException $e) {
                    $now = time();

                    if ($this->gracefulMaxExecutionDateTime
                        && $this->gracefulMaxExecutionDateTime <= new \DateTime("@$now")
                    ) {
                        return $this->gracefulMaxExecutionTimeoutExitCode;
                    } elseif ($this->getIdleTimeout()
                        && ($this->getLastActivityDateTime()->getTimestamp() + $this->getIdleTimeout() <= $now)
                    ) {
                        $idleEvent = new OnIdleEvent($this);
                        $this->dispatchEvent(OnIdleEvent::NAME, $idleEvent);

                        if ($idleEvent->isForceStop()) {
                            if (null !== $this->getIdleTimeoutExitCode()) {
                                return $this->getIdleTimeoutExitCode();
                            } else {
                                throw $e;
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Tell the server you are going to stop consuming.
     *
     * It will finish up the last message and not send you any more.
     */
    public function stopConsuming()
    {
        // This gets stuck and will not exit without the last two parameters set.
        $this->getChannel()->basic_cancel($this->getConsumerTag(), false, true);
    }

    protected function setupConsumer()
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }
        $this->getChannel()->basic_consume($this->queueOptions['name'], $this->getConsumerTag(), false, $this->consumerOptions['no_ack'], false, false, [$this, 'processMessage']);
    }

    public function processMessage(AMQPMessage $msg)
    {
        //To be implemented by descendant classes
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

            return;
        }

        if (!is_null($this->getMemoryLimit()) && $this->isRamAlmostOverloaded()) {
            // Also raise the force-stop flag so the consume() loop exits instead
            // of entering a wait() that can block forever on a cancelled consumer.
            $this->forceStopConsumer();
            $this->stopConsuming();
        }
    }

    /**
     * Checks if memory in use is greater or equal than memory allowed for this process
     *
     * @return boolean
     */
    protected function isRamAlmostOverloaded()
    {
        $memoryManager = new MemoryConsumptionChecker(new NativeMemoryUsageProvider());

        return $memoryManager->isRamAlmostOverloaded($this->getMemoryLimit().'M', '5M');
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
        $this->forceStop = true;
    }

    /**
     * Sets the qos settings for the current channel
     * Consider that prefetchSize and global do not work with rabbitMQ version <= 8.0
     *
     * @param int $prefetchSize
     * @param int $prefetchCount
     * @param bool $global
     */
    public function setQosOptions($prefetchSize = 0, $prefetchCount = 0, $global = false)
    {
        $this->getChannel()->basic_qos($prefetchSize, $prefetchCount, $global);
    }

    public function setIdleTimeout($idleTimeout)
    {
        $this->idleTimeout = $idleTimeout;
    }

    /**
     * Set exit code to be returned when there is a timeout exception
     *
     * @param int|null $idleTimeoutExitCode
     */
    public function setIdleTimeoutExitCode($idleTimeoutExitCode)
    {
        $this->idleTimeoutExitCode = $idleTimeoutExitCode;
    }

    public function getIdleTimeout()
    {
        return $this->idleTimeout;
    }

    /**
     * Get exit code to be returned when there is a timeout exception
     *
     * @return int|null
     */
    public function getIdleTimeoutExitCode()
    {
        return $this->idleTimeoutExitCode;
    }

    /**
     * @param \DateTime|null $dateTime
     */
    public function setGracefulMaxExecutionDateTime(?\DateTime $dateTime = null)
    {
        $this->gracefulMaxExecutionDateTime = $dateTime;
    }

    /**
     * @param int $secondsInTheFuture
     */
    public function setGracefulMaxExecutionDateTimeFromSecondsInTheFuture($secondsInTheFuture)
    {
        $this->setGracefulMaxExecutionDateTime(new \DateTime("+{$secondsInTheFuture} seconds"));
    }

    /**
     * @param int $exitCode
     */
    public function setGracefulMaxExecutionTimeoutExitCode($exitCode)
    {
        $this->gracefulMaxExecutionTimeoutExitCode = $exitCode;
    }

    public function setTimeoutWait(int $timeoutWait): void
    {
        $this->timeoutWait = $timeoutWait;
    }

    /**
     * @return \DateTime|null
     */
    public function getGracefulMaxExecutionDateTime()
    {
        return $this->gracefulMaxExecutionDateTime;
    }

    /**
     * @return int
     */
    public function getGracefulMaxExecutionTimeoutExitCode()
    {
        return $this->gracefulMaxExecutionTimeoutExitCode;
    }

    public function getTimeoutWait(): ?int
    {
        return $this->timeoutWait;
    }

    /**
     * Resets the consumed property.
     * Use when you want to call start() or consume() multiple times.
     */
    public function resetConsumed()
    {
        $this->consumed = 0;
    }

    /**
     * Choose the timeout wait (in seconds) to use for the $this->getChannel()->wait() method.
     */
    private function chooseWaitTimeout(): int
    {
        if ($this->gracefulMaxExecutionDateTime) {
            $allowedExecutionDateInterval = $this->gracefulMaxExecutionDateTime->diff(new \DateTime());
            $allowedExecutionSeconds = $allowedExecutionDateInterval->days * 86400
                + $allowedExecutionDateInterval->h * 3600
                + $allowedExecutionDateInterval->i * 60
                + $allowedExecutionDateInterval->s;

            if (!$allowedExecutionDateInterval->invert) {
                $allowedExecutionSeconds *= -1;
            }

            /*
             * Respect the idle timeout if it's set and if it's less than
             * the remaining allowed execution.
             */
            if ($this->getIdleTimeout()
                && $this->getIdleTimeout() < $allowedExecutionSeconds
            ) {
                $waitTimeout = $this->getIdleTimeout();
            } else {
                $waitTimeout = $allowedExecutionSeconds;
            }
        } else {
            $waitTimeout = $this->getIdleTimeout();
        }

        if (!is_null($this->getTimeoutWait()) && $this->getTimeoutWait() > 0) {
            $waitTimeout = min($waitTimeout, $this->getTimeoutWait());
        }
        return $waitTimeout;
    }

    public function setLastActivityDateTime(\DateTime $dateTime)
    {
        $this->lastActivityDateTime = $dateTime;
    }

    protected function getLastActivityDateTime(): ?\DateTime
    {
        return $this->lastActivityDateTime;
    }
}
