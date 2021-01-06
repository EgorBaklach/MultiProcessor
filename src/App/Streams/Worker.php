<?php namespace App\Streams;

class Worker
{
    private $thread;
    private $attr;
    
    public function __construct($thread, $attr)
    {
        $this->thread = addslashes($thread);
        $this->attr = addslashes($attr);
    }

    public function getThread(): string
    {
        return $this->thread;
    }

    public function getCommand(string $command, string $path): string
    {
        return implode(' ', [$command, $path, $this->thread, $this->attr]);
    }
}
