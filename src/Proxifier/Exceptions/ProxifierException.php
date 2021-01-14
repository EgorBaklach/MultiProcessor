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

    public function __construct($message, $attr = false)
    {
        parent::__construct($message, 500);

        [
            $this->attr['url'],
            $this->attr['queries'],
            $this->attr['headers'],
            $this->attr['proxy'],
            $this->attr['data'],
            $this->attr['options']
        ] = $attr;
    }

    public function getAttr()
    {
        return [
            $this->url,
            null,
            $this->headers ?: null,
            $this->proxy ?: null,
            $this->data,
            $this->options
        ];
    }

    public function __get($name)
    {
        if(!array_key_exists($name, $this->attr))
        {
            throw new \Exception('Attribute '.$name.' From Proxifier Exception is not available');
        }

        return $this->attr[$name] ?: null;
    }
}
