<?php namespace DB\Handlers;

abstract class Handler
{
    public $statement;

    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    abstract public function process();
}