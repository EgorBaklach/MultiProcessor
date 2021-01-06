<?php namespace App;

use Phpfastcache\Drivers\Memcache\Config as PDMConfig;
use Phpfastcache\Helper\Psr16Adapter;
use App\Exceptions\InternalException;
use Cron\Abstracts\Cron;
use DB\Collection;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Config
 * @package App
 */
class Config
{
    private $thread;
    private $cache;
    private $path;
    private $attr;
    private $hash;
    private $env;

    public function __construct()
    {
        $this->cache = new Psr16Adapter('memcache', new PDMConfig(['host' => '127.0.0.1', 'port' => 11211]));

        $arguments = (array) $_SERVER['argv'];

        $this->path = array_shift($arguments);
        $this->thread = array_shift($arguments);

        $this->attr = $arguments;

        $this->env = json_decode(file_get_contents('env.json'), true);
    }

    public function connect()
    {
        foreach($this->env['DB'] as $type => $connections)
        {
            foreach($connections as $connection => $access)
            {
                Collection::$connection($access);
            }
        }
    }

    public function disconnect()
    {
        $this->cache->delete($this->getHash());

        foreach($this->env['DB'] as $connections)
        {
            foreach(array_keys($connections) as $connection)
            {
                Collection::$connection()->abortConnection();
            }
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getCommand()
    {
        return $this->env['PHP']['command'] ?: 'php';
    }

    public function getThread()
    {
        return $this->thread;
    }

    public function getAttr()
    {
        return $this->attr;
    }

    public function getHash()
    {
        if(empty($this->hash))
        {
            $hash = ['Cache', stripslashes($this->thread)];

            if(!empty($this->attr))
            {
                $hash[] = ucfirst(md5(serialize($this->attr)));
            }

            $this->hash = implode('', $hash);
        }

        return $this->hash;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getReady()
    {
        switch(true)
        {
            case !class_exists($this->thread):
            case $this->cache->get($this->getHash()) === 'Y':
                return false;
        }

        return true;
    }

    public function initProcess(): Cron
    {
        return new $this->thread($this);
    }

    public function getLocalDatabases()
    {
        return array_keys($this->env['DB']['local']);
    }

    public function getRemoteDatabases()
    {
        return array_keys($this->env['DB']['remote']);
    }

    public function throwable(\Throwable $e)
    {
        if(!$e instanceof InternalException)
        {
            $this->disconnect();
        }

        do
        {
            $messages[] = implode(PHP_EOL, [
                implode(' ', [$e->getFile(), 'on line', $e->getLine()]),
                $this->thread ?: 'Not exist',
                $e->getMessage(),
                $e->getTraceAsString()
            ]);
        }
        while($e = $e->getPrevious());

        die(implode(PHP_EOL, $messages).PHP_EOL);
    }
}