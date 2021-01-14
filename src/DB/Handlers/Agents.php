<?php namespace DB\Handlers;

class Agents extends DBHandler
{
    public function __construct(\PDOStatement $statement)
    {
        while($value = $statement->fetch())
        {
            $this->result[$value['type']][] = $value;
        }
    }
}