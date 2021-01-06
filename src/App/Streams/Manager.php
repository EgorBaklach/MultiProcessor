<?php namespace App\Streams;

use Cron\Abstracts\Cron;

class Manager
{
    const STDIN  = 0;
    const STDOUT = 1;
    const STDERR = 2;
    const NON_BLOCKING = 0;
    const BLOCKING = 1;

    private $workers = [];
    private $processes = [];
    private $stdins = [];
    private $stdouts = [];
    private $stderrs = [];

    private static $DESCRIPTORSPEC = [
        self::STDIN  => ['pipe', 'r'],
        self::STDOUT => ['pipe', 'w'],
        self::STDERR => ['pipe', 'w'],
    ];

    private $command;
    private $path;

    public function __construct(string $command, string $path)
    {
        $this->command = $command;
        $this->path = $path;
    }

    public function attach(Worker $worker)
    {
        $process = proc_open($worker->getCommand($this->command, $this->path), self::$DESCRIPTORSPEC, $pipes);

        if (false === is_resource($process))
        {
            throw new \RuntimeException();
        }

        stream_set_blocking($pipes[self::STDOUT], self::NON_BLOCKING);

        $this->workers[] = $worker;
        $this->processes[] = $process;
        $this->stdins[] = $pipes[self::STDIN];
        $this->stdouts[] = $pipes[self::STDOUT];
        $this->stderrs[] = $pipes[self::STDERR];
    }

    public function listen($timeout = 200000)
    {
        $read = [];

        foreach($this->workers as $i => $_)
        {
            $read[] = $this->stdouts[$i];
            $read[] = $this->stderrs[$i];
        }

        $write = null;
        $expect = null;

        $changed_num = stream_select($read, $write, $expect, 0, $timeout);

        if(false === $changed_num)
        {
            throw new \RuntimeException();
        }

        if(0 === $changed_num)
        {
            return;
        }

        foreach ($read as $stream)
        {
            $i = array_search($stream, $this->stdouts, true);

            if (false === $i)
            {
                $i = array_search($stream, $this->stderrs, true);

                if (false === $i)
                {
                    continue;
                }
            }

            /** @var Worker $worker */
            $worker = $this->workers[$i];
            $stdout = stream_get_contents($this->stdouts[$i]);
            $stderr = stream_get_contents($this->stderrs[$i]);
            
            /** @var Cron $thread */
            $thread = stripslashes($worker->getThread());

            $status = $this->detach($worker);

            if (0 === $status)
            {
                $thread::done($this, $stdout, $stderr);
            }
            else if (0 < $status)
            {
                $thread::fail($this, $stdout, $stderr, $status);
            }
            else
            {
                throw new \RuntimeException();
            }
        }
    }

    public function detach(Worker $worker)
    {
        $i = array_search($worker, $this->workers, true);

        if (false === $i)
        {
            throw new \RuntimeException();
        }

        fclose($this->stdins[$i]);
        fclose($this->stdouts[$i]);
        fclose($this->stderrs[$i]);

        $status = proc_close($this->processes[$i]);

        unset($this->workers[$i]);
        unset($this->processes[$i]);
        unset($this->stdins[$i]);
        unset($this->stdouts[$i]);
        unset($this->stderrs[$i]);

        return $status;
    }

    public function getWorkers(): array
    {
        return $this->workers;
    }

    public function __destruct()
    {
        array_walk($this->stdins, 'fclose');
        array_walk($this->stdouts, 'fclose');
        array_walk($this->stderrs, 'fclose');
        array_walk($this->processes, 'proc_close');
    }
}