<?php


namespace Gsv\Bitrix\Http;


use Bitrix\Main\Web\Uri;

if(RawBitrixHttpClient::isPsrCompatible())
{
    //А здесь ничего мы не устраним...
    class RestClient extends \Bitrix\Main\Web\HttpClient
    {
        public function __construct($options = [])
        {
            parent::__construct($options);
        }
    }
}
else
{
    //Здесь устраняем баг из старой версии
    class RestClient extends \Bitrix\Main\Web\HttpClient
    {
        public function __construct($options = [])
        {
            parent::__construct($options);
        }

        //WTF? Why we should correct this? Who has developed this class!?
        protected function sendRequest($method, Uri $url, $entityBody = null)
        {
            static $arHeadersToPreserve = [
                "Proxy-Authorization", "Authorization", "Accept-Encoding", "Cookie",
                "Content-Length", 'Content-Type'
            ];

            $arBackup = [];
            foreach ($arHeadersToPreserve as $header) {
                $arBackup[$header] = $this->requestHeaders->get($header, true);
            }

            $return = parent::sendRequest($method, $url, $entityBody);

            foreach ($arHeadersToPreserve as $header) {
                $this->requestHeaders->delete($header);
                if (is_array($arBackup[$header]))
                    foreach ($arBackup[$header] as $value)
                        $this->requestHeaders->add($header, $value);
            }

            return $return;
        }
    }
}