<?php

namespace OldSound\RabbitMqBundle\Tests\Event;

use OldSound\RabbitMqBundle\MemoryChecker\MemoryConsumptionChecker;
use OldSound\RabbitMqBundle\MemoryChecker\NativeMemoryUsageProvider;

/**
 * Class MemoryManagerTest
 *
 * @package OldSound\RabbitMqBundle\Tests\Manager
 */
class MemoryConsumptionCheckerTest extends \PHPUnit_Framework_TestCase
{
    public function testMemoryIsNotAlmostOverloaded()
    {
        $currentMemoryUsage = '7M';
        $allowedConsumptionUntil = '2M';
        $maxConsumptionAllowed = '10M';

        $memoryUsageProvider = $this->getMockBuilder('OldSound\\RabbitMqBundle\\MemoryChecker\\NativeMemoryUsageProvider')->getMock();
        $memoryUsageProvider->expects($this->any())->method('getMemoryUsage')->willReturn($currentMemoryUsage);

        $memoryManager = new MemoryConsumptionChecker($memoryUsageProvider);

        $this->assertFalse($memoryManager->isRamAlmostOverloaded($allowedConsumptionUntil, $maxConsumptionAllowed));
    }

    public function testMemoryIsAlmostOverloaded()
    {
        $currentMemoryUsage = '9M';
        $allowedConsumptionUntil = '2M';
        $maxConsumptionAllowed = '10M';

        $memoryUsageProvider = $this->getMockBuilder('OldSound\\RabbitMqBundle\\MemoryChecker\\NativeMemoryUsageProvider')->getMock();
        $memoryUsageProvider->expects($this->any())->method('getMemoryUsage')->willReturn($currentMemoryUsage);

        $memoryManager = new MemoryConsumptionChecker($memoryUsageProvider);

        $this->assertTrue($memoryManager->isRamAlmostOverloaded($allowedConsumptionUntil, $maxConsumptionAllowed));
    }
}
