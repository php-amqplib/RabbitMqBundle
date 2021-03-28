<?php

namespace OldSound\RabbitMqBundle\Declarations;

class DeclarationsRegistry
{
    /** @var ExchangeDeclaration[] */
    public $exchanges = [];
    /** @var QueueDeclaration[] */
    public $queues = [];
    /** @var BindingDeclaration[] */
    public $bindings = [];

    /** @var ConsumerDef[] */
    public $consumers = [];
    
    public function addExchange(ExchangeDeclaration $exchangeDeclaration)
    {
        if (isset($this->exchanges[$exchangeDeclaration->name])) {
            throw new \InvalidArgumentException(sprintf('Exchange declartion with %s name already registerd', $exchangeDeclaration->name));
        }
        $this->exchanges[$exchangeDeclaration->name] = $exchangeDeclaration;
    }
    
    public function addQueue(QueueDeclaration $queueDeclaration)
    {
        $this->queues[] = $queueDeclaration;    
    }

    public function addBinding(BindingDeclaration $bindingDeclaration)
    {
        $this->bindings[] = $bindingDeclaration;
    }

    public function addConsumer(ConsumerDef $consumerDef)
    {
        if (isset($this->consumers[$consumerDef->name])) {
            throw new \InvalidArgumentException(sprintf('Consumer definition with %s name already registerd', $consumerDef->name));
        }
        $this->consumers[$consumerDef->name] = $consumerDef;
    }

    /**
     * @param ExchangeDeclaration $exchange
     * @return BindingDeclaration[]
     */
    public function getBindingsByExchange(ExchangeDeclaration $exchange): array
    {
        return array_filter($this->bindings, function ($binding) use ($exchange) {
            return $binding->exchange === $exchange->name || ($binding->destinationIsExchange && $binding->destination === $exchange->name);
        });
    }
}