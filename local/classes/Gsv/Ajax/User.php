<?php

namespace Gsv\Ajax;

use \Bitrix\Main;
use \Bitrix\Main\Authentication;
use \Bitrix\Main\Authentication\ShortCode;
use \Gsv\Ajax\ShortCode as RegisterCode;
use \Bitrix\Main\Localization\Loc;

class User extends \CUser
{
    const PHONE_CODE_OTP_INTERVAL = self::PHONE_CODE_RESEND_INTERVAL;

    const CODE_LENGTH = 6;

    /**
     * @param int $userId
     * @return array|bool [code, phone_number]
     */
    public static function GeneratePhoneCode($userId)
    {
        $row = Main\UserPhoneAuthTable::getRowById($userId);
        if ($row && $row["OTP_SECRET"] != '')
        {
            $totp = new Main\Security\Mfa\TotpAlgorithm();
            //$totp->setInterval(self::PHONE_CODE_OTP_INTERVAL);
            $totp->setInterval(static::PHONE_CODE_OTP_INTERVAL);
            $totp->setSecret($row["OTP_SECRET"]);

            $timecode = $totp->timecode(time());
            $code = $totp->generateOTP($timecode);

            Main\UserPhoneAuthTable::update($userId, [
                "ATTEMPTS" => 0,
                "DATE_SENT" => new Main\Type\DateTime(),
            ]);

            $code = static::trimCode($code);

            return [$code, $row["PHONE_NUMBER"]];
        }
        return false;
    }

    /**
     * @param string $phoneNumber
     * @param string $code
     * @return bool|int User ID on success, false on error
     */
    public static function VerifyPhoneCode($phoneNumber, $code)
    {
        if ($code == '')
        {
            return false;
        }

        $code = static::restoreCode($code);

        $phoneNumber = Main\UserPhoneAuthTable::normalizePhoneNumber($phoneNumber);

        $row = Main\UserPhoneAuthTable::getList(["filter" => ["=PHONE_NUMBER" => $phoneNumber]])->fetch();
        if ($row && $row["OTP_SECRET"] != '')
        {
            if ($row["ATTEMPTS"] >= 3)
            {
                return false;
            }

            $totp = new Main\Security\Mfa\TotpAlgorithm();
            //$totp->setInterval(self::PHONE_CODE_OTP_INTERVAL);
            $totp->setInterval(static::PHONE_CODE_OTP_INTERVAL);
            $totp->setSecret($row["OTP_SECRET"]);

            try
            {
                [$result,] = $totp->verify($code);
            }
            catch (Main\ArgumentException)
            {
                return false;
            }

            $data = [];
            if ($result)
            {
                if ($row["CONFIRMED"] == 'N')
                {
                    $data["CONFIRMED"] = 'Y';
                }

                $data['DATE_SENT'] = '';
            }
            else
            {
                $data["ATTEMPTS"] = (int)$row["ATTEMPTS"] + 1;
            }

            if (!empty($data))
            {
                Main\UserPhoneAuthTable::update($row["USER_ID"], $data);
            }

            if ($result)
            {
                return $row["USER_ID"];
            }
        }
        return false;
    }

    protected static function trimCode($code)
    {
        $salt = '';
        $_SESSION['GSV_PHONE_OTP'] = [];
        $_SESSION['GSV_PHONE_OTP']['LENGTH'] = strlen($code);
        if($_SESSION['GSV_PHONE_OTP']['LENGTH'] > static::CODE_LENGTH) {
            $salt = substr($code, static::CODE_LENGTH);
            $code = substr($code, 0, static::CODE_LENGTH);
        } elseif($_SESSION['GSV_PHONE_OTP']['LENGTH'] < static::CODE_LENGTH) {
            $salt = randString(static::CODE_LENGTH - $code);
            $code .= $salt;
        }
        $_SESSION['GSV_PHONE_OTP']['SALT'] = $salt;
        return $code;
    }

    protected static function restoreCode($code)
    {
        $salt = (string)$_SESSION['GSV_PHONE_OTP']['SALT'];
        $length = (string)$_SESSION['GSV_PHONE_OTP']['LENGTH'];
        if(strlen($code) > $length) {
            $check = substr($code, $length);
            if($check != $salt )
            {
                return false;
            }
            $code = substr($code, 0, $length);
        } elseif(strlen($code) < $length) {
            $code .= $salt;
        }
        return $code;
    }

    public static function SendEmailCode($userId, $siteId)
    {
        return parent::SendEmailCode($userId, $siteId);
    }

    public static function SendUserInfo($ID, $SITE_ID, $MSG, $bImmediate = false, $eventName = "USER_INFO", $checkword = null)
    {
        if($eventName == 'USER_CODE_REQUEST') {
            $checkword = static::trimCode($checkword);
        }
        parent::SendUserInfo($ID, $SITE_ID, $MSG, $bImmediate, $eventName, $checkword);
    }


    public static function VerifyEmailCode($userId, $code)
    {
        //$result = new Main\Result();

        $context = new Authentication\Context();
        $context->setUserId($userId);

        $shortCode = new ShortCode($context);

        $code = static::restoreCode($code);

        $result = $shortCode->verify($code);

        if ($result->isSuccess())
        {
            $result->setData(['USER_ID' => $userId]);
        }

        return $result;
    }

    public static function SendRegisterCode($type, $login, $siteId)
    {
        $result = new Main\Result();

        //$context = new Authentication\Context();
        //$context->setUserId($userId);

        $shortCode = new RegisterCode(
            $login,
            $type,
        );

        //alowed only once in a minute
        $check = $shortCode->checkDateSent();

        if ($check->isSuccess())
        {
            $code = $shortCode->generate();

            $code = static::trimCode($code);

            if($type == RegisterCode::TYPE_SMS)
            {
                $arInfo = [
                    'PHONE' => $login,
                    'CODE' => $code,
                ];

                static::SendRegisterSms($arInfo, $siteId, true);
            }
            else if($type == RegisterCode::TYPE_EMAIL)
            {
                $arInfo = [
                    'EMAIL' => $login,
                    'CODE' => $code,
                ];

                static::SendRegisterEmail($arInfo, $siteId, true);
            }

            $shortCode->saveDateSent();
        }
        else
        {
            $result->addError(new Main\Error(Loc::getMessage("main_register_timeout"), "ERR_TIMEOUT"));
        }

        $result->setData($check->getData());

        return $result;
    }

    public static function SendRegisterEmail($arInfo, $SITE_ID, $bImmediate = false, $eventName = "USER_REGISTER_CODE")
    {
        $event = new \CEvent;
        $arFields = [
            "EMAIL" => $arInfo['EMAIL'],
            'CODE' => $arInfo['CODE'],
        ];

        $arParams = [
            "FIELDS" => &$arFields,
            "SITE_ID" => &$SITE_ID,
            "EVENT_NAME" => &$eventName,
        ];

        foreach (GetModuleEvents('main', 'OnSendRegisterEmail', true) as $arEvent)
        {
            ExecuteModuleEventEx($arEvent, [&$arParams]);
        }

        if (!$bImmediate)
        {
            $event->Send($eventName, $SITE_ID, $arFields, 'Y', '', [], LANGUAGE_ID);
        }
        else
        {
            $event->SendImmediate($eventName, $SITE_ID, $arFields, 'Y', '', [], LANGUAGE_ID);
        }
    }

    public static function SendRegisterSms($arInfo, $SITE_ID, $bImmediate = false, $eventName = "USER_REGISTER_SMS")
    {
        $arFields = [
            "PHONE" => $arInfo['PHONE'],
            'CODE' => $arInfo['CODE'],
        ];

        $arParams = [
            "FIELDS" => &$arFields,
            "SITE_ID" => &$SITE_ID,
            "EVENT_NAME" => &$eventName,
        ];

        foreach (GetModuleEvents('main', 'OnSendRegisterSms', true) as $arEvent)
        {
            ExecuteModuleEventEx($arEvent, [&$arParams]);
        }

        $sms = new \Bitrix\Main\Sms\Event(
            $eventName,
            $arFields
        );
        $sms->setSite($SITE_ID);
        return $sms->send($bImmediate);
    }

    public static function VerifyRegisterCode($type, $login, $code)
    {
        $result = new Main\Result();

        //$context = new Authentication\Context();
        //$context->setUserId($userId);

        $shortCode = new RegisterCode(
            $login,
            $type,
        );

        $code = static::restoreCode($code);

        $result = $shortCode->verify($code);

        if ($result->isSuccess())
        {
            $result->setData([
                'EMAIL' => $login,
                'CODE' => $code,
            ]);
        }

        return $result;
    }
}