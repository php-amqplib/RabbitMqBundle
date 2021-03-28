<?php


namespace OldSound\RabbitMqBundle\Declarations;

/**
 * @TODO move
 */
class QueueDeclaration
{
    /** @var string */
    public $name;
    /** @var bool */
    public $passive = false;
    /** @var bool */
    public $durable = true;
    /** @var bool */
    public $exclusive = false;
    /** @var bool */
    public $autoDelete = false;
    /** @var array */
    public $arguments = [];
    public $ticket;
    public $declare;

    // TODO move
    public static function createAnonymous(): QueueDeclaration
    {
        $anonQueueDeclaration = new QueueDeclaration();
        $anonQueueDeclaration->passive = false;
        $anonQueueDeclaration->durable = false;
        $anonQueueDeclaration->exclusive = true;
        $anonQueueDeclaration->autoDelete = true;
        $anonQueueDeclaration->nowait = false;

        return $anonQueueDeclaration;
    }
}