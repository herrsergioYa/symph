<?php

namespace Gsv\Db;

class MedooProxyStmt
{
    /**
     * @var \Medoo\Medoo $m
     */
    protected $m;

    /**
     * @var string $table
     */
    protected $table;

    /**
     * @var array $keys
     */
    protected $keys;

    /**
     * @var array $values
     */
    protected $values;

    /**
     * @var array $fields
     */
    protected $fields;

    /**
     * @param \Medoo\Medoo $m
     * @param string $table
     * @param array $keys
     * @param array $values
     * @param array $fields
     */
    public function __construct(\Medoo\Medoo $m, string $table, array $keys, array $values, array $fields)
    {
        $this->m = $m;
        $this->table = $table;
        $this->keys = $keys;
        $this->values = $values;
        $this->fields = $fields;
    }

    public function execute()
    {
        if(!$this->m->has($this->table, $this->keys)) {
            $stmt = $this->m->insert($this->table, $this->fields);
        } else if($this->values) {
            $stmt = $this->m->update($this->table, $this->values, $this->keys);
        } else {
            return true;
        }
        return $stmt && $stmt->execute();
    }
}