<?php namespace Collections;

use DB\Connection;
use Helpers\Shower;

/**
 * Class Databases
 * @package Collections
 * @method static Connection tools(array $access = [])
 */
class Databases extends Collection
{
    protected static function set($name, $access)
    {
        self::$instances[$name] = function() use ($access)
        {
            $DB = new Connection($access);

            $rs = $DB->connection()->query('show tables');

            while($table = $rs->fetch(\PDO::FETCH_COLUMN))
            {
                Tables::$table($DB);
            }

            return $DB;
        };
    }

    public static function available($name): bool
    {
        return self::$instances[$name] instanceof Connection;
    }
}
