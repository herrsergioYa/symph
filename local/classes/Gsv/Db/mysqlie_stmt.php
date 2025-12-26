<?php

namespace Gsv\Db;


class mysqlie_stmt
{
    /**
     * @var \mysqli_stmt $stmt
     */
    public /*\mysqli_stmt*/ $stmt;


    /**
     * @var array $arQuery
     */
    public /*array*/ $arQuery;

    public function __construct(\mysqli_stmt $stmt, array $arQuery)
    {
        $this->stmt = $stmt;
        $this->arQuery = $arQuery;
    }

    public function execute(array $params = [])
    {
        return mysqlie_stmt::execute_stmt($this->stmt, $this->arQuery, $params);
    }

    //---------------------------------

    public static function prepare_query($query, $args = null, ...$_)
    {
        $arResult = [];
        $arAdditions = [];
        $argv = null;
        $num = 0;
        $codes = '';
        $offset = 0;
        while(true)
        {
            $matches = [];
            if(!preg_match('/%(%|(\d+|[\d\w]+\$)?[ids])/', $query, $matches, PREG_OFFSET_CAPTURE, $offset))
            {
                break;
            }

            if($matches[1][0] == '%')
            {
                $replacement = '%';
            }
            else
            {
                $num++;
                $replacement = '?';
                $codes .= substr($matches[1][0], -1);
                if($matches[2][0])
                {
                    $index = $matches[2][0];
                }
                else
                {
                    $index = $num;
                }
                if(is_numeric($index))
                {
                    $index = intval($index) - 1;
                    if($args === null)
                    {
                        $arResult[~count($arResult)] = $index;
                    }
                    else
                    {
                        if ($argv === null)
                        {
                            if (array_is_list($args))
                            {
                                $argv = &$args;
                            }
                            else
                            {
                                $argv = array_values($args);
                            }
                        }
                        $arResult[] = $argv[$index];
                    }
                    if($_)
                    {
                        foreach ($_ as $k => $v)
                        {
                            if(!array_key_exists($k, $arAdditions))
                            {
                                $arAdditions[$k] = [];
                            }
                            $arAdditions[$k][] = array_slice($v, $index, 1, false)[0];
                        }
                    }
                }
                else
                {
                    $index = substr($index, 0, -1);
                    if($args == null)
                    {
                        $arResult[] = $index;
                    }
                    else
                    {
                        $arResult[] = $args[$index];
                    }
                    if($_)
                    {
                        foreach ($_ as $k => $v)
                        {
                            if(!array_key_exists($k, $arAdditions))
                            {
                                $arAdditions[$k] = [];
                            }
                            $arAdditions[$k][] = $v[$index];
                        }
                    }
                }
            }

            $offset = $matches[0][1];
            $query = substr($query, 0, $offset) . $replacement . substr($query, $offset + strlen($matches[0][0]));
            $offset += 1;
        }

        $arResult = [
            $query,
            $codes,
            $arResult,
        ];

        if($arAdditions)
        {
            $arResult = array_merge($arResult, array_values($arAdditions));
        }

        return $arResult;
    }

    public static function prepare_args(array $tmpl, array $args)
    {
        $arParams = [];
        $argv = null;

        foreach ($tmpl as $k => $v)
        {
            if($k < 0)
            {
                if ($argv === null)
                {
                    if (array_is_list($args))
                    {
                        $argv = &$args;
                    }
                    else
                    {
                        $argv = array_values($args);
                    }
                }
                $arParams[] = $argv[$v];
            }
            else
            {
                $arParams[] = $args[$v];
            }
        }

        return $arParams;
    }

    public static function bind_args(\mysqli_stmt $stmt, array $arResult, $offsetOrArgs = null)
    {
        if(is_array($offsetOrArgs))
        {
            $arParams = static::prepare_args($arResult[2], $offsetOrArgs);
        }
        else if(is_numeric($offsetOrArgs))
        {
            $arParams = $arResult[2 + $offsetOrArgs];
        }
        else
        {
            $arParams = $arResult[2];
        }

        if($arParams)
        {
            return $stmt->bind_param($arResult[1], ...$arParams);
        }
        else
        {
            return true;
        }
    }

    public static function execute_stmt(\mysqli_stmt $stmt, array $arQuery, $offsetOrArgs = null)
    {
        $ok = mysqlie_stmt::bind_args($stmt, $arQuery, $offsetOrArgs);
        if(!$ok)
        {
            return false;
        }

        $ok = $stmt->execute();
        if(!$ok)
        {
            return false;
        }

        $result = $stmt->get_result();
        if($result)
        {
            return $result;
        }
        else
        {
            return true;
        }
    }

    //----------------

    public static function stmt_execute(\mysqli_stmt $stmt, /*?array*/ $params = null)
    {
        static $canDoItNatively = null;
        if($canDoItNatively === null)
        {
            $canDoItNatively = defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80100;
        }
        if($canDoItNatively)
        {
            return $stmt->execute($params);
        }

        if($params)
        {
            $codes = str_repeat('s', count($params));
            $ok = $stmt->bind_param($codes, ...$params);
            if (!$ok)
            {
                return false;
            }
        }

        return $stmt->execute();
    }
}