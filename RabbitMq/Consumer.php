<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Declarations\BatchConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\ConsumerDef;
use OldSound\RabbitMqBundle\Declarations\RpcConsumeOptions;
use OldSound\RabbitMqBundle\Event\AfterProcessingMessagesEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\Event\ReceiverArgumentsEvent;
use OldSound\RabbitMqBundle\EventDispatcherAwareTrait;
use OldSound\RabbitMqBundle\ExecuteReceiverStrategy\BatchExecuteReceiverStrategy;
use OldSound\RabbitMqBundle\ExecuteReceiverStrategy\ExecuteReceiverStrategyInterface;
use OldSound\RabbitMqBundle\ExecuteReceiverStrategy\SingleExecuteReceiverStrategy;
use OldSound\RabbitMqBundle\RabbitMq\Exception\ReceiverException;
use OldSound\RabbitMqBundle\Receiver\ArgumentResolver;
use OldSound\RabbitMqBundle\Receiver\ArgumentResolverInterface;
use OldSound\RabbitMqBundle\ReceiverExecutor\BatchReceiverResultHandler;
use OldSound\RabbitMqBundle\Receiver\ReceiverInterface;
use OldSound\RabbitMqBundle\ReceiverExecutor\ReceiverResultHandlerInterface;
use OldSound\RabbitMqBundle\ReceiverExecutor\ReplyReceiverResultHandler;
use OldSound\RabbitMqBundle\ReceiverExecutor\SingleReceiverResultHandler;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Consumer
{
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    /** @var ConsumerDef */
    protected $consumerDef;
    /** @var \AMQPChannel */
    protected $channel;

    /** @var ExecuteReceiverStrategyInterface[] */
    protected $executeReceiverStrategies;
    /** @var bool */
    protected $forceStop = false;

    /** @var ArgumentResolverInterface */
    protected $argumentResolver;

    /**
     * @var \DateTime|null DateTime after which the consumer will gracefully exit. "Gracefully" means, that
     *      any currently running consumption will not be interrupted.
     */
    public $gracefulMaxExecutionDateTime;
    /** @var int Exit code used, when consumer is closed by the Graceful Max Execution Timeout feature. */
    public $gracefulMaxExecutionTimeoutExitCode = 0;
    /** @var int|null */
    public $timeoutWait;
    /** @var int */
    public $idleTimeout = 0;
    /** @var int|null */
    public $idleTimeoutExitCode;

    public function __construct(ConsumerDef $consumerDef, ArgumentResolverInterface $argumentResolver = null)
    {
        $this->consumerDef = $consumerDef;
        $this->logger = new NullLogger();
        $this->argumentResolver = $argumentResolver ?? new ArgumentResolver();
    }

    protected function setup(): Consumer
    {
        $this->channel = AMQPConnectionFactory::getChannelFromConnection($this->consumerDef->connection);

        foreach($this->consumerDef->consumeOptions as $index => $options) {
            $this->channel->basic_qos($options->qosPrefetchSize, $options->qosPrefetchCount, false);

            $options->consumerTag = $options->consumerTag ?? sprintf("PHPPROCESS_%s_%s_%s", gethostname(), getmypid(), $index);

            $executeReceiverStrategy = $options instanceof BatchConsumeOptions ?
                new BatchExecuteReceiverStrategy($options->batchCount) :
                new SingleExecuteReceiverStrategy();
            $this->executeReceiverStrategies[$index] = $executeReceiverStrategy;

            $executeReceiverStrategy->setReceiver(fn (array $messages) => $this->runReceiver($messages, $options));

            $consumerTag = $this->channel->basic_consume(
                $options->queue,
                $options->consumerTag,
                $options->noLocal,
                $options->noAck,
                $options->exclusive,
                false,
                fn (AMQPMessage $message) => $this->messageCallback($message, $executeReceiverStrategy)
            );

            $options->consumerTag = $consumerTag;
        }

        return $this;
    }

    private function runReceiver(array $messages, ConsumeOptions $options)
    {
        try {
            $arguments = $this->argumentResolver->getArguments($messages, $options);

            // $messages
            $event = new ReceiverArgumentsEvent($arguments, $options);
            $this->dispatchEvent($event, ReceiverArgumentsEvent::NAME);
            if ($event->isForceStop()) {
                throw new Exception\StopConsumerException();
            }

            $receiver = $event->getOptions()->receiver;
            $arguments = $event->getArguments();

            $result = $receiver(...$arguments);
        } catch (Exception\ReceiverException $e) {
            if (null !== $e->getFlag()) {
                Utils::handleReceiverMessages($messages[0]->getChannel(), array_fill(0, count($messages), $e->getFlag()), true);
            }
            $this->logger->info('Receiver exception', [
                'exception' => $e,
                'amqp' => $this->createLoggerExtraContext($messages, $options)
            ]);
        } catch (Exception\StopConsumerException $e) {
            $this->logger->info('Consumer requested stop', [
                'exception' => $e,
                'amqp' => $this->createLoggerExtraContext($messages, $options)
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Throw exception while process messages', [
                'exception' => $e,
                'amqp' => $this->createLoggerExtraContext($messages, $options)
            ]);
            throw $e;
        }

        $receiverResultHandler = $this->createReceiverResultHandler($options);
        $receiverResultHandler->handle($result, $messages, $options);

        //$this->logger->info('Queue messages processed', ['amqp' => [...$this->createLoggerExtraContext($messages), 'flags' => $flags]]);
        $event = new AfterProcessingMessagesEvent($messages); // TODO add flag code
        $this->dispatchEvent($event, AfterProcessingMessagesEvent::NAME);
        if ($event->isForceStop()) {
            throw new StopConsumerException();
        }
    }


    private function createLoggerExtraContext(array $messages, ConsumeOptions $options): array
    {
        return [
            'consumer' => $this->consumerDef->name,
            'queue' => $options->queue,
            'messages' => $messages
        ];
    }

    private function messageCallback(AMQPMessage $message, ExecuteReceiverStrategyInterface $executeReceiverStrategy)
    {
        $executeReceiverStrategy->onConsumeCallback($message);
        $executeReceiverStrategy->onMessageProcessed($message);

        $this->maybeStopConsumer();
    }

    private function createReceiverResultHandler(ConsumeOptions $options): ReceiverResultHandlerInterface
    {
        if ($options instanceof BatchConsumeOptions) {
            return new BatchReceiverResultHandler();
        } else if ($options instanceof RpcConsumeOptions) {
            return new ReplyReceiverResultHandler($options);
        } else {
            return new SingleReceiverResultHandler();
        }
    }

    /**
     * @throws  AMQPTimeoutException
     */
    public function startConsume(): int
    {
        $this->setup();
        $lastActivityDateTime = new \DateTime();
        while ($this->channel->is_consuming()) {
            $event = new OnConsumeEvent();
            $this->dispatchEvent($event, OnConsumeEvent::NAME);
            if ($event->isForceStop()) {
                break;
            }
            $this->maybeStopConsumer();

            if ($this->forceStop) {
                break;
            }
            /*
             * Be careful not to trigger ::wait() with 0 or less seconds, when
             * graceful max execution timeout is being used.
             */
            $waitTimeout = $this->chooseWaitTimeout();
            if ($this->gracefulMaxExecutionDateTime && $waitTimeout < 1) {
                return $this->gracefulMaxExecutionTimeoutExitCode;
            }

            try {
                $this->channel->wait(null, false, $waitTimeout);
                $lastActivityDateTime = new \DateTime();
                if ($this->forceStop) {
                    break;
                }
            } catch (AMQPTimeoutException $e) {
                $now = new \DateTime();
                if ($this->gracefulMaxExecutionDateTime && $this->gracefulMaxExecutionDateTime <= $now) {
                    return $this->gracefulMaxExecutionTimeoutExitCode;
                }

                if ($this->idleTimeout && ($lastActivityDateTime->getTimestamp() + $this->idleTimeout <= $now->getTimestamp())) {
                    $idleEvent = new OnIdleEvent();
                    $this->dispatchEvent($idleEvent, OnIdleEvent::NAME);

                    if ($idleEvent->isForceStop()) {
                        if (null !== $this->idleTimeoutExitCode) {
                            return $this->idleTimeoutExitCode;
                        } else {
                            throw $e;
                        }
                    }
                }
            }
        }

        return 0;
    }

    protected function maybeStopConsumer()
    {
        if ($this->forceStop) {
            $this->stopConsuming();
        }
    }

    public function forceStopConsumer()
    {
        $this->forceStop = true;
    }

    public function stopConsuming($immediately = false)
    {
        if (false === $immediately) {
            foreach ($this->executeReceiverStrategies as $executeReceiverStrategy) {
                $executeReceiverStrategy->onStopConsuming();
            }
        }

        foreach ($this->consumerDef->consumeOptions as $options) {
            $this->channel->basic_cancel($options->consumerTag, false, true);
            $options->consumerTag = null;
        }
    }

    public function setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(int $secondsInTheFuture)
    {
        $this->gracefulMaxExecutionDateTime = new \DateTime("+{$secondsInTheFuture} seconds");
    }

    /**
     * Choose the timeout wait (in seconds) to use for the $this->getChannel()->wait() method.
     */
    private function chooseWaitTimeout(): int
    {
        if ($this->gracefulMaxExecutionDateTime) {
            $allowedExecutionSeconds = $this->gracefulMaxExecutionDateTime->getTimestamp() - time();

            /*
             * Respect the idle timeout if it's set and if it's less than
             * the remaining allowed execution.
             */
            $waitTimeout = $this->idleTimeout && $this->idleTimeout < $allowedExecutionSeconds
                ? $this->idleTimeout
                : $allowedExecutionSeconds;
        } else {
            $waitTimeout = $this->idleTimeout;
        }

        if (!is_null($this->timeoutWait) && $this->timeoutWait > 0) {
            $waitTimeout = min($waitTimeout, $this->timeoutWait);
        }
        return $waitTimeout;
    }
}
