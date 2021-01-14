<?php namespace Streams\Exceptions;

abstract class ThreadException extends \Exception
{
    public function __toString()
    {
        return $this->message;
    }
}
