<?php

namespace Gsv\Db;

class Medoo extends \Medoo\Medoo
{
    /**
     * @param string $table
     * @param array $keys
     * @param array $values
     * @return \PDOStatement|MedooProxyStmt|bool
     */
    public function upsert(string $table, array $keys, array $values)
    {
        if(empty($keys)) {
            return false;
        }

        $fields = $keys;
        foreach ($values as $name => $value) {
            if(!preg_match('#^([^[]]+)+\[([-+/*])\]$#', $name)
                && !array_key_exists($name, $fields)) {
                $fields[$name] = $value;
            }
        }

        /*if(empty($values)) {
            if(!$this->has($table, $keys)) {
                return $this->insert($table, $keys);
            } else {
                return true;
            }
        }*/

        if($this->getDbType() != 'mysql') {
            /*if(!$this->has($table, $keys)) {
                $stmt = $this->insert($table, $fields);
            } else if($values) {
                $stmt = $this->update($table, $values, $keys);
            } else {
                return true;
            }
            return $stmt && $stmt->execute();*/
            return new MedooProxyStmt($this, $table, $keys, $values, $fields);
        } else {

            $fieldNames = [];
            $fieldValues = [];
            $valueUpdates = [];
            $index = 0;
            $params = [];

            foreach ($fields as $name => $value) {
                $fieldNames[] = "<$name>";
                $fieldValues[] = ":param$index";
                $params["param$index"] = $value;
                ++$index;
            }

            foreach ($values as $name => $value) {
                $matches = [];
                if(preg_match('#^([^[]]+)+\[([-+/*])\]$#', $name, $matches)) {
                    $valueUpdates[] = "<${matches[0]}> = <${matches[0]}> ${matches[1]} :param$index";
                } else {
                    $valueUpdates[] = "<$name> = :param$index";
                }
                $params["param$index"] = $value;
                ++$index;
            }

            $fieldNames = implode(',', $fieldNames);
            $fieldValues = implode(',', $fieldValues);

            if($valueUpdates) {
                $sql = "INSERT INTO ";
            } else {
                $sql = 'INSERT IGNORE INTO ';
            }

            $sql .= "<$table>($fieldNames) VALUES($fieldValues)";

            if($valueUpdates) {
                $valueUpdates = implode(',', $valueUpdates);
                $sql .= " ON DUPLICATE KEY UPDATE $valueUpdates";
            }

            return $this->query($sql, $params);
        }
    }

    public function getDbType()
    {
        if(isset($this->type)) {
            $type = $this->type;
        } elseif(isset($this->database_type)) {
            $type = $this->database_type;
        } else {
            $type = '';
        }
        $type = strtolower($type);
        if($type == 'mariadb') {
            $type = 'mysql';
        }
        return $type;
    }
}