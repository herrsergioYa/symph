<?php

namespace Gsv\Db;

class mysqlie
{
    /**
     * @var \mysqli $mysqli
     */
    public /*\mysqli*/ $mysqli;

    public function __construct(\mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function query(string $sql, array $params = [])
    {
        return static::execute_query($this->mysqli, $sql, $params);
    }

    public function prepare(string $sql)
    {
        $arStmt = mysqlie::prepare_stmt($this->mysqli, $sql, null);
        return $arStmt ? new mysqlie_stmt($arStmt['stmt'], $arStmt) : false;
    }

    //----------------------------------

    public static function execute_query(\mysqli $mysql, string $query, array $params = [])
    {
        $arStmt = mysqlie::prepare_stmt($mysql, $query, $params);
        return $arStmt ? mysqlie_stmt::execute_stmt($arStmt['stmt'], $arStmt) : false;
    }

    protected static function prepare_stmt(\mysqli $mysql, string $query, /*?array*/ $params)
    {
        $arQuery = mysqlie_stmt::prepare_query($query, $params);

        $arQuery['stmt'] = $mysql->prepare($arQuery[0]);
        if(empty($arQuery['stmt']))
        {
            return false;
        }

        return $arQuery;
    }

    //----------------

    public function insert(string $table, array $params) //: bool
    {
        $arStmt = $this->upsert_sql($table, $params, false, false);
        return $this->query($arStmt[0], $arStmt[1]);
    }

    public function insertStatement(string $table, array $params) //: mysqlie_stmt|bool
    {
        $arStmt = $this->upsert_sql($table, $params, true, false);
        $arQuery = mysqlie_stmt::prepare_query($arStmt[0]);
        if(empty($arQuery))
        {
            return false;
        }
        foreach ($arQuery[2] as &$paramName)
        {
            $paramName = $arStmt[1][$paramName];
        }
        unset($paramName);
        $arQuery['stmt'] = $this->mysqli->prepare($arQuery[0]);
        return $arQuery['stmt'] ? new mysqlie_stmt($arQuery['stmt'], $arQuery) : false;
    }

    public function update(string $table, array $params, array $where) //: bool
    {
        $arStmt = $this->upsert_sql($table, $params, false, true);
        $cond = $this->where($where);
        if(empty($cond))
        {
            return false;
        }
        $arStmt[0] .= ' ' . $cond[0];
        $arStmt[1] += $cond[1];
        return $this->query($arStmt[0], $arStmt[1]);
    }

    public function upsert(string $table, array $params, array $upd) //: bool
    {
        $arStmt = $this->upsert_sql($table, $params, false, $upd);
        return $this->query($arStmt[0], $arStmt[1]);
    }

    public function upsertStatement(string $table, array $params, array $upd) //: mysqlie_stmt|bool
    {
        $arStmt = $this->upsert_sql($table, $params, true, $upd);
        $arQuery = mysqlie_stmt::prepare_query($arStmt[0]);
        if(empty($arQuery))
        {
            return false;
        }
        foreach ($arQuery[2] as &$paramName)
        {
            $paramName = $arStmt[1][$paramName];
        }
        unset($paramName);
        $arQuery['stmt'] = $this->mysqli->prepare($arQuery[0]);
        return $arQuery['stmt'] ? new mysqlie_stmt($arQuery['stmt'], $arQuery) : false;
    }

    public function delete(string $table, array $where) //: bool
    {
        $arStmt = $this->delete_sql($table);
        $cond = $this->where($where);
        if(empty($cond))
        {
            return false;
        }
        $arStmt[0] .= ' ' . $cond[0];
        $arStmt[1] += $cond[1];
        return $this->query($arStmt[0], $arStmt[1]);
    }

    public function upsert_sql(string $table, array $params, bool $isTmpl, /*bool|array*/ $upd)
    {
        $arg = $args = $argv = [];

        if(is_array($upd))
        {
            if(empty($upd))
            {
                //return false;
            }
            if(!$isTmpl)
            {
                $params += $upd;
                $updList = array_fill_keys(array_keys($upd), -1);
            }
            else
            {
                $updList = [];
                foreach ($upd as $k => $v)
                {
                    if(!in_array($v, $params))
                    {
                        while(array_key_exists($k, $params))
                        {
                            $k .= '1';
                        }
                        $params[$k] = $v;
                    }
                    $updList[$v] = -1;
                }
            }
            $upd = false;
        }
        else
        {
            $updList = null;
        }

        $table = mysqli_real_escape_string($this->mysqli, $table);

        $k = 0;

        foreach ($params as $name => $value) {
            $realName =  $isTmpl ? $value : $name;
            $arg[] = '`' . mysqli_real_escape_string($this->mysqli, $realName) . '`';
            $code = "%param${k}" . '$';
            if(!$isTmpl) {
                if (is_numeric($value)) {
                    if (intval($value) == $value) {
                        $code .= 'i';
                    } else {
                        $code .= 'd';
                    }
                } else {
                    $code .= 's';
                }
            } else {
                $matches = [];
                if(preg_match('/^\d*([ids])\d*$/', '' . $name, $matches)) {
                    $code .= $matches[1];
                } else {
                    $code .= 's';
                }
            }
            $args[] = $code;
            $argv["param${k}"] = $value;
            $k++;

            if($updList && array_key_exists($realName, $updList))
            {
                $updList[$realName] = count($arg) - 1;
            }
        }

        if($upd)
        {
            $sql = [];

            foreach ($arg as $i => $name)
            {
                $sql[] = "$name = " . $args[$i];
            }

            $sql = implode(',', $sql);

            $sql = <<<SQL
        UPDATE `$table`
        SET $sql
        WHERE
SQL;
        }
        else
        {
            if($updList !== null)
            {
                if($updList)
                {
                    $sqlUpd = [];

                    foreach ($updList as $i)
                    {
                        $sqlUpd[] = $arg[$i] . " = " . $args[$i];
                    }

                    $sqlUpd = implode(',', $sqlUpd);

                    $sqlUpd = <<<SQL
    ON DUPLICATE KEY UPDATE $sqlUpd
SQL;
                    $insertMod = "";
                }
                else
                {
                    $sqlUpd = "";
                    $insertMod = " IGNORE";
                }
            }
            else
            {
                $sqlUpd = '';
                $insertMod = "";
            }

            $arg = implode(',', $arg);
            $args = implode(',', $args);

            $sql = <<<SQL
    INSERT$insertMod INTO `$table`($arg)
    VALUES($args)$sqlUpd
SQL;

        }

        return [$sql, $argv];
    }

    public function delete_sql(string $table)
    {
        $table = mysqli_real_escape_string($this->mysqli, $table);
        $sql = <<<SQL
    DELETE FROM `$table`
    WHERE 
SQL;
        return [$sql, []];
    }

    public function where(array $filter)
    {
        $num = 0;
        return $this->where_sql($filter, $num);
    }

    protected function where_sql(array $filter, &$num, string $cond = 'AND')
    {
        $result = [];
        $params = [];
        $userParams = [];

        foreach ($filter as $k => $v)
        {
            if(is_array($v))
            {
                $matches = [];
                if(preg_match('/^\d*(and|or)\d*$/i', $k, $matches))
                {
                    $res = $this->where_sql($v, $num, $matches[1]);
                    $result[] = $res[0];
                    $params += $res[1];
                }
                else
                {
                    //TODO: IN, NOT IN, BETWEEN....
                    return false;
                }
            }
            else
            {
                $matches = [];
                if(preg_match('/^([><=!])(\S+)$/', $k, $matches))
                {
                    $op = $matches[1];
                    $str = $matches[2];
                }
                else
                {
                    $op = '=';
                    $str = $k;
                }

                if($op == '!')
                {
                    $op = '<>';
                }

                $arg = '`' . mysqli_real_escape_string($this->mysqli, $str) . '`';

                if($v === null)
                {
                    if($op == '=')
                    {
                        $result[] = $arg . ' IS NULL';
                    }
                    else if($op == '<>' || $op == '!=')
                    {
                        $result[] = $arg . ' IS NOT NULL';
                    }
                    else
                    {
                        $result[] = '0 = 1';
                    }
                    continue;
                }

                $code = "whr" . ++$num;
                $params[$code] = $v;
                $code = "%" . $code . '$';
                if (is_numeric($v)) {
                    if (intval($v) == $v) {
                        $code .= 'i';
                    } else {
                        $code .= 'd';
                    }
                } else {
                    $code .= 's';
                }

                $result[] = $arg . ' ' . $op . ' ' . $code;
            }
        }

        if($result)
        {
            $result = implode(' ' . $cond . ' ', $result);
            $result = "($result)";
        }
        else if(strtoupper(trim($cond)) == 'AND')
        {
            $result = '(1 = 1)';
        }
        else if(strtoupper(trim($cond)) == 'OR')
        {
            $result = '(0 = 1)';
        }
        else
        {
            return null;
        }

        return [$result, $params, $userParams];
    }
}