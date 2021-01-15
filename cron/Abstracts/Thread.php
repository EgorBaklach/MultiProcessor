<?php namespace Cron\Abstracts;

use App\Interfaces\Config;
use Psr\SimpleCache\CacheInterface;
use Streams\Manager as StreamManager;
use Streams\Worker;

abstract class Thread extends Multi
{
    /** @var StreamManager */
    protected $stream;

    /** @var CacheInterface */
    protected $cache;

    protected function prepare(Config $config)
    {
        parent::prepare($config);

        $this->stream = new StreamManager($config->getCommand(), $config->getPath());
        $this->cache = $config->getCache();
    }

    abstract public function query();
    abstract public function subquery($attr);

    protected function attach($hash, $attr, $ttl = null)
    {
        $this->cache->set($hash, $attr, $ttl ?? self::TTL_FOREVER);
        $this->stream->attach(new Worker(static::class, $hash));
    }

    public function extract($hash)
    {
        $params = $this->cache->get($hash);

        $this->cache->delete($hash);

        return $params;
    }

    public function run()
    {
        while (0 < count($this->stream->getWorkers()))
        {
            $this->stream->listen();
        }

        if(method_exists($this, 'terminate'))
        {
            $this->terminate();
        }

        $this->stream->throwException();
    }
}