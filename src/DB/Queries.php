<?php namespace DB;

use Helpers\Corrector;

class Queries extends Factory
{
    public function select(array $fields = ['*']): self
    {
        $this->chains = ['SELECT', implode(', ', $fields), 'FROM', $this->table];

        if(!empty($this->dependencies))
        {
            $this->chains[] = implode(' ', $this->dependencies);
        }

        $this->setAdditional();

        return $this;
    }

    public function update(array $update): self
    {
        $this->chains = ['UPDATE', $this->table];

        if(!empty($this->dependencies))
        {
            $this->chains[] = implode(' ', $this->dependencies);
        }

        $this->chains[] = 'SET';
        $this->chains[] = $this->conditions($update, false, '=');

        $this->setAdditional();

        return $this;
    }

    public function insert(array $insert, array $fields = null): self
    {
        $this->fields = $fields ?? array_keys($insert);

        $this->chains = ['INSERT INTO', $this->table, Corrector::RoundFraming(implode(',', $this->fields))];

        $insert = !empty($fields) ? implode(',', $insert) : Corrector::RoundFraming($this->conditions($insert, true));

        $this->chains[] = 'VALUES';
        $this->chains[] = $insert;

        return $this;
    }

    public function merge(array $insert, array $fields): self
    {
        return $this->insert($insert, $fields)->onDuplicate(true);
    }

    public function delete(array $fields = []): self
    {
        $this->chains = ['DELETE', implode(',', $fields), 'FROM', $this->table];

        if(!empty($this->dependencies))
        {
            $this->chains[] = implode(' ', $this->dependencies);
        }

        $this->setAdditional();

        return $this;
    }
}