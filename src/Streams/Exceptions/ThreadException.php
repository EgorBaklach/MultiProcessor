<?php namespace Streams\Exceptions;

abstract class ThreadException extends \Error
{
    public function __toString()
    {
        return $this->message;
    }
}
