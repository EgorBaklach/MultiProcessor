<?php namespace DB;

final class Factory
{
    private $table_name;
    private $columns;
    private $indexes;

    private $error;
    private $update;
    private $sql;

    /**
     * @var Connection $DB
     */
    private $DB;

    /**
     * Factory constructor.
     * @param $table_name
     * @param Connection $DB
     */
    public function __construct($table_name, Connection $DB)
    {
        $this->DB = $DB;

        $rs = $this->DB->connection()->query("SHOW INDEXES FROM $table_name");

        while($index = $rs->fetch())
        {
            if (intval($index["Non_unique"])) continue;

            $this->indexes[$index["Column_name"]] = $index;
        }

        $rs = $this->DB->connection()->query("DESCRIBE $table_name");

        while($column = $rs->fetch())
        {
            $field = [];

            if($column['Key'] === 'PRI' || !empty($this->indexes[$column['Field']]))
            {
                $field['primary'] = true;
            }

            if(!empty($column["Default"]))
            {
                $field['default_value'] = $field["Default"];
            }

            if($column["Null"] === "NO" && !$field['primary'])
            {
                $field['required'] = true;
            }

            if($column['Extra'] === 'auto_increment')
            {
                $field['autocomplete'] = true;
            }

            switch(true)
            {
                case preg_match("/^(varchar|char|varbinary|enum|json)/", $column["Type"]):

                    $field["data_type"] = "string";

                    break;

                case preg_match("/^(text|longtext|mediumtext|longblob)/", $column["Type"]):

                    $field["data_type"] = "text";

                    break;

                case preg_match("/^(datetime|timestamp)/", $column["Type"]):

                    $field["data_type"] = "datetime";

                    break;

                case preg_match("/^(date)/", $column["Type"]):

                    $field["data_type"] = "date";

                    break;

                case preg_match("/^(int|smallint|bigint|tinyint)/", $column["Type"]):

                    $field["data_type"] = "integer";

                    break;

                case preg_match("/^(float|double|decimal)/", $column["Type"]):

                    $field["data_type"] = "float";

                    break;

                default:

                    $field["data_type"] = "UNKNOWN";
            }

            if($field['data_type'] === 'string')
            {
                preg_match("/\((\d+)\)$/", $column["Type"], $match);

                if(!empty($match[1]) && $match[1] == 1 && in_array($column["Default"], ['N', 'Y']))
                {
                    $field["data_type"] = "boolean";
                    $field["values"] = ['N', 'Y'];
                }
            }

            $this->columns[$column['Field']] = $field;
        }

        $this->table_name = $table_name;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * @param array $fields
     * @param bool $multi
     * @param bool $and
     * @param bool $special_chars
     * @return string
     */
    public function convertToDB($fields, $multi = false, $and = false, $special_chars = false)
    {
        $rows = [];

        foreach($fields as $field => &$value)
        {
            if(is_int($field))
            {
                $rows[] = str_replace('this', $this->table_name, $value);
                continue;
            }

            switch(gettype($value))
            {
                case 'array':

                    $value = "('".implode("', '", $value)."')";

                    break;
                case 'NULL':

                    $value = 'NULL';

                    break;
                default:

                    $field_original = preg_replace('/[^\w_.]+/i', '', $field);
                    $field_original = str_replace('this.', '', $field_original);

                    switch($this->columns[$field_original]['data_type'])
                    {
                        case 'string':
                        case 'text':
                        case 'date':
                        case 'datetime':
                        case 'boolean':
                            $value = $this->DB->connection()->quote($value);
                            break;
                    }
            }

            $field = str_replace('this', $this->table_name, $field);

            $rows[] = $field.($special_chars ? '' : '=').$value;
        }

        if($multi)
        {
            return '('.implode(',', $fields).')';
        }

        return implode($and ? ' AND ' : ', ',  $rows);
    }

    /**
     * @example (
    ['*'],
    ['this.id=' => 123],
    ['this.id' => 'ASC'],
    ['reference' => [
        'table_name' => 'b_instamall_product_files',
        'type' => 'LEFT',
        'reference' => [
            'ref.id=' => 'this.picture_id'
        ]
    ]],
    100
    )
     *
     * @param $fields
     * @param array $where
     * @param array $order
     * @param array $join
     * @param mixed $limit
     * @return $this
     */
    public function select($fields, $where = [], $order = [], $join = [], $limit = false)
    {
        $this->sql = false;

        try
        {
            foreach($join as $name => &$data)
            {
                foreach($data['reference'] as $p => &$c)
                {
                    foreach(['value.' => '', 'this' => $this->table_name] as $f => $r)
                    {
                        if(strpos($c, $f) === 0)
                        {
                            $c = preg_replace("/$f/", $r, $c, 1);
                            break;
                        }
                    }

                    $c = implode('', [str_replace('ref', $name, $p), $c]);
                }

                $data = implode(' ', [
                    $data['type'],
                    'JOIN',
                    $data['table_name'],
                    $name,
                    'ON',
                    implode(' AND ', $data['reference'])
                ]);
            }

            if(!empty($join))
            {
                foreach($fields as &$field)
                {
                    $field = str_replace('this', $this->table_name, $field);
                }
            }

            $sql = [
                'SELECT',
                implode(', ', $fields),
                'FROM',
                $this->table_name
            ];

            if(!empty($join))
            {
                $sql[] = implode(' ', $join);
            }

            if(!empty($where))
            {
                $sql[] = implode(' ', [
                    'WHERE',
                    $this->convertToDB($where, false, true, true)
                ]);
            }

            if(!empty($order))
            {
                foreach($order as $name => &$field)
                {
                    $field = implode(' ', [str_replace('this', $this->table_name, $name), $field]);
                }

                $sql[] = "ORDER BY ".implode(', ', $order);
            }

            if(!empty($limit))
            {
                $sql[] = "LIMIT $limit";
            }

            $this->sql = implode(' ', $sql);
        }
        catch(\PDOException $e)
        {
            $this->error = $e->getMessage();
        }

        return $this;
    }

    /**
     * @param $update
     * @param array $where
     * @param array $order
     * @param array $join
     * @param mixed $limit
     * @return $this
     */
    public function update($update, $where = [], $order = [], $join = [], $limit = false)
    {
        $this->sql = false;

        try
        {
            foreach($join as $name => &$data)
            {
                foreach($data['reference'] as $p => &$c)
                {
                    $c = implode('', [
                        str_replace('ref', $name, $p),
                        str_replace('this', $this->table_name, $c)
                    ]);
                }

                $data = implode(' ', [
                    $data['type'],
                    'JOIN',
                    $data['table_name'],
                    $name,
                    'ON',
                    implode(' AND ', $data['reference'])
                ]);
            }

            if(!empty($join))
            {
                foreach($update as &$field)
                {
                    $field = str_replace('this', $this->table_name, $field);
                }
            }

            $sql = [
                'UPDATE',
                $this->table_name
            ];

            if(!empty($join))
            {
                $sql[] = implode(' ', $join);
            }

            $sql[] = implode(' ', [
                'SET',
                $this->convertToDB($update)
            ]);

            if(!empty($where))
            {
                $sql = array_merge($sql, [
                    'WHERE',
                    $this->convertToDB($where, false, true, true)
                ]);
            }

            if(!empty($order))
            {
                foreach($order as $name => &$field)
                {
                    $field = implode(' ', [str_replace('this', $this->table_name, $name), $field]);
                }

                $sql[] = "ORDER BY ".implode(', ', $order);
            }

            if(!empty($limit))
            {
                $sql[] = "LIMIT $limit";
            }

            $this->sql = implode(' ', $sql);
        }
        catch(\PDOException $e)
        {
            $this->error = $e->getMessage();
        }

        return $this;
    }

    /**
     * @param $insert
     * @param array $updates
     * @param bool $multi
     * @return $this
     */
    public function merge($insert, $updates = [])
    {
        if(empty($updates))
        {
            $updates = array_keys($this->columns);
        }

        $this->sql = false;
        $this->update = $updates;

        try
        {
            $sql = [
                'INSERT INTO',
                $this->table_name,
                "(".implode(', ', $updates).")",
                'VALUES',
                implode(', ', $insert),
            ];

            $this->sql = implode(' ', $sql);

            $this->onDuplicate(true);
        }
        catch(\PDOException $e)
        {
            $this->error = $e->getMessage();
        }

        return $this;
    }

    /**
     *
     * @param mixed $param
     * @return $this
     */
    public function onDuplicate($param = false)
    {
        if(empty($this->sql) || empty($this->update))
        {
            return $this;
        }

        $fields = [];

        switch(gettype($param))
        {
            case 'array': $fields = $param; break;
            case 'string': $fields = [$param => $param]; break;
            default:
                foreach($this->update as $field)
                {
                    $fields[$field] = $param ? "values($field)" : $field;
                }
                break;
        }

        $this->sql .= implode(' ', ['', 'ON DUPLICATE KEY UPDATE', urldecode(http_build_query($fields, false, ', '))]);

        $this->update = null;

        return $this;
    }

    /**
     * @param $insert
     * @param array $fields
     * @param bool $multi
     * @return $this
     */
    public function insert($insert, $fields = [], $multi = false)
    {
        if(empty($fields))
        {
            $fields = array_keys($this->columns);
            unset($fields[0]);
        }

        $this->sql = false;
        $this->update = $fields;

        try
        {
            $insert = $multi ? implode(',', $insert) : $this->convertToDB($insert, true);

            $fields = implode(',', $fields);

            $this->sql = implode(' ', [
                'INSERT INTO',
                $this->table_name,
                "($fields)",
                'VALUES',
                $insert
            ]);
        }
        catch(\PDOException $e)
        {
            $this->error = $e->getMessage();
        }

        return $this;
    }

    /**
     * @param $where
     * @param array $fields
     * @param array $join
     * @return $this
     */
    public function delete($where, $fields = [], $join = [])
    {
        $this->sql = false;

        try
        {
            foreach($join as $name => &$data)
            {
                foreach($data['reference'] as $p => &$c)
                {
                    $c = implode('', [
                        str_replace('ref', $name, $p),
                        str_replace('this', $this->table_name, $c)
                    ]);
                }

                $data = implode(' ', [
                    $data['type'],
                    'JOIN',
                    $data['table_name'],
                    $name,
                    'ON',
                    implode(' AND ', $data['reference'])
                ]);
            }

            if(!empty($join))
            {
                foreach($fields as &$field)
                {
                    $field = str_replace('this', $this->table_name, $field);
                }
            }

            $sql = [
                'DELETE',
                implode(', ', $fields),
                'FROM',
                $this->table_name
            ];

            if(!empty($join))
            {
                $sql[] = implode(' ', $join);
            }

            $sql[] = implode(' ', [
                'WHERE',
                $this->convertToDB($where, false, true, true)
            ]);

            $this->sql = implode(' ', $sql);
        }
        catch(\PDOException $e)
        {
            $this->error = $e->getMessage();
        }

        return $this;
    }

    /**
     * @return \PDOStatement
     * Set validating PDO method for sql queries, if it should be
     */
    public function exec()
    {
        return $this->DB->connection()->query($this->sql);
    }

    /**
     * @return mixed
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    public function disconnect()
    {
        $this->DB->abortConnection();
    }
}
