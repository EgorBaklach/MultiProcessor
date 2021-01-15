<?php namespace Collections;

use DB\Connection;
use DB\Queries;

/**
 * Class Databases
 * @package Collections
 * @property Queries $proxies;
 * @property Queries $agents;
 */
class Databases extends Collection
{
    private $tables;
    private $DB;

    private function __construct(array $access)
    {
        $this->DB = new Connection($access);

        $rs = $this->DB->connection()->query('show tables');

        while($table = $rs->fetch(\PDO::FETCH_COLUMN))
        {
            $this->tables[$table] = function () use ($table)
            {
                return new Queries($table, $this->DB);
            };
        }
    }

    public function __get($name)
    {
        if(!array_key_exists($name, $this->tables))
        {
            throw new \Exception('Table '.$name.' is not exist from '.$this->DB->name());
        }

        if($this->tables[$name] instanceof \Closure)
        {
            $this->tables[$name] = self::call($this->tables[$name]);
        }

        return $this->tables[$name];
    }

    public function connection()
    {
        return $this->DB->connection();
    }

    public function abortConnection()
    {
        $this->DB->abortConnection();
    }

    protected static function set($name, $access)
    {
        self::$instances[$name] = function() use ($access)
        {
            return new self($access);
        };
    }

    public static function available($name): bool
    {
        if(self::$instances[$name] instanceof self)
        {
            return self::$instances[$name]->connection() instanceof \PDO;
        }

        return false;
    }
}
