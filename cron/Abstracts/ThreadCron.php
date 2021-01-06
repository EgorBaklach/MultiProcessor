<?php namespace Cron\Abstracts;

use App\Streams\Worker;

/**
 * Class AbstractThreadCron
 * @package Cron
 */
abstract class ThreadCron extends MultiCron
{
    /** @var string */
    protected $hash;

    abstract public function query();
    abstract public function subquery($attr);

    protected function attach($hash, $attr, $ttl)
    {
        $this->cache->set($hash, $attr, $ttl);
        $this->manager->attach(new Worker(static::class, $hash));
    }

    public function extract($hash)
    {
        $attr = $this->cache->get($hash);
        $this->cache->delete($hash);
        $this->hash = $hash;

        return $attr;
    }

    public function run()
    {
        while (0 < count($this->manager->getWorkers()))
        {
            $this->manager->listen();
        }

        if(method_exists($this, 'terminate'))
        {
            $this->terminate();
        }
    }
}