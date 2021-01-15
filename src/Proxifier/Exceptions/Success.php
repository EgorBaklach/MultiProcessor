<?php namespace Proxifier\Exceptions;

class Success extends ProxifierException
{
    public function __construct($attributes)
    {
        parent::__construct(false, $attributes);
    }
}