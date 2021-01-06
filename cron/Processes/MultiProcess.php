<?php namespace Cron\Processes;

use App\Config;
use Cron\Abstracts\ThreadCron;
use Cron\Traits\Utils;
use Proxifier\Manager;

class MultiProcess extends ThreadCron
{
    /** @var Manager */
    private $proxifier;

    use Utils;

    public function prepare()
    {
        $this->proxifier = new Manager($this->cache);
    }

    public function query()
    {
        //$this->attach('unique_hash', $value, $ttl);
    }

    public function subquery($param)
    {
        if(empty($param)) throw new \Exception('Cache is Empty');
    }
}
