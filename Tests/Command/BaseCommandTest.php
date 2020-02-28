<?php

namespace OldSound\RabbitMqBundle\Tests\Command;

use PHPUnit\Framework\TestCase;

abstract class BaseCommandTest extends TestCase
{
    protected $application;
    protected $definition;
    protected $helperSet;
    protected $command;

    protected function setUp(): void
    {
        $this->application = $this->getMockBuilder('Symfony\\Component\\Console\\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $this->definition = $this->getMockBuilder('Symfony\\Component\\Console\\Input\\InputDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperSet = $this->getMockBuilder('Symfony\\Component\\Console\\Helper\\HelperSet')->getMock();

        $this->application->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($this->definition));
        $this->definition->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(array()));
    }
}
