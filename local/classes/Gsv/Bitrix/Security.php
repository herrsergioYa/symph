<?php


namespace Gsv\Bitrix;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\SecurityException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;

class Security extends \Gsv\Util\Security
{
    /**
     *
     */
    const SECURITY_OPT = 'gsv.security';

    public static function encrypt(string $message, string $key = '', bool $binary = false) : string
    {
        $cipher = new \Bitrix\Main\Security\Cipher;
        if(strlen($key) > 0)
            $return = $cipher->encrypt($message, $key);
        else
            $return = $cipher->encrypt($message, static::getCipherDefaultKey());
        if($return !== false && !$binary)
            $return = base64_encode($return);
        return $return;
    }

    public static function decrypt(string $message, string $key = '', bool $binary = false)
    {
        if(!$binary) {
            $data = base64_decode($message, true);
            if ($data === false) {
                return false;
            }
        } else {
            $data = $message;
        }

        $cipher = new \Bitrix\Main\Security\Cipher;
        try
        {
            if (strlen($key) > 0)
                return (string)$cipher->decrypt($data, $key);
            else
                return (string)$cipher->decrypt($data, static::getCipherDefaultKey());
        }
        catch(SecurityException $ex)
        {
            return false;
        }
    }

    public static function sign(string $message, string $salt = '', string $key = ''): string
    {
        $signer = self::createSigner($key);
        if(strlen($salt) > 0)
            return $signer->sign($message, $salt);
        else
            return $signer->sign($message);
    }

    public static function unsign(string $message, string $salt = '', string $key = ''): string | false
    {
        $signer = self::createSigner($key);
        try
        {
            if (strlen($salt) > 0)
                return (string)$signer->unsign($message, $salt);
            else
                return (string)$signer->unsign($message);
        }
        catch(BadSignatureException $ex)
        {
            return false;
        }
    }

    public static function signTill(string $message, $time, string $salt = '', string $key = ''): string
    {
        $time = self::getTimeStamp($time);

        $signer = self::createTimeSigner($key);
        if(strlen($salt) > 0)
            return $signer->sign($message, $time, $salt);
        else
            return $signer->sign($message, $time);
    }

    public static function unsignBefore(string $message, string $salt = '', string $key = '')
    {
        $signer = self::createTimeSigner($key);
        try
        {
            if (strlen($salt) > 0)
                return (string)$signer->unsign($message, $salt);
            else
                return (string)$signer->unsign($message);
        }
        catch(BadSignatureException $ex)
        {
            return false;
        }
    }

    /*public static function getSignerDefaultKey()
    {
        //__InnerSigner::getOptKey();
        static $defaultKey = null;
        if ($defaultKey === null)
        {
            $defaultKey = Option::get('main', 'signer_default_key', false);
            if (!$defaultKey)
            {
                $defaultKey = hash('sha512', uniqid(rand(), true));// Not cryptographically safe!! BUT Bitrix uses it :-(
                Option::set('main', 'signer_default_key', $defaultKey, '');
            }
        }

        return $defaultKey;
    }*/

    public static function getCipherDefaultKey()
    {
        static $defaultKey = null;
        if ($defaultKey === null)
        {
            $defaultKey = \base64_decode(Option::get(static::SECURITY_OPT, 'gsv_cipher_default_key', ''), true);
            if ($defaultKey === false || strlen($defaultKey) <= 0)
            {
                //$defaultKey = \random_bytes(48);
                $defaultKey = static::getRandomBytes(48);
                Option::set(static::SECURITY_OPT, 'gsv_cipher_default_key', \base64_encode($defaultKey), '');
            }
        }

        return $defaultKey;
    }

    protected static function createSigner(string $key = ''): \Bitrix\Main\Security\Sign\Signer
    {
        $signer = new \Bitrix\Main\Security\Sign\Signer;
        if (strlen($key) > 0)
            $signer->setKey($key);
        //else
        //    $signer->setKey(static::getSignerDefaultKey());
        return $signer;
    }

    protected static function createTimeSigner(string $key = ''): \Bitrix\Main\Security\Sign\TimeSigner
    {
        $signer = new \Bitrix\Main\Security\Sign\TimeSigner;
        if (strlen($key) > 0)
            $signer->setKey($key);
        //else
        //    $signer->setKey(static::getSignerDefaultKey());
        return $signer;
    }

    /**
     * @param $time
     * @return mixed
     * @throws \Exception
     */
    protected static function getTimeStamp($time)
    {
        if ($time instanceof DateTime) //Superfluous?
        {
            $time = $time->getTimestamp();
        }
        else if ($time instanceof Date)
        {
            $time = $time->getTimestamp();
        }
        else
        {
            $time = parent::getTimeStamp($time);
        }
        return $time;
    }

    public static function getRandomBytes($length = 64)
    {
        if(class_exists(\Bitrix\Main\Security\Random::class, true)
            && method_exists(\Bitrix\Main\Security\Random::class, 'getBytes'))
        {
            return \Bitrix\Main\Security\Random::getBytes(intval($length));
        }

        return parent::getRandomBytes($length);
    }
}