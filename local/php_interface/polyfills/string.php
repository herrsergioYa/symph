<?php

//https://github.com/symfony/polyfill/blob/1.x/src/Php80/Php80.php

if (!function_exists('str_starts_with'))
{
    function str_starts_with($haystack, $needle)
    {
        return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
}

if (!function_exists('str_ends_with'))
{
    function str_ends_with($haystack, $needle)
    {
        if ('' === $needle || $needle === $haystack) {
            return true;
        }

        if ('' === $haystack) {
            return false;
        }

        $needleLength = \strlen($needle);

        return $needleLength <= \strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needleLength);
    }
}

if (!function_exists('str_contains'))
{
    function str_contains($haystack, $needle)
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}
