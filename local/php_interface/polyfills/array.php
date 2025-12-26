<?php

// https://github.com/symfony/polyfill/blob/1.x/src/Php73/bootstrap.php

if (!function_exists('array_key_first'))
{
    function array_key_first(array $array)
    {
        foreach($array as $key => $value)
        {
            return $key;
        }
        return null;
    }
}


if (!function_exists('array_key_last'))
{
    function array_key_last(array $array)
    {
        return (!empty($array)) ? key(array_slice($array, -1, 1, true)) : null;
    }
}

//if (!function_exists('array_first'))
//{
//    function array_first(array $array, $sentinel = null)
//    {
//        foreach ($array as $key => $value)
//        {
//            return $value;
//        }
//        return $sentinel;
//    }
//}

//if (!function_exists('array_last'))
//{
//    function array_last(array $array, $sentinel = null)
//    {
//        return (!empty($array)) ? array_slice($array, -1, 1, false)[0] : $sentinel;
//    }
//}

// https://github.com/symfony/polyfill/blob/1.x/src/Php81/Php81.php

if (!function_exists('array_is_list'))
{
    function array_is_list(array $array)
    {
        //if(!is_array($array))
        //    return false;

        if ([] === $array || $array === array_values($array)) {
            return true;
        }

        $i = 0;
        foreach($array as $key => $unused)
        {
            if($key !== $i)
            {
                return false;
            }
            else
            {
                $i ++;
            }
        }

        return true;
    }
}