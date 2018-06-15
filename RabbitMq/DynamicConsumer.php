<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Provider\QueueOptionsProviderInterface;

class DynamicConsumer extends Consumer{

    /**
     * Queue provider
     *
     * @var QueueOptionsProviderInterface
     */
    protected $queueOptionsProvider = null;
    
    /**
     * Context the consumer runs in
     * 
     * @var string
     */
    protected $context = null;

    /**
     * QueueOptionsProvider setter
     *
     * @param QueueOptionsProviderInterface $queueOptionsProvider
     *
     * @return self
     */
    public function setQueueOptionsProvider(QueueOptionsProviderInterface $queueOptionsProvider)
    {
        $this->queueOptionsProvider = $queueOptionsProvider;
        return $this;
    }
    
    public function setContext($context)
    {
        $this->context = $context;
    }


    protected function setupConsumer()
    {   
        $this->mergeQueueOptions();
        parent::setupConsumer();
    }
    
    protected function mergeQueueOptions()
    {
        if (null === $this->queueOptionsProvider) {
            return;
        }
        $this->queueOptions = array_merge($this->queueOptions, $this->queueOptionsProvider->getQueueOptions($this->context));
    }
}