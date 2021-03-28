<?php


namespace OldSound\RabbitMqBundle\Declarations;


class ExchangeDeclaration
{
    public $name;
    public $type;
    public $passive;
    public $durable;
    public $autoDelete;
    public $internal;
    public $nowait;
    public $arguments;
    public $ticket;
    public $declare;
}