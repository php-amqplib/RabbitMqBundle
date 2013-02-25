<?php

namespace OldSound\RabbitMqBundle\Tests\Command;

use OldSound\RabbitMqBundle\Command\ConsumerCommand;

use Symfony\Component\Console\Input\InputOption;

class ConsumerCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $definition;
    private $helperSet;
    private $command;

    protected function setUp()
    {
        $this->application = $this->getMockBuilder('Symfony\\Bundle\\FrameworkBundle\\Console\\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $this->definition = $this->getMockBuilder('Symfony\\Component\\Console\\Input\\InputDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperSet = $this->getMock('Symfony\\Component\\Console\\Helper\\HelperSet');

        $this->application->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($this->definition));
        $this->definition->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(array()));
        $this->definition->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue(array(
                new InputOption('--verbose', '-v', InputOption::VALUE_NONE, 'Increase verbosity of messages.'),
                new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'),
                new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'),
            )));
        $this->application->expects($this->once())
            ->method('getHelperSet')
            ->will($this->returnValue($this->helperSet));

        $this->command = new ConsumerCommand();
        $this->command->setApplication($this->application);
    }

    /**
     * testInputsDefinitionCommand
     */
    public function testInputsDefinitionCommand()
    {
        // check argument
        $this->assertTrue($this->command->getDefinition()->hasArgument('name'));
        $this->assertTrue($this->command->getDefinition()->getArgument('name')->isRequired()); // Name is required to find the service

        //check options
        $this->assertTrue($this->command->getDefinition()->hasOption('messages'));
        $this->assertTrue($this->command->getDefinition()->getOption('messages')->isValueOptional()); // It should accept value

        $this->assertTrue($this->command->getDefinition()->hasOption('route'));
        $this->assertTrue($this->command->getDefinition()->getOption('route')->isValueOptional()); // It should accept value

        $this->assertTrue($this->command->getDefinition()->hasOption('without-signals'));
        $this->assertFalse($this->command->getDefinition()->getOption('without-signals')->acceptValue()); // It shouldn't accept value because it is a true/false input

        $this->assertTrue($this->command->getDefinition()->hasOption('debug'));
        $this->assertFalse($this->command->getDefinition()->getOption('debug')->acceptValue()); // It shouldn't accept value because it is a true/false input
    }
}
