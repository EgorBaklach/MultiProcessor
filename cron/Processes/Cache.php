<?php namespace Cron\Processes;

use App\Interfaces\Config;
use Cron\Abstracts\Multi;
use Psr\SimpleCache\CacheInterface;

class Cache extends Multi
{
    /** @var CacheInterface */
    private $cache;

    public function prepare(Config $config)
    {
        parent::prepare($config);

        $this->cache = $config->getCache();
    }

    public function MultiProcess($value = false)
    {
        echo '-----------------------------------'.PHP_EOL;
        echo $this->call('CacheCronProcessesMultiProcess', $value).PHP_EOL;
        echo '-----------------------------------'.PHP_EOL;
    }

    public function list()
    {
        echo '-----------------------------------'.PHP_EOL;
        echo 'MultiProcess - Check cache from Multi Process'.PHP_EOL;
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
