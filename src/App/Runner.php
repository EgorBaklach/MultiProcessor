<?php namespace App;

use App\Exceptions\InProcess;
use App\Config as AppConfig;
use App\Interfaces\Config;
use Cron\Abstracts\Cron;
use Cron\Abstracts\Thread;

class Runner
{
    private $method;
    private $submethod;
    private $arguments;

    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = new AppConfig();
    }

    public function open()
    {
        if(!class_exists($this->config->getThread()))
        {
            throw new \Exception('Thread '.$this->config->getThread().' does not exist', 503);
        }

        $this->miner(function($cache, $hash)
        {
            if($cache->get($hash) === 'Y')
            {
                throw new InProcess('Process is not ready');
            }

            $cache->set($hash, 'Y');
        });

        $this->config->connect();
    }

    public function close()
    {
        $this->miner(function($cache, $hash)
        {
            $cache->delete($hash, 'Y');
        });

        return $this;
    }

    public function disconnect()
    {
        $this->config->disconnect();
    }

    public function throw(\Throwable $e)
    {
        if(!$e instanceof InProcess)
        {
            $this->close();
        }

        $this->config->throw($e);
    }

    public function init(): Cron
    {
        return $this->config->getObjectThread();
    }

    public function run(Cron $cron)
    {
        $this->arguments = $cron->exec();

        if($cron instanceof Thread)
        {
            $this->method = 'query';
            $this->submethod = 'run';

            if(!empty($this->arguments))
            {
                $this->method = 'extract';
                $this->submethod = 'subquery';
            }
        }

        if(gettype($this->arguments) === 'array' && empty($this->method))
        {
            $this->method = array_shift($this->arguments);
        }

        if(!empty($this->method) && !method_exists($cron, $this->method))
        {
            throw new \LogicException("Method {$this->method} does not exist in {$this->config->getThread()}", 501);
        }
    }

    public function miner(\Closure $callback)
    {
        $callback($this->config->getCache(), $this->config->getHash());
    }

    public function arguments()
    {
        return $this->arguments;
    }

    public function method()
    {
        return $this->method;
    }

    public function SubMethod()
    {
        return $this->submethod;
    }
}
