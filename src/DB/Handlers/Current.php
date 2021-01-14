<?php namespace DB\Handlers;

class Current extends DBHandler
{
    public function __construct(\PDOStatement $statement)
    {
        $key = 0;

        while($value = $statement->fetch())
        {
            $this->result[$value['key'] ?: $key++] = $value;
        }
    }
}