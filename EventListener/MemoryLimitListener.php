<?php

namespace OldSound\RabbitMqBundle\EventListener;

use OldSound\RabbitMqBundle\MemoryChecker\MemoryConsumptionChecker;
use OldSound\RabbitMqBundle\MemoryChecker\NativeMemoryUsageProvider;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;

class MemoryLimitListener
{
    /** @var int */
    private $memoryLimit;
    
    public function __construct(int $memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;
    }
    
    public function __invoke(Consumer $consumer)
    {
        if (!is_null($this->getMemoryLimit()) && $this->isRamAlmostOverloaded()) {
            $consumer->stopConsuming(true);
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

        return $memoryManager->isRamAlmostOverloaded($this->memoryLimit.'M', '5M');
    }
}