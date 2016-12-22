<?php

namespace OldSound\RabbitMqBundle\Tests\Command;

use OldSound\RabbitMqBundle\Command\PurgeConsumerCommand;
use Symfony\Component\Console\Input\InputOption;

class PurgeCommandTest extends BaseCommandTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->definition->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue(array(
                new InputOption('--no-confirmation', null, InputOption::VALUE_NONE, 'Switches off confirmation mode.'),
            )));
        $this->application->expects($this->once())
            ->method('getHelperSet')
            ->will($this->returnValue($this->helperSet));

        $this->command = new PurgeConsumerCommand();
        $this->command->setApplication($this->application);
    }

    /**
     * testInputsDefinitionCommand
     */
    public function testInputsDefinitionCommand()
    {
        // check argument
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->getArgument('name')->isRequired()); // Name is required to find the service

        //check options
        $this->assertTrue($definition->hasOption('no-confirmation'));
        $this->assertFalse($definition->getOption('no-confirmation')->acceptValue()); // It shouldn't accept value because it is a true/false input
    }
}
