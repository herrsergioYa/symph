<?php

namespace Gsv\Sms;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;

use Bitrix\MessageService\Sender\Result\MessageStatus;
use Bitrix\MessageService\Sender\Result\SendMessage;

use Bitrix\MessageService;

class Mock extends \Bitrix\MessageService\Sender\Base
{
    public static $logTarget = false;

    public static function isSupported()
    {
        return true;
    }

    public function getId()
    {
        return 'mocksms';
    }

    public function getName()
    {
        return "Mock Sms";
    }

    public function getShortName()
    {
        return 'mock sms';
    }

    public function isDemo()
    {
        return false;
    }

    public function canUse()
    {
        return true;
    }

    public function sendMessage(array $messageFields)
    {
        if (!$this->canUse()) {
            $result = new SendMessage();
            $result->addError(new Error(self::getMessage('MESSAGESERVICE_SENDER_SMS_SMSRU_CAN_USE_ERROR')));
            return $result;
        }

        $params = array(
            'to' => $messageFields['MESSAGE_TO'],
            'msg' => $messageFields['MESSAGE_BODY']
        );

        $result = new SendMessage();
        $result->setAccepted();

        if(static::$logTarget) {
            gsv_dump([
                'name' => "Mocking SMS service",
                'params' => $params,
                'messageFields' => $messageFields,
            ], static::$logTarget);
        } else {
            $file = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/sms/';
            if(!is_dir($file))
                mkdir($file, 0775, true);
            $file .= date('Y-m-d_H.i.s') . '.txt';

            while(file_exists($file))
            {
                $file = substr($file, 0, strlen($file) - 4);
                $file .= '_' . randString(3) . '.txt';
            }

            $str = "Mocking SMS service:\n";
            foreach ($params as $k => $v) {
                $str .= "$k: $v\n";
            }
            $str .= "messageFields=" . var_export($messageFields, true)  . "\n";

            file_put_contents($file, $str);
        }

        return $result;
    }

    public function getMessageStatus(array $messageFields)
    {
        return parent::getMessageStatus($messageFields);
    }

    public static function resolveStatus($serviceStatus)
    {
        $status = parent::resolveStatus($serviceStatus);

        return $status;
    }
    public function getFromList()
    {
        $from = array();

        $from[] = array(
            'id' => 'mock_id',
            'name' => 'mock_name'
        );

        return $from;
    }
}