<?php

namespace Gsv\Sms;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use \Bitrix\MessageService\Sender;
use Bitrix\MessageService\Sender\Result\MessageStatus;
use Bitrix\MessageService\Sender\Result\SendMessage;
use Bitrix\MessageService;

class SmsRu extends \Bitrix\MessageService\Sender\Base
{
    public static function isSupported()
    {
        return true;
    }

    public function getId()
    {
        return \Bitrix\MessageService\Sender\Sms\SmsRu::ID . "_gsv_custom";
    }

    public function getName()
    {
        return Loc::getMessage('MESSAGESERVICE_SENDER_SMS_SMSRU_NAME') . " (gsv_custom)";
    }

    public function getShortName()
    {
        return 'sms.ru' . '_gsv_custom';
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
        if (!$this->canUse())
        {
            $result = new SendMessage();
            $result->addError(new Error(Loc::getMessage('MESSAGESERVICE_SENDER_SMS_SMSRU_CAN_USE_ERROR')));
            return $result;
        }

        $params = array(
            'to' => $messageFields['MESSAGE_TO'],
            'text' => $this->prepareMessageBodyForSend($messageFields['MESSAGE_BODY']),
            //'embed_id' => $this->getOption('embed_id'),
            'api_id' => $this->getOption('api_id'),
        );

        if ($this->isDemo())
        {
            $params['to'] = $this->getOption('user_phone');
        }

        if ($messageFields['MESSAGE_FROM'])
        {
            $params['from'] = $messageFields['MESSAGE_FROM'];
        }

        $result = new SendMessage();
        $apiResult = $this->callExternalMethod('sms/send', $params);
        $result->setServiceRequest($apiResult->getHttpRequest());
        $result->setServiceResponse($apiResult->getHttpResponse());

        $resultData = $apiResult->getData();

        if (!$apiResult->isSuccess())
        {
            if ((int)$resultData['status_code'] == 206)
            {
                $result->setStatus(MessageService\MessageStatus::DEFERRED);
                $result->addError(new Error($this->getErrorMessage($resultData['status_code'])));
            }
            else
            {
                $result->addErrors($apiResult->getErrors());
            }
        }
        else
        {
            $smsData = current($resultData['sms']);

            if (isset($smsData['sms_id']))
            {
                $result->setExternalId($smsData['sms_id']);
            }

            if ((int)$smsData['status_code'] !== 100)
            {
                $result->addError(new Error($this->getErrorMessage($smsData['status_code'])));
            }
            elseif ((int)$smsData['status_code'] == 206)
            {
                $result->setStatus(MessageService\MessageStatus::DEFERRED);
                $result->addError(new Error($this->getErrorMessage($smsData['status_code'])));
            }
            else
            {
                $result->setAccepted();
            }
        }

        return $result;
    }

    public function getMessageStatus(array $messageFields)
    {
        $result = new MessageStatus();
        $result->setId($messageFields['ID']);
        $result->setExternalId($messageFields['EXTERNAL_ID']);

        if (!$this->canUse())
        {
            $result->addError(new Error(Loc::getMessage('MESSAGESERVICE_SENDER_SMS_SMSRU_CAN_USE_ERROR')));
            return $result;
        }

        $params = array(
            'sms_id' => $result->getExternalId(),
            //'embed_id' => $this->getOption('embed_id')
            'api_id' => $this->getOption('api_id')
        );

        $apiResult = $this->callExternalMethod('sms/status', $params);
        if (!$apiResult->isSuccess())
        {
            $result->addErrors($apiResult->getErrors());
        }
        else
        {
            $resultData = $apiResult->getData();
            $smsData = current($resultData['sms']);

            $result->setStatusCode($smsData['status_code']);
            $result->setStatusText($smsData['status_text']);

            if ((int)$resultData['status_code'] !== 100)
            {
                $result->addError(new Error($this->getErrorMessage($smsData['status_code'])));
            }
        }

        return $result;
    }

    public static function resolveStatus($serviceStatus)
    {
        $status = parent::resolveStatus($serviceStatus);

        switch ((int)$serviceStatus)
        {
            case 100:
                return MessageService\MessageStatus::ACCEPTED;
                break;
            case 101:
                return MessageService\MessageStatus::SENDING;
                break;
            case 102:
                return MessageService\MessageStatus::SENT;
                break;
            case 103:
                return MessageService\MessageStatus::DELIVERED;
                break;
            case 104: //timeout
            case 105: //removed by moderator
            case 106: //error on receiver`s side
            case 107: //unknown reason
            case 108: //rejected
                return MessageService\MessageStatus::UNDELIVERED;
                break;
            case 110:
                return MessageService\MessageStatus::READ;
                break;
        }

        return $status;
    }

    public function sync()
    {
        if ($this->isRegistered())
        {
            $this->loadFromList();
        }
        return $this;
    }

    private function callExternalMethod($method, $params): Sender\Result\HttpRequestResult
    {
        $url = 'https://sms.ru/'.$method;

        $httpClient = new HttpClient(array(
            "socketTimeout" => $this->socketTimeout,
            "streamTimeout" => $this->streamTimeout,
            "waitResponse" => true,
        ));
        $httpClient->setHeader('User-Agent', 'Bitrix24');
        $httpClient->setCharset('UTF-8');

        $params['json'] = 1;

        $result = new Sender\Result\HttpRequestResult();
        $answer = array();

        $result->setHttpRequest(new MessageService\DTO\Request([
            'method' => HttpClient::HTTP_POST,
            'uri' => $url,
            'headers' => method_exists($httpClient, 'getRequestHeaders') ? $httpClient->getRequestHeaders()->toArray() : [],
            'body' => $params,
        ]));
        if ($httpClient->query(HttpClient::HTTP_POST, $url, $params) && $httpClient->getStatus() == '200')
        {
            $answer = $this->parseExternalAnswer($httpClient->getResult());
        }

        $answerCode = isset($answer['status_code']) ? (int)$answer['status_code'] : 0;

        if ($answerCode !== 100)
        {
            $result->addError(new Error($this->getErrorMessage($answerCode, $answer)));
        }
        $result->setData($answer);
        $result->setHttpResponse(new MessageService\DTO\Response([
            'statusCode' => $httpClient->getStatus(),
            'headers' => $httpClient->getHeaders()->toArray(),
            'body' => $httpClient->getResult(),
            'error' => Sender\Util::getHttpClientErrorString($httpClient)
        ]));

        return $result;
    }

    private function parseExternalAnswer($httpResult)
    {
        try
        {
            $answer = Json::decode($httpResult);
        }
        catch (\Bitrix\Main\ArgumentException $e)
        {
            $data = explode(PHP_EOL, $httpResult);
            $code = (int)array_shift($data);
            $answer = $data;
            $answer['status_code'] = $code;
            $answer['status'] = $code === 100 ? 'OK' : 'ERROR';
        }

        if (!is_array($answer) && is_numeric($answer))
        {
            $answer = array(
                'status' => $answer === 100 ? 'OK' : 'ERROR',
                'status_code' => $answer
            );
        }

        return $answer;
    }

    private function getErrorMessage($errorCode, $answer = null)
    {
        $message = Loc::getMessage('MESSAGESERVICE_SENDER_SMS_SMSRU_ERROR_'.$errorCode);
        if (!$message && $answer && !empty($answer['errors']))
        {
            $errorCode = $answer['errors'][0]['status_code'];
            $message = Loc::getMessage('MESSAGESERVICE_SENDER_SMS_SMSRU_ERROR_'.$errorCode);
            if (!$message)
            {
                $message = $answer['errors'][0]['status_text'];
            }
        }

        return $message ?: Loc::getMessage('MESSAGESERVICE_SENDER_SMS_SMSRU_ERROR_OTHER');
    }

    public function getFromList()
    {
        $from = array();

        $from[] = array(
            'id' => $this->getId() . '_default',
            'name' => $this->getOption('api_default_from') ?: $this->getShortName(),
        );

        return $from;
    }

    public function getOption($name)
    {
        return Option::get('gsv.sms', $this->getId() . "." . $name, '');
    }
}