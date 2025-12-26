<?php

namespace Gsv\Ajax;

class AuthRegAjax extends \Gsv\Bitrix\JsonAjaxController
{
    public function sendSmsCodeAction($arParams)
    {
        $phone = $arParams['phone'];
        $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);
        $register = static::isRegistration($arParams);

        $arUser = static::getUserByPhone($phone);
        if($register) {

            $result = [
                'status' => false,
                'message' => 'INTERNAL_ERROR',
                'data' => [],
            ];

            if($arUser) {
                $result['message'] = 'USER_ALREADY_EXISTS';
                return $result;
            }

            $res = User::SendRegisterCode(ShortCode::TYPE_SMS, $phone, SITE_ID);
            if($res->isSuccess()) {
                $result['status'] = true;
                $result['message'] = '';
            } else {
                $result['message'] = implode(', ', $res->getErrorMessages());
            }

            return $result;
        }

        if(!$arUser) {
            $result = [
                'status' => false,
                'message' => 'USER_NOT_FOUND',
                'data' => [],
            ];
            return $result;
        }

        $res = User::SendPhoneCode($phone, 'SMS_USER_CONFIRM_NUMBER', SITE_ID);

        $result = [
            'status' => !!$res->isSuccess(),
            'message' => implode(', ', $res->getErrorMessages()),
            'data' => $res->getData(),
        ];

        return $result;
    }

    public function verifySmsCodeAction($arParams)
    {
        $phone = $arParams['phone'];
        $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);
        $code = $arParams['code'];
        $code = trim($code);
        $register = static::isRegistration($arParams);

        $result = [
            'status' => false,
            'message' => 'WRONG_CODE',
            'data' => [],
        ];
        if($register) {
            $arUser = static::getUserByPhone($phone);
            static::clearAuthorizedLogin();
            if($arUser) {
                $result['message'] = 'USER_ALREADY_EXISTS';
                return $result;
            }
            $res = User::VerifyRegisterCode(ShortCode::TYPE_SMS, $phone, $code);
            if($res->isSuccess()) {
                $result['status'] = true;
                $result['message'] = '';
                static::setAuthorizedLogin(
                    ShortCode::TYPE_SMS,
                    $phone
                );
                $result['data'] = static::getAuthorizedLogin();
            } else {
                //$result['message'] = implode(', ', $res->getErrorMessages());
            }
            return $result;
        }
        $userId = User::VerifyPhoneCode($phone, $code);
        if($userId && $userId > 0) {
            global $USER;
            $res = $USER->Authorize($userId);
            $result['status'] = !!$res;
            $result['message'] = 'INTERNAL_ERROR';
        }

        return $result;
    }


    public function sendEmailCodeAction($arParams)
    {
        $email = $arParams['email'];
        $email = trim($email);
        $register = static::isRegistration($arParams);

        $result = [
            'status' => false,
            'message' => 'USER_NOT_FOUND',
            'data' => [],
        ];

        $arUser = static::getUserByEmail($email);

        if($register) {
            if($arUser) {
                $result['message'] = 'USER_ALREADY_EXISTS';
                return $result;
            }

            $res = User::SendRegisterCode(ShortCode::TYPE_EMAIL, $email, SITE_ID);
            if($res->isSuccess()) {
                $result['status'] = true;
                $result['message'] = '';
            } else {
                $result['message'] = implode(', ', $res->getErrorMessages());
            }

            return $result;
        }

        if($arUser) {
            $res = User::SendEmailCode($arUser['ID'], SITE_ID);
            if($res->isSuccess()) {
                $result['status'] = true;
                $result['message'] = '';
            } else {
                $result['message'] = implode(', ', $res->getErrorMessages());
            }
        }

        return $result;
    }

    public function verifyEmailCodeAction($arParams)
    {
        $email = $arParams['email'];
        $email = trim($email);
        $code = $arParams['code'];
        $code = trim($code);
        $register = static::isRegistration($arParams);

        $result = [
            'status' => false,
            'message' => 'USER_NOT_FOUND',
            'data' => [],
        ];

        $arUser = static::getUserByEmail($email);
        if($register) {
            static::clearAuthorizedLogin();
            if($arUser) {
                $result['message'] = 'USER_ALREADY_EXISTS';
                return $result;
            }
            $res = User::VerifyRegisterCode(ShortCode::TYPE_EMAIL, $email, $code);
            if($res->isSuccess()) {
                $result['status'] = true;
                $result['message'] = '';
                static::setAuthorizedLogin(
                    ShortCode::TYPE_EMAIL,
                    $email
                );
                $result['data'] = static::getAuthorizedLogin();
            } else {
                //$result['message'] = implode(', ', $res->getErrorMessages());
                $result['message'] = 'WRONG_CODE';
            }
            return $result;
        }
        if($arUser) {
            $res = User::VerifyEmailCode($arUser['ID'], $code);
            if($res->isSuccess()) {
                $result['status'] = true;
                $result['message'] = '';

                $userId = $arUser['ID'];
                global $USER;
                $res = $USER->Authorize($userId);
                if(empty($res)) {
                    $result['status'] = false;
                    $result['message'] = 'INTERNAL_ERROR';
                }
            } else {
                //$result['message'] = implode(', ', $res->getErrorMessages());
                $result['message'] = 'WRONG_CODE';
            }
        }

        return $result;
    }

    public function registerUserAction($arParams)
    {
        $type = $arParams['type'];
        $type = trim($type);

        $email = $arParams['email'];
        $email = trim($email);
        $phone = $arParams['phone'];
        $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);

        $result = [
            'status' => false,
            'message' => 'INTERNAL_ERROR',
            'data' => [],
        ];

        if($type == ShortCode::TYPE_SMS) {
            $login = $phone;
            $arUser = static::getUserByPhone($phone);;
        } else if($type == ShortCode::TYPE_EMAIL) {
            $login = $email;
            $arUser = static::getUserByEmail($email);
        } else {
            $login = '';
            $arUser = false;
        }

        if($arUser) {
            $result['message'] = 'USER_ALREADY_EXISTS';
            return $result;
        }

        if(!$this->isRegistrationApproved($type, $login)) {
            $result['message'] = 'NOT_VERIFIED';
            return $result;
        }

        $password = randString(42);

        $USER = new \CUser();
        $arRes = $USER->Register(
            $phone ?: $email,
            $arParams['name'],
            $arParams['last_name'],
            $password,
            $password,
            $email,
            SITE_ID,
            '',
            0,
            false,
            $phone,
        );
        if ($arRes["TYPE"] == "OK")
        {
            $ID = intval($arRes['ID']);
            $sBirthday = $arParams['birthday'];
            if($sBirthday) {
                $obBirthday = new \Bitrix\Main\Type\Date($sBirthday, 'Y-m-d');
                $sBirthday = \ConvertTimeStamp($obBirthday->getTimestamp(), 'SHORT');
            } else {
                $sBirthday = false;
            }
            $bRes = $USER->Update($ID, [
                'PERSONAL_BIRTHDAY' => $sBirthday,
                'PERSONAL_PHONE' => $phone,
            ]);
            if($bRes) {
                $result['status'] = true;
                $result['message'] = '';
            } else if($USER->LAST_ERROR) {
                $result['message'] = $USER->LAST_ERROR;
            }
        }
        else if($arRes["MESSAGE"])
        {
            $result['message'] = $arRes["MESSAGE"];
        }

        return $result;
    }

    protected function isRegistrationApproved($type, $login)
    {
        return $_SESSION['GSV_AUTH_LOGIN']
            && $_SESSION['GSV_AUTH_LOGIN']['TYPE'] == $type
            && $_SESSION['GSV_AUTH_LOGIN']['LOGIN'] == $login;
    }

    protected function isRegistration($arParams)
    {
        return $arParams['register'] === true
            || $arParams['register'] === 'true'
            || $arParams['register'] === '1';

    }

    public static function getUserByEmail($email)
    {
        $arUser = \Bitrix\Main\UserTable::getRow([
            'filter' => [
                'EMAIL' => $email,
            ],
            'select' => ['ID'],
        ]);

        return $arUser ?: false;
    }

    public static function getUserByPhone($phone)
    {
        if(empty($phone)) {
            return false;
        }

        $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);

        if(empty($phone)) {
            return false;
        }

        $arPhoneFilter = [
            $phone,
            preg_replace('/\D+/', '', $phone),
        ];

        $arUser = \Bitrix\Main\UserTable::getRow([
            'filter' => [
                '=PERSONAL_PHONE' => $arPhoneFilter,
                '=PHONE_AUTH.PHONE_NUMBER' => $arPhoneFilter,
            ],
            'select' => [
                'ID',
                'PERSONAL_PHONE',
                'PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
            ],
        ]);

        if($arUser) {
            //HACK: We should 'fix' the user if needed
            $phone = $arUser['PERSONAL_PHONE'] ?: '';
            $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);
            if(empty($arUser['PHONE_NUMBER'])
                /* || $phone && $arUser['PHONE_NUMBER'] != $phone*/) {
                $obUser = new \CUser();
                $obUser->Update(
                    $arUser['ID'],
                    [
                        'PHONE_NUMBER' => $phone,
                    ],
                );
            }
            unset($arUser['PHONE_NUMBER'], $arUser['PERSONAL_PHONE']);
        } else {
            $arUser = false;
        }

        return $arUser;
    }

    protected static function setAuthorizedLogin($type, $login)
    {
        $_SESSION['GSV_AUTH_LOGIN'] = [
            'TYPE' => $type,
            'LOGIN' => $login,
        ];
    }

    protected static function clearAuthorizedLogin()
    {
        unset($_SESSION['GSV_AUTH_LOGIN']);
    }

    protected static function getAuthorizedLogin()
    {
        return $_SESSION['GSV_AUTH_LOGIN'] ?: false;
    }
}