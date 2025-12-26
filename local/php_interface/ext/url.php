<?php

if(!function_exists('build_url'))
{
    function build_url($parts)
    {
        $result = '';

        if (isset($parts['scheme'])) {
            $result .= $parts['scheme'] . ':';
        }

        if (isset($parts['host'])) {

            $result .= '//';

            if (isset($parts['user'])) {
                $result .= $parts['user'];

                if (isset($parts['pass'])) {
                    $result .= ':' . $parts['pass'];
                }

                $result .= '@';
            }

            $result .= $parts['host'];

            if (isset($parts['port'])) {
                $result .= ':' . $parts['port'];
            }
        }

        if (isset($parts['path'])) {
            $result .= $parts['path'];
        }

        if (isset($parts['query'])) {
            $result .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }

        return $result !== '' ? $result : false;
    }
}