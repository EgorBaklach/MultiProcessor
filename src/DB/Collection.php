<?php namespace DB;

/**
 * Class Collection
 * @method static self example(array $access = [])
 * @method static self tools(array $access = [])
 */
class Collection
{
    private static $instances = [];

    public static function __callStatic($name, array $access)
    {
        if(!self::$instances[$name] instanceof Connection)
        {
            self::$instances[$name] = new Connection(...$access);
        }

        return self::$instances[$name];
    }
};