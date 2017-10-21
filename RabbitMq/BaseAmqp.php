<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\AMQPEvent;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseAmqp
{
    protected $conn;
    protected $ch;
    protected $consumerTag;
    protected $exchangeDeclared = false;
    protected $queueDeclared = false;
    protected $routingKey = '';
    protected $autoSetupFabric = true;
    protected $basicProperties = array('content_type' => 'text/plain', 'delivery_mode' => 2);

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $exchangeOptions = array(
        'passive' => false,
        'durable' => true,
        'auto_delete' => false,
        'internal' => false,
        'nowait' => false,
        'arguments' => null,
        'ticket' => null,
        'declare' => true,
    );

    protected $queueOptions = array(
        'name' => '',
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
        'nowait' => false,
        'arguments' => null,
        'ticket' => null,
        'declare' => true,
    );

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param AbstractConnection   $conn
     * @param AMQPChannel|null $ch
     * @param null             $consumerTag
     */
    public function __construct(AbstractConnection $conn, AMQPChannel $ch = null, $consumerTag = null)
    {
        $this->conn = $conn;
        $this->ch = $ch;

        if ($conn->connectOnConstruct()) {
            $this->getChannel();
        }

        $this->consumerTag = empty($consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $consumerTag;

        $this->logger = new NullLogger();
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if ($this->ch) {
            try {
                $this->ch->close();
            } catch (\Exception $e) {
                // ignore on shutdown
            }
        }

        if ($this->conn && $this->conn->isConnected()) {
            try {
                $this->conn->close();
            } catch (\Exception $e) {
                // ignore on shutdown
            }
        }
    }

    public function reconnect()
    {
        if (!$this->conn->isConnected()) {
            return;
        }

        $this->conn->reconnect();
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        if (empty($this->ch) || null === $this->ch->getChannelId()) {
            $this->ch = $this->conn->channel();
        }

        return $this->ch;
    }

    /**
     * @param  AMQPChannel $ch
     *
     * @return void
     */
    public function setChannel(AMQPChannel $ch)
    {
        $this->ch = $ch;
    }

    /**
     * @throws \InvalidArgumentException
     * @param  array                     $options
     * @return void
     */
    public function setExchangeOptions(array $options = array())
    {
        if (!isset($options['name'])) {
            throw new \InvalidArgumentException('You must provide an exchange name');
        }

        if (empty($options['type'])) {
            throw new \InvalidArgumentException('You must provide an exchange type');
        }

        $this->exchangeOptions = array_merge($this->exchangeOptions, $options);
    }

    /**
     * @param  array $options
     * @return void
     */
    public function setQueueOptions(array $options = array())
    {
        $this->queueOptions = array_merge($this->queueOptions, $options);
    }

    /**
     * @param  string $routingKey
     * @return void
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }

    public function setupFabric()
    {
        if (!$this->exchangeDeclared) {
            $this->exchangeDeclare();
        }

        if (!$this->queueDeclared) {
            $this->queueDeclare();
        }
    }

    /**
     * disables the automatic SetupFabric when using a consumer or producer
     */
    public function disableAutoSetupFabric()
    {
        $this->autoSetupFabric = false;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Declares exchange
     */
    protected function exchangeDeclare()
    {
        if ($this->exchangeOptions['declare']) {
            $this->getChannel()->exchange_declare(
                $this->exchangeOptions['name'],
                $this->exchangeOptions['type'],
                $this->exchangeOptions['passive'],
                $this->exchangeOptions['durable'],
                $this->exchangeOptions['auto_delete'],
                $this->exchangeOptions['internal'],
                $this->exchangeOptions['nowait'],
                $this->exchangeOptions['arguments'],
                $this->exchangeOptions['ticket']);

            $this->exchangeDeclared = true;
        }
    }

    /**
     * Declares queue, creates if needed
     */
    protected function queueDeclare()
    {
        if ($this->queueOptions['declare']) {
            list($queueName, ,) = $this->getChannel()->queue_declare($this->queueOptions['name'], $this->queueOptions['passive'],
                $this->queueOptions['durable'], $this->queueOptions['exclusive'],
                $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
                $this->queueOptions['arguments'], $this->queueOptions['ticket']);

            if (isset($this->queueOptions['routing_keys']) && count($this->queueOptions['routing_keys']) > 0) {
                foreach ($this->queueOptions['routing_keys'] as $routingKey) {
                    $this->queueBind($queueName, $this->exchangeOptions['name'], $routingKey);
                }
            } else {
                $this->queueBind($queueName, $this->exchangeOptions['name'], $this->routingKey);
            }

            $this->queueDeclared = true;
        }
    }

    /**
     * Binds queue to an exchange
     *
     * @param string $queue
     * @param string $exchange
     * @param string $routing_key
     */
    protected function queueBind($queue, $exchange, $routing_key)
    {
        // queue binding is not permitted on the default exchange
        if ('' !== $exchange) {
            $this->getChannel()->queue_bind($queue, $exchange, $routing_key);
        }
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return BaseAmqp
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @param string $eventName
     * @param AMQPEvent  $event
     */
    protected function dispatchEvent($eventName, AMQPEvent $event)
    {
        if ($this->getEventDispatcher()) {
            $this->getEventDispatcher()->dispatch(
                $eventName,
                $event
            );
        }
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
}
