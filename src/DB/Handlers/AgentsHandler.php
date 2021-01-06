<?php namespace DB\Handlers;

class AgentsHandler extends Handler
{
    public function process()
    {
        $result = [];

        while($value = $this->statement->fetch())
        {
            $result[$value['type']][] = $value;
        }

        return $result;
    }
}