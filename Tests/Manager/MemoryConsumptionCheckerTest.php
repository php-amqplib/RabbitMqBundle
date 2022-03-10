<?php

namespace OldSound\RabbitMqBundle\Tests\Manager;

use OldSound\RabbitMqBundle\MemoryChecker\MemoryConsumptionChecker;
use OldSound\RabbitMqBundle\MemoryChecker\NativeMemoryUsageProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class MemoryManagerTest
 *
 * @package OldSound\RabbitMqBundle\Tests\Manager
 */
class MemoryConsumptionCheckerTest extends TestCase
{
    public function testMemoryIsNotAlmostOverloaded()
    {
        $currentMemoryUsage = '7M';
        $allowedConsumptionUntil = '2M';
        $maxConsumptionAllowed = '10M';

        $memoryUsageProvider = $this->getMockBuilder('OldSound\\RabbitMqBundle\\MemoryChecker\\NativeMemoryUsageProvider')->getMock();
        $memoryUsageProvider->expects($this->any())->method('getMemoryUsage')->willReturn($currentMemoryUsage);

        $memoryManager = new MemoryConsumptionChecker($memoryUsageProvider);

        $this->assertFalse($memoryManager->isRamAlmostOverloaded($maxConsumptionAllowed, $allowedConsumptionUntil));
    }

    public function testMemoryIsAlmostOverloaded()
    {
        $currentMemoryUsage = '9M';
        $allowedConsumptionUntil = '2M';
        $maxConsumptionAllowed = '10M';

        $memoryUsageProvider = $this->getMockBuilder('OldSound\\RabbitMqBundle\\MemoryChecker\\NativeMemoryUsageProvider')->getMock();
        $memoryUsageProvider->expects($this->any())->method('getMemoryUsage')->willReturn($currentMemoryUsage);

        $memoryManager = new MemoryConsumptionChecker($memoryUsageProvider);

        $this->assertTrue($memoryManager->isRamAlmostOverloaded($maxConsumptionAllowed, $allowedConsumptionUntil));
    }

    public function testMemoryExactValueIsNotAlmostOverloaded()
    {
        $currentMemoryUsage = '7M';
        $maxConsumptionAllowed = '10M';

        $memoryUsageProvider = $this->getMockBuilder('OldSound\\RabbitMqBundle\\MemoryChecker\\NativeMemoryUsageProvider')->getMock();
        $memoryUsageProvider->expects($this->any())->method('getMemoryUsage')->willReturn($currentMemoryUsage);

        $memoryManager = new MemoryConsumptionChecker($memoryUsageProvider);

        $this->assertFalse($memoryManager->isRamAlmostOverloaded($maxConsumptionAllowed));
    }

    public function testMemoryExactValueIsAlmostOverloaded()
    {
        $currentMemoryUsage = '11M';
        $maxConsumptionAllowed = '10M';

        $memoryUsageProvider = $this->getMockBuilder('OldSound\\RabbitMqBundle\\MemoryChecker\\NativeMemoryUsageProvider')->getMock();
        $memoryUsageProvider->expects($this->any())->method('getMemoryUsage')->willReturn($currentMemoryUsage);

        $memoryManager = new MemoryConsumptionChecker($memoryUsageProvider);

        $this->assertTrue($memoryManager->isRamAlmostOverloaded($maxConsumptionAllowed));
    }
}
