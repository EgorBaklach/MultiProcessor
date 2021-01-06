<?php namespace Cron\Processes;

use Cron\Abstracts\MultiCron;

class Cache extends MultiCron
{
    public function Example($value = false)
    {
        echo '-----------------------------------'.PHP_EOL;
        echo $this->call('example_last_id', $value).PHP_EOL;

        if($value !== 'delete') $value = false;

        echo $this->call('CacheCronExample', $value).PHP_EOL;
        echo '-----------------------------------'.PHP_EOL;
    }

    public function list()
    {
        echo '-----------------------------------'.PHP_EOL;
        echo 'Example - Example process'.PHP_EOL;
        echo '-----------------------------------'.PHP_EOL;
    }

    public function call($hash, $value = false)
    {
        switch(true)
        {
            case $value === 'delete': $this->cache->delete($hash); break;
            case !empty($value): $this->cache->set($hash, $value, self::TTL_HOUR); break;
        }

        return empty($value) ? $this->cache->get($hash) : 'success';
    }
}
