<?php namespace Proxifier\Exceptions;

class NotFound extends ProxifierException
{
    public function __construct($attributes)
    {
        parent::__construct('Page is not found', $attributes);
    }
}