<?php namespace DB;

use Helpers\Corrector;

abstract class Factory
{
    protected $table;

    protected $dependencies;
    protected $references;
    protected $conditions;
    protected $fields;
    protected $where;
    protected $order;
    protected $binds;

    protected $chains;
    protected $mark;

    private $DB;

    const mark_of_parameter = ':';

    public function __construct($table, Connection $DB)
    {
        $this->table = $table;
        $this->DB = $DB;

        $this->abort();
    }

    public function abort()
    {
        $this->dependencies = $this->where = $this->order = $this->binds = [];
        $this->references = [$this->table];
        $this->mark = 'a';
    }

    protected function crypt($field, $node, $multi = false, $spec = false)
    {
        if(is_int($field) && is_string($node))
        {
            return $node;
        }

        switch(gettype($node))
        {
            case 'array':

                $values = [];

                foreach($node as $value)
                {
                    $values[] = $this->crypt($field, $value, true);
                }

                $value = Corrector::RoundFraming(implode(',', $values));

                break;
            default:

                $value = self::mark_of_parameter.$this->mark++;
                $this->convert($value, $node);
        }

        if(!$multi)
        {
            $value = $field.$spec.$value;
        }

        return $value;
    }

    protected function convert($name, $value)
    {
        switch(gettype($value))
        {
            case 'NULL': return $this->bind($name, null, \PDO::PARAM_NULL);
            case 'integer': return $this->bind($name, $value, \PDO::PARAM_INT);
        }

        return $this->bind($name, $value, \PDO::PARAM_STR);
    }

    public function conditions(array $fields, $multi = false, $spec = false)
    {
        $this->conditions = [];

        foreach($fields as $field => $value)
        {
            $this->conditions[] = $this->crypt($field, $value, $multi, $spec);
        }

        return implode(',', $this->conditions);
    }

    public function bind($name, ...$binds)
    {
        if(array_key_exists($name, $this->binds))
        {
            throw new \LogicException("Duplicate bind parameters. Argument: $name", 500);
        }

        $this->binds[$name] = $binds;

        return $this;
    }

    public function dependence($table, $type, array $reference = [])
    {
        $references = [];

        foreach($reference as $p => $c)
        {
            $references[] = implode('', [$p, $c]);
        }

        $this->dependencies[$table] = implode(' ', [$type, 'JOIN', $table, 'ON', implode(' AND ', $references)]);

        $this->references[] = $table;

        return $this;
    }

    public function where(...$rules)
    {
        $where = [];

        foreach($rules as $conditions)
        {
            if(!$conditions) continue;

            $this->conditions = [];

            foreach($conditions as $field => $value)
            {
                $this->conditions[] = $this->crypt($field, $value);
            }

            $where[] = implode(' AND ', $this->conditions);
        }

        if(!empty($where))
        {
            $this->where[] = Corrector::RoundFraming(implode(' OR ', $where));
        }

        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function order(array $order)
    {
        foreach($order as $field => $condition)
        {
            if(is_int($field))
            {
                $field = $condition;
                $condition = 'ASC';
            }

            $this->order[] = implode(' ', [$field, $condition]);
        }

        return $this;
    }

    protected function setAdditional()
    {
        if(!empty($this->where))
        {
            $this->chains[] = 'WHERE';
            $this->chains[] = implode(' AND ', $this->where);
        }

        if(!empty($this->order))
        {
            $this->chains[] = 'ORDER BY';
            $this->chains[] = implode(', ', $this->order);
        }

        if(!empty($this->limit))
        {
            $this->chains[] = 'LIMIT';
            $this->chains[] = $this->limit;
        }
    }

    public function onDuplicate($param = false)
    {
        if(empty($this->chains) || empty($this->fields))
        {
            return $this;
        }

        $fields = [];

        switch(gettype($param))
        {
            case 'array': $fields = $param; break;
            case 'string': $fields = [$param => $param]; break;
            default:
                foreach($this->fields as $field)
                {
                    $fields[$field] = $param ? "values($field)" : $field;
                }
                break;
        }

        $this->chains[] = 'ON DUPLICATE KEY UPDATE';
        $this->chains[] = urldecode(http_build_query($fields, false, ', '));

        $this->fields = null;

        return $this;
    }

    private function unifier()
    {
        return preg_replace_callback('/(\d):/', function($matches)
        {
            if(!array_key_exists($matches[1], $this->references))
            {
                throw new \ValueError("Reference {$matches[1]}: does not exist", 501);
            }

            return $this->references[$matches[1]].'.';

        }, implode(' ', $this->chains));
    }

    /**
     * @return false|\PDOStatement
     */
    public function exec()
    {
        $stmt = $this->DB->connection()->prepare($this->unifier());

        foreach($this->binds as $value => $bind)
        {
            $stmt->bindValue($value, ...$bind);
        }

        $stmt->execute();

        $this->abort();

        return $stmt;
    }

    public function getSql()
    {
        return $this->unifier();
    }

    public function disconnect()
    {
        $this->DB->abortConnection();
    }
}
