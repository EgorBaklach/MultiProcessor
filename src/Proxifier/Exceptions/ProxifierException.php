<?php namespace Proxifier\Exceptions;

/**
 * Class ProxifierException
 * @package Proxifier\Exceptions
 * @property string $url
 * @property array|null $queries
 * @property array $headers
 * @property array|null $proxy
 * @property array|null $data
 * @property array|null $options
 */
abstract class ProxifierException extends \LogicException
{
    private $attr;

    public function __construct($message, array $attributes = null)
    {
        parent::__construct($message, 500);

        [
            $this->attr['url'],
            $this->attr['queries'],
            $this->attr['headers'],
            $this->attr['proxy'],
            $this->attr['data'],
            $this->attr['options']
        ] = $attributes;
    }

    public function getAttr()
    {
        return [$this->url, null, null, null, $this->data, $this->options];
    }

    public function __get($name)
    {
        if(!array_key_exists($name, $this->attr))
        {
            throw new \Exception('Attribute '.$name.' From Proxifier Exception is not available');
        }

        return $this->attr[$name] ?: null;
    }

    public function __isset($name)
    {
        return isset($this->attr[$name]);
    }
}
