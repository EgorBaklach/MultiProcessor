<?php namespace Collections;

abstract class Collection
{
    protected static $instances = [];

    protected static function call(\Closure $closure)
    {
        return $closure();
    }

    protected static function get($name)
    {
        if(!array_key_exists($name, self::$instances))
        {
            throw new \LogicException("Instance $name does not exist", 501);
        }

        if(self::$instances[$name] instanceof \Closure)
        {
            self::$instances[$name] = static::call(self::$instances[$name]);
        }

        return self::$instances[$name];
    }

    public static function __callStatic($name, $arguments)
    {
        return !empty($arguments) ? static::set($name, ...$arguments) : static::get($name);
    }

    abstract protected static function set($name, $instance);
};