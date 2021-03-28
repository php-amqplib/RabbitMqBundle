<?php


namespace OldSound\RabbitMqBundle\RabbitMq\Exception;


use Throwable;

class ReceiverException extends \Exception
{
    /** @var int|null */
    private $flag;

    public function __construct(?int $flag = null, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->flag = $flag;
        parent::__construct($message, $code, $previous);
    }

    public function getFlag(): ?int
    {
        return $this->flag;
    }
}