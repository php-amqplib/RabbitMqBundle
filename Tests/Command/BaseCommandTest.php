<?php

namespace OldSound\RabbitMqBundle\Tests\Command;

abstract class BaseCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $application;
    protected $definition;
    protected $helperSet;
    protected $command;

    protected function setUp()
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
