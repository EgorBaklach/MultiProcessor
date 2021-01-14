<?php namespace App\Exceptions;

abstract class AppException extends \Exception
{
    protected $attr = [];

    public function __construct($message, $attr = false)
    {
        parent::__construct($message, 500);

        $this->attr = $attr;
    }

    public function getAttr()
    {
        return $this->attr;
    }
}