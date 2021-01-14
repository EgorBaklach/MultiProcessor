<?php namespace Cron\Processes;

use App\Interfaces\Config;
use Collections\Databases;
use Cron\Abstracts\Multi;

class Handlers extends Multi
{
    private $cache;
    private $DB;

    public function prepare(Config $config)
    {
        parent::prepare($config);

        $this->cache = $config->getCache();
        $this->DB = [
            'local' => $config->getLocalDatabases(),
            'remotes' => $config->getRemoteDatabases()
        ];
    }

    public function checkConnections()
    {
        if(empty($this->DB['remotes']))
        {
            throw new \Exception('Remote connections are empty', 501);
        }

        echo '-----------------------------------'.PHP_EOL;
        foreach($this->DB['remotes'] as $slave)
        {
            echo implode(' - ', ['Try', $slave, '']);

            Databases::$slave()->connection();

            echo 'ALREADY!'.PHP_EOL;
        }
        echo '-----------------------------------'.PHP_EOL;
    }

    public function list()
    {
        echo '-----------------------------------'.PHP_EOL;
        echo 'checkConnections - Check remote connections'.PHP_EOL;
        echo 'test - Test method. Process go to sleep during 30 seconds'.PHP_EOL;
        echo 'logon - Turn On Logger'.PHP_EOL;
        echo 'logoff - Switch Off Logger'.PHP_EOL;
        echo '-----------------------------------'.PHP_EOL;
    }

    public function test()
    {
        sleep(30);
    }

    public function logon()
    {
        $this->cache->set('LOGGER', 'Y', self::TTL_2MONTHS);

        echo '-----------------------------------'.PHP_EOL;
        echo $this->cache->get('LOGGER').PHP_EOL;
        echo '-----------------------------------'.PHP_EOL;
    }

    public function logoff()
    {
        $this->cache->delete('LOGGER');

        echo '-----------------------------------'.PHP_EOL;
        echo 'SUCCESS'.PHP_EOL;
        echo '-----------------------------------'.PHP_EOL;
    }
}
