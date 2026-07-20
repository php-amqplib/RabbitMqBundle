<?php

use OldSound\RabbitMqBundle\Command\RpcServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

beforeEach(function () {
    $this->application = $this->getMockBuilder(Application::class)->disableOriginalConstructor()->getMock();
    $this->definition  = $this->getMockBuilder(InputDefinition::class)->disableOriginalConstructor()->getMock();
    $this->helperSet   = $this->getMockBuilder(HelperSet::class)->getMock();

    $this->application->method('getDefinition')->willReturn($this->definition);
    $this->definition->method('getArguments')->willReturn([]);
    $this->definition->method('getOptions')->willReturn([
        new InputOption('--verbose', '-v', InputOption::VALUE_NONE, 'Increase verbosity of messages.'),
        new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'),
        new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'),
    ]);

    $this->application->expects($this->once())->method('getHelperSet')->willReturn($this->helperSet);

    $this->command = new RpcServerCommand();
    $this->command->setApplication($this->application);
});

test('rpc server command has the correct name', function () {
    expect($this->command->getName())->toBe('rabbitmq:rpc-server');
});

test('rpc server command has the correct input definition', function () {
    $definition = $this->command->getDefinition();

    expect($definition->hasArgument('name'))->toBeTrue();
    expect($definition->getArgument('name')->isRequired())->toBeTrue();
    expect($definition->getArgument('name')->getDescription())->toBe('Server Name');

    expect($definition->hasOption('messages'))->toBeTrue();
    expect($definition->getOption('messages')->isValueOptional())->toBeTrue();

    expect($definition->hasOption('memory-limit'))->toBeTrue();
    expect($definition->getOption('memory-limit')->isValueOptional())->toBeTrue();
    expect($definition->getOption('memory-limit')->getShortcut())->toBe('l');

    expect($definition->hasOption('route'))->toBeTrue();
    expect($definition->getOption('route')->isValueOptional())->toBeTrue();

    expect($definition->hasOption('without-signals'))->toBeTrue();
    expect($definition->getOption('without-signals')->acceptValue())->toBeFalse();

    expect($definition->hasOption('debug'))->toBeTrue();
    expect($definition->getOption('debug')->acceptValue())->toBeFalse();
});
