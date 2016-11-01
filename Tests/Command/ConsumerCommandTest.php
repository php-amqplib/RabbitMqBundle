<?php

namespace OldSound\RabbitMqBundle\Tests\Command;

use OldSound\RabbitMqBundle\Command\ConsumerCommand;

use Symfony\Component\Console\Input\InputOption;

class ConsumerCommandTest extends BaseCommandTest
{
    protected function setUp()
    {
        parent::setUp();
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
        $definition = $this->command->getDefinition();
        // check argument
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->getArgument('name')->isRequired()); // Name is required to find the service

        //check options
        $this->assertTrue($definition->hasOption('messages'));
        $this->assertTrue($definition->getOption('messages')->isValueOptional()); // It should accept value

        $this->assertTrue($definition->hasOption('route'));
        $this->assertTrue($definition->getOption('route')->isValueOptional()); // It should accept value

        $this->assertTrue($definition->hasOption('without-signals'));
        $this->assertFalse($definition->getOption('without-signals')->acceptValue()); // It shouldn't accept value because it is a true/false input

        $this->assertTrue($definition->hasOption('debug'));
        $this->assertFalse($definition->getOption('debug')->acceptValue()); // It shouldn't accept value because it is a true/false input
    }
}
