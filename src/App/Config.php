<?php namespace App;

use Collections\Databases;
use Phpfastcache\Drivers\Memcache\Config as PDMConfig;
use Phpfastcache\Helper\CacheConditionalHelper;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\SimpleCache\CacheInterface;

class Config implements Interfaces\Config
{
    private $options;
    private $thread;
    private $cache;
    private $path;
    private $hash;
    private $env;

    public function __construct()
    {
        // TODO На Перспективу, ввиду того что кеш нигде не записывается - его надо хранить
        $this->cache = new Psr16Adapter('memcache', new PDMConfig(['host' => '127.0.0.1', 'port' => 11211]));

        $arguments = (array) $_SERVER['argv'];

        $this->path = array_shift($arguments);
        $this->thread = array_shift($arguments);

        $this->options = $arguments;

        $this->env = json_decode(file_get_contents('env.json'), true);
    }

    public function connect()
    {
        foreach($this->env['DB'] as $type => $connections)
        {
            foreach($connections as $connection => $access)
            {
                Databases::$connection($access);
            }
        }
    }

    public function disconnect()
    {
        foreach($this->env['DB'] as $connections)
        {
            foreach(array_keys($connections) as $name)
            {
                if(Databases::available($name))
                {
                    Databases::$name()->abortConnection();
                }
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

    public function getObjectThread()
    {
        return new $this->thread($this);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getHash()
    {
        if(empty($this->hash))
        {
            $hash = ['Cache', stripslashes($this->thread)];

            if(!empty($this->options))
            {
                $hash[] = ucfirst(md5(serialize($this->options)));
            }

            $this->hash = implode('', $hash);
        }

        return $this->hash;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getConditionalCache()
    {
        return new CacheConditionalHelper($this->cache->getInternalCacheInstance());
    }

    public function getLocalDatabases()
    {
        return array_keys($this->env['DB']['local']);
    }

    public function getRemoteDatabases()
    {
        return array_keys($this->env['DB']['remote']);
    }

    public function throw(\Throwable $e)
    {
        $this->disconnect();

        $messages = [];

        do
        {
            $messages[] = $e instanceof \Error ? $e->getMessage() : implode(PHP_EOL, [
                implode(':', [get_class($e), $e->getCode(), $e->getFile(), $e->getLine()]),
                $e->getMessage(),
                $e->getTraceAsString()
            ]);

        } while ($e = $e->getPrevious());

        die(implode(PHP_EOL.PHP_EOL, $messages).PHP_EOL);
    }
}