<?php

namespace OldSound\RabbitMqBundle\Declarations;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Declarator
{
    use LoggerAwareTrait;
    /** @var AMQPChannel */
    private $channel;
    
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
        $this->logger = new NullLogger();
    }

    /**
     * @param ExchangeDeclaration[] $exchanges
     */
    public function declareExchanges(array $exchanges) 
    {
        foreach ($exchanges as $exchange) {
            $this->channel->exchange_declare(
                $exchange->name,
                $exchange->type,
                $exchange->passive,
                $exchange->durable,
                $exchange->autoDelete,
                $exchange->internal,
                $exchange->nowait,
                $exchange->arguments,
                $exchange->ticket,
            );

//            $this->logger->info('Exchange {exchange} declared ', [
//                'exchange' => $exchange->name
//            ]);
        }
    }

    /**
     * @deprecated
     * TODO remove
     */
    public function declareQueuesAndBindings(DeclarationsRegistry $declarationsRegistry)
    {
        foreach ($declarationsRegistry->queues as $name => $queue) {
            list($queueName, ,) = $this->channel->queue_declare(
                $queue->name,
                $queue->passive,
                $queue->durable,
                $queue->exclusive,
                $queue->autoDelete,
                $queue->nowait,
                $queue->arguments,
                $queue->ticket
            );

            /*if (isset($options['routing_keys']) && count($options['routing_keys']) > 0) {
                foreach ($options['routing_keys'] as $queue->name) {
                    $this->channel->queue_bind($queue->name, $queue->name, $routingKey, $options['arguments'] ?? []);
                }
            } else {
                $this->queueBind($queue->name, $this->exchangeOptions['name'], $this->routingKey, $options['arguments'] ?? []);
            }*/
        }
    }

    /**
     * @param QueueDeclaration[] $queues
     * @return string[]
     */
    public function declareQueues(array $queues): array
    {
        $queueNames = [];
        foreach ($queues as $queue) {
            $result = $this->channel->queue_declare(
                $queue->name,
                $queue->passive,
                $queue->durable,
                $queue->exclusive,
                $queue->autoDelete,
                false,
                $queue->arguments,
                $queue->ticket,
            );

            if ($result === null) {
                throw new AMQPException('');
            } else {
                $queueNames[] = $result[0];
                // $this->logger->info('Queue {queue} declared', ['queue' => $queue->name]);
            }
        }
        return $queueNames;
    }

    /**
     * @param BindingDeclaration[] $bindings
     */
    public function declareBindings(array $bindings) 
    {
        foreach ($bindings as $binding) {
            if ($binding->destinationIsExchange) {
                foreach ($binding->routingKeys as $routingKey) {
                    $this->channel->exchange_bind(
                        $binding->destination,
                        $binding->exchange,
                        $routingKey,
                        $binding->nowait,
                        $binding->arguments
                    );
                }
                if ([] === $binding->routingKeys) {
                    $this->channel->queue_bind(
                        $binding->destination,
                        $binding->exchange,
                        '',
                        $binding->nowait,
                        $binding->arguments
                    );
                }
            } else {
                foreach ($binding->routingKeys as $routingKey) {
                    $this->channel->queue_bind(
                        $binding->destination,
                        $binding->exchange,
                        $routingKey,
                        $binding->nowait,
                        $binding->arguments
                    );
                }
                if ([] === $binding->routingKeys) {
                    $this->channel->queue_bind(
                        $binding->destination,
                        $binding->exchange,
                        '',
                        $binding->nowait,
                        $binding->arguments
                    );
                }
            }

            // $this->logger->info('Binding declared', ['binding' => $binding]);
        }
    }

    public function declareForExchange(ExchangeDeclaration $exchange, DeclarationsRegistry $declarationsRegistry) 
    {
        $bindings = $declarationsRegistry->getBindingsByExchange($exchange);
        $queues = array_filter($bindings, function ($binding) use($exchange) {
            false === $binding->destinationIsExchange && $binding->destination == $exchange->name;
        });

        $this->declareExchanges([$exchange]);
        $this->declareQueues($queues);
        $this->declareBindings($bindings);
    }

    public function declareForQueueDeclaration(string $queueName, DeclarationsRegistry $declarationsRegistry)
    {
        $consumerQueues = array_filter($declarationsRegistry->queues, function ($queue) use ($queueName) {
            return $queue->name === $queueName;
            // TODO not found! exception?
        });

        /** @var BindingDeclaration[] $bindings */
        $bindings = [];
        $exchanges = [];
        foreach ($consumerQueues as $queue) {
            $b = array_filter($declarationsRegistry->bindings, function ($binding) use ($queue) {
                return !$binding->destinationIsExchange && $binding->destination === $queue->name;
            });
            $bindings = array_merge($bindings, $b);
            foreach ($b as $binding) {
                $exchanges[] = $binding->exchange;
                if ($binding->destinationIsExchange) {
                    $exchanges[] = $binding->destination;
                }
            }
        }

        $exchanges = array_map(fn ($exchange) => $declarationsRegistry->exchanges[$exchange], array_unique($exchanges));
        $this->declareExchanges($exchanges);
        $this->declareQueues($consumerQueues);
        $this->declareBindings($bindings);
    }
    
    public function declareForQueue(QueueDeclaration $queue)
    {
        $exchanges = array_map(function ($binding) {
            return $binding->exchange;
        }, $queue->bindings);

        $this->declareExchanges($exchanges);
        $this->declareQueues([$queue]);
        $this->declareBindings($queue->bindings);
    }
    
    public function purgeQueue(QueueDeclaration $queue, $nowait = true, ?int $ticket = null)
    {
        $this->channel->queue_purge($queue->name, $nowait, $ticket);
    }
    
    public function deleteQueue(
        QueueDeclaration $queue, 
        bool $ifUnsed = true, 
        bool $ifEmpry = false,
        bool $nowait = false, 
        ?int $ticket = null
    ) {
        $this->channel->queue_delete($queue->name, $ifUnsed, $ifEmpry, $nowait, $ticket);
    }
}