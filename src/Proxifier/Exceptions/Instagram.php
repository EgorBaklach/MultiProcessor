<?php namespace Proxifier\Exceptions;

class Instagram extends ProxifierException
{
    public function __construct($attributes)
    {
        parent::__construct('Request dont caught. Try again.', $attributes);
    }
}