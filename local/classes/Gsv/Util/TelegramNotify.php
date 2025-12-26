<?php

namespace Gsv\Util;

class TelegramNotify
{
    protected TelegramBot $telegram;
    protected Http\HttpClient $httpClient;

    public function __construct(TelegramBot $telegram, ?Http\HttpClient $httpClient = null)
    {
        $this->telegram = $telegram;
        $this->httpClient = $httpClient ?? new Http\HttpClient(false, true);
    }

    public function sendMessage($chat_id, $message, $isHtml = false)
    {
        $arMessage = [
            'chat_id' => $chat_id,
            'text' => $message,
        ];

        if($isHtml) {
            $arMessage['parse_mode'] = 'HTML';
        }

        $ret = $this->sendRequest('sendMessage', $arMessage);

        return $ret;
    }

    public function getRequestUrl($method)
    {
        return "https://api.telegram.org/bot{$this->telegram->getToken()}/{$method}";
    }

    public function sendRequest($method, $data)
    {
        $url = $this->getRequestUrl($method);
        $response = $this->httpClient->post($url, $data);

        if($response && $response['code'] == 200) {
            $response = $response['data'];
            if(is_string($response)) {
                $temp = json_decode($response, true);
                if($temp) {
                    $response = $temp;
                }
            }
            return $response;
        }

        return false;
    }
}