<?php namespace DB;

class Connection
{
    private $type = 'mysql';
    private $name;
    private $query;
    private $user;
    private $pass;
    private $charset;

    private $connection;

    public function __construct(array $access)
    {
        $this->charset = $access['charset'];
        $this->user = $access['user'];
        $this->pass = $access['pass'];
        $this->name = $access['db'];
        $this->query = http_build_query([
            'host' => $access['host'],
            'dbname' => $access['db'],
            'charset' => $access['charset']
        ], '', ';');
    }

    public function connection()
    {
        if(!$this->connection instanceof \PDO)
        {
            $this->connection = new \PDO(
                $this->type.':'.$this->query,
                $this->user,
                $this->pass,
                [
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_ERRMODE  => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_PERSISTENT => false,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$this->charset
                ]
            );
        }

        return $this->connection;
    }

    public function name()
    {
        return $this->name;
    }

    public function abortConnection()
    {
        $this->connection = null;
    }
}