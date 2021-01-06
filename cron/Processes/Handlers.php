<?php namespace Cron\Processes;

use App\Config;
use Cron\Abstracts\MultiCron;
use DB\Collection;
use DB\Connection;

class Handlers extends MultiCron
{
    public function prepare(Config $config)
    {
        $this->DB = [
            'local' => $config->getLocalDatabases(),
            'remotes' => $config->getRemoteDatabases()
        ];
    }

    public function checkConnections()
    {
        echo '-----------------------------------'.PHP_EOL;
        foreach($this->DB['remotes'] as $slave)
        {
            /** @var Connection $DBSalve */
            $DBSalve = Collection::$slave();

            echo implode(' - ', ['Checkin', $slave, '']);

            $DBSalve->connection();

            echo 'ALREADY!'.PHP_EOL;
        }
        echo '-----------------------------------'.PHP_EOL;
    }

    public function list()
    {
        echo '-----------------------------------'.PHP_EOL;
        echo 'checkConnections - Check remote connections'.PHP_EOL;
        echo 'test - Test method'.PHP_EOL;
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
