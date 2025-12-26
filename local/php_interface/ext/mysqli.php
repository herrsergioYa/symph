<?php

if(!function_exists('mysqlie_prepare_query'))
{
    function mysqlie_prepare_query($query, $args = null, ...$_)
    {
        return \Gsv\Db\mysqlie_stmt::prepare_query($query, $args, ...$_);
    }
}

if(!function_exists('mysqlie_prepare_args'))
{
    function mysqlie_prepare_args(array $tmpl, array $args)
    {
        return \Gsv\Db\mysqlie_stmt::prepare_args($tmpl, $args);
    }
}

if(!function_exists('mysqlie_bind_args'))
{
    function mysqlie_bind_args(\mysqli_stmt $stmt, array $arResult, $offsetOrArgs = null)
    {
        return \Gsv\Db\mysqlie_stmt::bind_args($stmt, $arResult, $offsetOrArgs);
    }
}

if(!function_exists('mysqlie_stmt_execute'))
{
    function mysqlie_stmt_execute(\mysqli_stmt $stmt, /*?array*/ $params = null)
    {
        return \Gsv\Db\mysqlie_stmt::stmt_execute($stmt, $params);
    }
}

if(!function_exists('mysqlie_execute_query'))
{
    function mysqlie_execute_query(\mysqli $mysql, string $query, array $params = [])
    {
        return \Gsv\Db\mysqlie::execute_query($mysql, $query, $params);
    }
}

if(!function_exists('mysqlie_execute_stmt'))
{
    function mysqlie_execute_stmt(\mysqli_stmt $stmt, array $arQuery, $offsetOrArgs = null)
    {
        return \Gsv\Db\mysqlie_stmt::execute_stmt($stmt, $arQuery, $offsetOrArgs);
    }
}