<?php

namespace Gsv\Bitrix;

class TelegramAuth extends \Gsv\Util\TelegramAuth
{

    const URL = '/local/auth/tg.php';

    public function __construct(?string $app = 'auth_bot')
    {
        parent::__construct(new TelegramBot($app));
    }

    public function authorizeUser($auth_data, $allowRegister, $authorize)
    {
        $arUser = \Bitrix\Main\UserTable::getRow([
            'filter' => [
                'UF_TELEGRAM_ID' => $auth_data['id'],
            ],
            'select' => ['ID']
        ]);
        if(empty($arUser)) {
            if(!$allowRegister) {
                return [
                    'ID' => false,
                    'ERROR' => 'NOT_FOUND',
                ];
            }
            $arUser = [
                'NAME' => $auth_data['first_name'] ?: '',
                'LAST_NAME' => $auth_data['last_name'] ?: '',
                'LOGIN' => $auth_data['username'] ?: 'tg' . $auth_data['id'],
                'EMAIL' => 'tg' . $auth_data['id'] . '@tg.me.stub',

                'PASSWORD' => \Bitrix\Main\Security\Random::getString(32, true),

                'UF_TELEGRAM_ID' => $auth_data['id'],
            ];
            while(true) {
                $arTemp = \Bitrix\Main\UserTable::getRow([
                    'filter' => [
                        'LOGIN' => $arUser['LOGIN'],
                    ],
                    'select' => ['ID']
                ]);
                if($arTemp) {
                    $arUser['LOGIN'] .= '_' . randString(3);
                } else {
                    break;
                }
            }
            while(true) {
                $arTemp = \Bitrix\Main\UserTable::getRow([
                    'filter' => [
                        'EMAIL' => $arUser['EMAIL'],
                    ],
                    'select' => ['ID']
                ]);
                if($arTemp) {
                    $arUser['EMAIL'] = str_replace('@', '_' . randString(3) .'@', $arUser['EMAIL']);
                } else {
                    break;
                }
            }
            if($auth_data['photo_url']) {
                $arUser['PERSONAL_PHOTO'] = \CFile::MakeFileArray($auth_data['photo_url']);
                //gsv_dump($arUser, false, false);
                //gsv_dump($auth_data, false, false);
            }
            $obUser = new \CUser();
            $arRes = $obUser->Register(
                $arUser['LOGIN'],
                $arUser['NAME'],
                $arUser['LAST_NAME'],
                $arUser['PASSWORD'],
                $arUser['PASSWORD'],
                $arUser['EMAIL']
            );
            unset (
                $arUser['LOGIN'],
                $arUser['NAME'],
                $arUser['LAST_NAME'],
                $arUser['PASSWORD'],
                $arUser['PASSWORD'],
                $arUser['EMAIL']
            );
            if($arRes && $arRes['ID'] && $arRes['TYPE'] == 'OK') {
                $ID = $arRes['ID'];
                $msg = $arRes['MESSAGE'];

                if($arUser) {
                    $bOk = $obUser->Update($ID, $arUser);
                    if(empty($bOk)) {
                        $ID = false;
                        $msg = $obUser->LAST_ERROR;
                    }
                }

            } else {
                $ID = false;
                $msg = ($arRes ? $arRes['MESSAGE'] : '') ?: 'Unknown registration error';
            }
            if(!$authorize) {
                global $USER;
                if($USER->IsAuthorized()) {
                    $USER->Logout();
                }
            }
            return [
                'ID' => $ID,
                'IS_REGISTERED' => true,
                'ERROR' => $msg,
            ];
        } else {
            if($authorize) {
                global $USER;
                $bOk = $USER->Authorize($arUser['ID']);
            } else {
                $bOk = true;
            }
            return [
                'ID' => $arUser['ID'],
                'IS_REGISTERED' => false,
                'ERROR' => $bOk ? '' : 'Unknown authorization error',
            ];
        }
    }

    public function bindUser($telegramId, $USER_ID = 0)
    {
        $telegramId = static::normalizeTelegram($telegramId);

        if(empty($USER_ID)) {
            global $USER;
            if (!$USER->IsAuthorized()) {
                return [
                    'OK' => false,
                    'ERROR' => 'UNAUTHORIZED',
                ];
            }
            $USER_ID = $USER->GetID();
        }

        $arRes = $this->unbindUser($telegramId, $USER_ID);

        if(!$arRes['OK']) {
            return $arRes;
        }

        if(!$arRes['FOUND']) {
            $bRes = $this->getCUser()->Update($USER_ID, ['UF_TELEGRAM_ID' => $telegramId]);
            if (empty($bRes)) {
                return [
                    'OK' => false,
                    'ERROR' => 'INTERNAL_ERROR',
                    'MESSAGE' => $this->getCUser()->LAST_ERROR,
                ];
            }
        }

        return [
            'OK' => true,
            'USER_ID' => $USER_ID,
        ];
    }

    public function unbindUser($telegramId, $except = 0)
    {
        $telegramId = static::normalizeTelegram($telegramId);

        $dbUsers = \Bitrix\Main\UserTable::getList([
            'filter' => [
                'UF_TELEGRAM_ID' => $telegramId,
            ],
            'select' => ['ID']
        ]);
        //Let's check them all!
        $bFound = false;
        while($arUser = $dbUsers->fetch()) {
            if ($except && $arUser['ID'] == $except) {
                $bFound = true;
            } else {
                $bRes = $this->getCUser()->Update($arUser['ID'], ['UF_TELEGRAM_ID' => false]);
                if(empty($bRes)) {
                    return [
                        'OK' => false,
                        'ERROR' => 'INTERNAL_ERROR',
                        'MESSAGE' => $this->getCUser()->LAST_ERROR,
                    ];
                }
            }
        }

        return [
            'OK' => true,
            'FOUND' => $bFound,
        ];
    }

    public static function getCUser()
    {
        /** @var \CUser $obUser */
        static $obUser = NULL;
        if($obUser === NULL) {
            $obUser = new \CUser();
        }
        return $obUser;
    }

    public static function normalizeTelegram($telegramId)
    {
        if(is_array($telegramId)) {
            if(is_numeric($telegramId['id'])) {
                $telegramId = $telegramId['id'];
            } else {
                return false;
            }
        } else if(!is_numeric($telegramId)) {
            return false;
        }

        return $telegramId;
    }

    public static function getRedirectUrl($type = null, $return_url = null)
    {
        global $APPLICATION;
        $sUrl = ($APPLICATION->IsHTTPS() ? 'https://' : 'http://')
            . $_SERVER['SERVER_NAME']
            . '/local/auth/tg.php';
        $arQuery = [];
        if(is_string($type)) {
            $arQuery['TYPE'] = $type;
        }
        if(is_string($return_url)) {
            $arQuery['RETURN_TO'] = $return_url;
        }
        if($arQuery) {
            $sUrl .= '?' . http_build_query($arQuery);
        }
        return $sUrl;
    }

    public function download($photo_url)
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $image_data = file_get_contents($photo_url, false, $context);

        if ($image_data !== false) {
            $file = \CTempFile::GetFileName();
            $dir = dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, $image_data);
        } else {
            return false;
        }
    }
}