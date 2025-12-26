<?php

namespace Gsv\Util;

class Security
{
    public static function signArray(array $data, string $salt = '', string $key = '', bool $base64 = false): string
    {
        $data = static::serialize($data);
        if($base64)
            $data = base64_encode($data);
        return static::sign($data, $salt, $key);
    }

    public static function unsignArray(string $message, string $salt = '', string $key = '', bool $base64 = false): array | false
    {
        $data = static::unsign($message, $salt, $key);
        if($base64 && $data !== false)
            $data = base64_decode($data);
        if($data !== false)
            $data = static::deserialize($data);
        return $data;
    }

    public static function encryptArray(array $data, string $key = '', bool $binary = false) : string
    {
        return static::encrypt(static::serialize($data), $key, $binary);
    }

    public static function decryptArray(string $data, string $key = '', bool $binary = false) : array | false
    {
        $data = static::decrypt($data, $key, $binary);
        if($data !== false)
            $data = static::deserialize($data);
        return $data;
    }

    public static function protect(string $message, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false): string | false
    {
        $data = static::sign($message, $salt, $signerKey);
        if($data !== false)
        {
            $data = static::encrypt($data, $cipherKey, $binary);
        }
        return $data;
    }

    public static function unprotect(string $message, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false): string | false
    {
        $data = static::decrypt($message, $cipherKey, $binary);
        if($data !== false)
        {
            $data = static::unsign($data, $salt, $signerKey);
        }
        return $data;
    }

    public static function protectArray(array $data, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false): string|false
    {
        return static::protect(static::serialize($data), $salt, $signerKey, $cipherKey, $binary);
    }

    public static function unprotectArray(string $data, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false): array | false
    {
        $data = static::unprotect($data, $salt, $signerKey, $cipherKey, $binary);
        if($data !== false)
            $data = static::deserialize($data);
        return $data;
    }

    public static function signTillArray(array $data, $time, string $salt = '', string $key = ''): string
    {
        return static::signTill(static::serialize($data), $time, $salt, $key);
    }

    public static function unsignBeforeArray(string $message, string $salt = '', string $key = '')
    {
        $data = static::unsignBefore($message, $salt, $key);
        if($data !== false)
            $data = static::deserialize($data);
        return $data;
    }

    public static function protectTill(string $message, $time, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false): string
    {
        $data = static::signTill($message, $time, $salt, $signerKey);
        if($data !== false)
        {
            $data = static::encrypt($data, $cipherKey, $binary);
        }
        return $data;
    }

    public static function unprotectBefore(string $message, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false)
    {
        $data = static::decrypt($message, $cipherKey, $binary);
        if($data !== false)
        {
            $data = static::unsignBefore($data, $salt, $signerKey);
        }
        return $data;
    }

    public static function protectTillArray($data, $time, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false): string
    {
        return static::protectTill(static::serialize($data), $time, $salt, $signerKey, $cipherKey, $binary);
    }

    public static function unprotectBeforeArray(string $data, string $salt = '', string $signerKey = '', string $cipherKey = '', bool $binary = false)
    {
        $data = static::unprotectBefore($data, $salt, $signerKey, $cipherKey, $binary);
        if($data !== false)
            $data = static::deserialize($data);
        return $data;
    }

    /**
     * @param $time
     * @return mixed
     * @throws \Exception
     */
    protected static function getTimeStamp($time)
    {
        if ($time instanceof \DateTime)
        {
            $time = $time->getTimestamp();
        }
        elseif ($time instanceof \DateInterval)
        {
            $dt = new \DateTime();
            $dt->add($time);
            $time = $dt->getTimestamp();
            unset($dt);
        }
        elseif (is_string($time) && strlen($time) && $time[0] == 'P')
        {
            $dt = new \DateTime();
            $dt->add(new \DateInterval($time));
            $time = $dt->getTimestamp();
            unset($dt);
        }
        elseif (is_object($time))
        {
            //We do hope for the best!!
            $time = $time->getTimestamp();
        }
        return $time;
    }


    public static function serialize($data)
    {
        return serialize($data);
    }

    public static function deserialize(string $data)
    {
        static $false = null;
        if($false === null)
            $false = serialize(false);
        if($data === $false)
        {
            return false;
        }

        $result = unserialize($data);
        if($result === false)
        {
            throw new \Exception('Cannot deserialize data!');
        }

        return $result;
    }

    public static function getRandomBytes($length = 64)
    {
        if(function_exists('random_bytes'))
        {
            return \random_bytes(intval($length));
        }

        $backup = '';

        if(function_exists('openssl_random_pseudo_bytes')
            && strtolower(substr(PHP_OS, 0, 3)) !== "win")
        {
            $strong = null;
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes)
            {
                if ($strong && strlen($bytes) >= $length)
                {
                    return substr($bytes, 0, $length);
                }
                $backup = $bytes;
            }
        }

        if(file_exists(('/dev/urandom')))
        {
            $urandom = @fopen('/dev/urandom', 'rb');
            if($urandom)
            {
                $result = '';
                while(strlen($result) < $length)
                {
                    $temp = @fread($urandom, $length - strlen($result));
                    if($temp === '')
                    {
                        break;
                    }
                    $result .= $temp;
                }
                @fclose($urandom);

                if(strlen($result) >= $length)
                {
                    if(strlen($result) > $length)
                    {
                        $result = substr($result, 0, $length);
                    }
                    return $result;
                }
                else
                {
                    //At least we can improve the previous result...
                    $backup = $result . $backup;
                }
            }
        }

        if(class_exists(\Bitrix\Main\Security\Random::class, true)
            && method_exists(\Bitrix\Main\Security\Random::class, 'getBytes'))
        {
            return \Bitrix\Main\Security\Random::getBytes(intval($length));
        }

        while(strlen($backup) < $length)
        {
            $backup .= hash('sha512', uniqid(rand(), true), true);
        }

        return substr($backup, 0, $length);
    }

    public static function base64_urlencode(string $val)
    {
        $v = base64_encode($val);

//        if(!preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $v))
//        {
//            return false;
//        }

//        if((strlen($v) & 3) && substr($v, -1) == '=')
//        {
//            return false;
//        }

        $v = rtrim($v, '=');
        $v = str_replace(['+', '/'], ['-', '_'], $v);

        return $v;
    }

    public static function base64_urldecode(string $val)
    {
        if(!preg_match('#^[A-Za-z0-9_-]+$#', $val))
        {
            return false;
        }

        $v = str_replace(['-', '_'], ['+', '/'], $val);
        $rem = strlen($v) & 3;

        if($rem == 2)
        {
            $v .= '==';
        }
        else if($rem == 3)
        {
            $v .= '=';
        }
        else if($rem != 0)
        {
            return false;
        }

        return base64_decode($v, true);
    }
}