<?php namespace App;

use Cron\Abstracts\Cron;
use Cron\Abstracts\ThreadCron;

class Runner
{
    private $attr;
    private $method;
    private $submethod;

    public function run(Cron $cron)
    {
        $params = $cron->exec();

        if(gettype($params) === 'array')
        {
            $this->method = array_shift($params);
            $this->attr = $params;
        }

        if($cron instanceof ThreadCron)
        {
            $this->attr = $cron->getAttr();

            $this->method = 'query';
            $this->submethod = 'run';

            if(!empty($this->attr))
            {
                $this->method = 'extract';
                $this->submethod = 'subquery';
            }
        }
    }

    public function getAttr()
    {
        return $this->attr;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getSubMethod()
    {
        return $this->submethod;
    }
}
