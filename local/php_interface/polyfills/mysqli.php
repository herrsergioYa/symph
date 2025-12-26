<?php

if(!class_exists(\mysqli::class, true))
{
    return;
}

if(!function_exists('mysqli_execute_query'))
{
    function mysqli_execute_query(\mysqli $mysql, string $query, ?array $params = null)
    {
        $stmt = $mysql->prepare($query);
        if(!$stmt)
        {
            return false;
        }

        if(function_exists('mysqlie_stmt_execute'))
        {
            $ok = mysqlie_stmt_execute($stmt, $params);
            if(!$ok)
            {
                return false;
            }
        }
        else
        {
            if($params)
            {
                $codes = str_repeat('s', count($params));
                $ok = $stmt->bind_param($codes, ...$params);
                if (!$ok)
                {
                    return false;
                }
            }

            $ok = $stmt->execute();
            if(!$ok)
            {
                return false;
            }
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
}
else if(defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80200)
{
    //Native function
}
else
{
    //Some other polyfill is used!?
}