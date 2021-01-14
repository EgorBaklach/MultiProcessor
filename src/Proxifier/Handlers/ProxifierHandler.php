<?php namespace Proxifier\Handlers;

abstract class ProxifierHandler
{
    protected $callback;
    protected $charset;
    protected $type;

    const json_type = 'application/json';

    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    public function getContentType($value)
    {
        if(!is_array($value)) $value = (array) $value;

        [$this->type, $this->charset] = array_map('trim', explode(';', array_shift($value)));

        return $this->type;
    }

    abstract public function __invoke($content, $info, ...$attributes);
}
