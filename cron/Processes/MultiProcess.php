<?php namespace Cron\Processes;

use Cron\Abstracts\Thread;

class MultiProcess extends Thread
{
    public function query()
    {
        //$this->attach('test', 'test', self::TTL_10MIN);
    }

    public function subquery($param)
    {
        if(empty($param)) throw new \Exception('Cache is Empty', 501);
    }
}
