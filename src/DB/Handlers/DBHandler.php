<?php namespace DB\Handlers;

abstract class DBHandler implements \ArrayAccess
{
    protected $result = [];

    abstract public function __construct(\PDOStatement $statement);

    public function count(): int
    {
        return count($this->result);
    }

    public function offsetExists($offset)
    {
        return isset($this->result[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->result[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->result[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->result[$offset]);
    }
}