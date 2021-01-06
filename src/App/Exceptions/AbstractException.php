<?php namespace App\Exceptions;

abstract class AbstractException extends \LogicException
{
    protected $attr = [];

    public function getAttr()
    {
        return $this->attr;
    }
}