<?php namespace Collections;

use DB\Connection;
use DB\Factory;
use DB\Queries;

/**
 * Class Databases
 * @package Collections
 * @method static Queries proxies(Connection $connection = false)
 * @method static Queries agents(Connection $connection = false)
 */
class Tables extends Collection
{
    protected static function set($table, $connection)
    {
        self::$instances[$table] = function() use ($table, $connection)
        {
            return new Queries($table, $connection);
        };
    }

    public static function available($name): bool
    {
        return self::$instances[$name] instanceof Factory;
    }
}
