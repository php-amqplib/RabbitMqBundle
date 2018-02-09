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
    const TIMEOUT_TYPE_IDLE = 'idle';
    const TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION = 'graceful-max-execution';

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
     * @var int $memoryLimit
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
     * @return int
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * @param $callback
     */
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
     * @param int $msgAmount
     */
    public function start($msgAmount = 0)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        while (count($this->getChannel()->callbacks)) {
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

        while (count($this->getChannel()->callbacks)) {
            $this->dispatchEvent(OnConsumeEvent::NAME, new OnConsumeEvent($this));
            $this->maybeStopConsumer();

            /*
             * Be careful not to trigger ::wait() with 0 or less seconds, when
             * graceful max execution timeout is being used.
             */
            $waitTimeout = $this->chooseWaitTimeout();
            if (
                $waitTimeout['timeoutType'] === self::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION
                && $waitTimeout['seconds'] < 1
            ) {
                return $this->gracefulMaxExecutionTimeoutExitCode;
            }

            if (!$this->forceStop) {
                try {
                    $this->getChannel()->wait(null, false, $waitTimeout['seconds']);
                } catch (AMQPTimeoutException $e) {
                    if (self::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION === $waitTimeout['timeoutType']) {
                        return $this->gracefulMaxExecutionTimeoutExitCode;
                    }

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
        $this->getChannel()->basic_consume($this->queueOptions['name'], $this->getConsumerTag(), false, false, false, false, array($this, 'processMessage'));
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
     * Resets the consumed property.
     * Use when you want to call start() or consume() multiple times.
     */
    public function resetConsumed()
    {
        $this->consumed = 0;
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

    /**
     * @param \DateTime|null $dateTime
     */
    public function setGracefulMaxExecutionDateTime(\DateTime $dateTime = null)
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

    /**
     * Choose the timeout to use for the $this->getChannel()->wait() method.
     *
     * @return array Of structure
     *  {
     *      timeoutType: string; // one of self::TIMEOUT_TYPE_*
     *      seconds: int;
     *  }
     */
    private function chooseWaitTimeout()
    {
        if ($this->gracefulMaxExecutionDateTime) {
            $allowedExecutionDateInterval = $this->gracefulMaxExecutionDateTime->diff(new \DateTime());
            $allowedExecutionSeconds =  $allowedExecutionDateInterval->days * 86400
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
            if (
                $this->getIdleTimeout()
                && $this->getIdleTimeout() < $allowedExecutionSeconds
            ) {
                return array(
                    'timeoutType' => self::TIMEOUT_TYPE_IDLE,
                    'seconds' => $this->getIdleTimeout(),
                );
            }

            return array(
                'timeoutType' => self::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION,
                'seconds' => $allowedExecutionSeconds,
            );
        }

        return array(
            'timeoutType' => self::TIMEOUT_TYPE_IDLE,
            'seconds' => $this->getIdleTimeout(),
        );
    }
}
