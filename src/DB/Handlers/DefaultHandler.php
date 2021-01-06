<?php namespace DB\Handlers;

class DefaultHandler extends Handler
{
    public function process()
    {
        $result = [];
        $key = 0;

        while($value = $this->statement->fetch())
        {
            $result[$value['key'] ?: $key++] = $value;
        }

        return $result;
    }
}