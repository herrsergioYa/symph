<?php

//These function can become available in the next releases so be careful!

if (!function_exists('array_first'))
{
    function array_first(array $array, $sentinel = null)
    {
        foreach ($array as $key => $value)
        {
            return $value;
        }
        return $sentinel;
    }
}

if (!function_exists('array_last'))
{
    function array_last(array $array, $sentinel = null)
    {
        return (!empty($array)) ? array_slice($array, -1, 1, false)[0] : $sentinel;
    }
}

//Some BX cores declare it w/o function_exists() so it is very tricky...
/*if (!function_exists('is_array_assoc'))
{
    function is_array_assoc(array &$array)
    {
        if(!is_array($array))
            return false;

        $i = 0;
        foreach($array as $key => $unused)
        {
            if($key !== $i)
            {
                return true;
            }
            else
            {
                $i ++;
            }
        }

        return false;
    }
}*/
