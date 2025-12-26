<?php

namespace Gsv\Util;

class YaDisk
{
    const YA_DISK_URL = 'https://cloud-api.yandex.net:443/v1/disk/public/resources';

    /**
     * @var string $publicUrl
     */
    private $publicUrl = '';

    /**
     * @var \Gsv\Util\Http\IHttpClient $obHttp
     */
    private $obHttp;

    public function __construct($publicUrl, \Gsv\Util\Http\IHttpClient $obHttp = null)
    {
        $this->setPublicUrl($publicUrl);
        $this->obHttp = $obHttp;
    }

    public function execYaDisk($method, $params)
    {
        $obHttp = $this->getHttp();

        $url = static::YA_DISK_URL;
        $method = trim($method, '/');
        if($method != '') {
            $url .= '/' . $method;
        }
        $url .= '?' . http_build_query($params);

        $response = $obHttp->get($url);

        if($response['code'] != 200) {
            throw new \Exception("YaDisk returned ${response['code']}", $response['code']);
        }

        return $response['data'];
    }

    public function listYaDisk($path)
    {
        $params = [
            'public_key' => $this->getPublicUrl(),
            'path' => $path,
            'limit' => 50,
            'offset' => 0,
        ];
        $arResult = [];
        while(true) {
            $data = $this->execYaDisk('', $params);
            $data = $data['_embedded'];
            foreach ($data['items'] as $item) {
                $arResult[$item['name']] = $item;
            }
            if(count($arResult) >= $data['total']) {
                break;
            }
            $params['offset'] = $data['offset'] + count($data['items']);
            $params['limit'] = $data['limit'];
        }
        return $arResult;
    }

    public function getHttp()
    {
        if($this->obHttp === null) {
            $this->obHttp = new \Gsv\Util\Http\HttpClient(true, true);
        }
        return $this->obHttp;
    }

    /**
     * @return string
     */
    public function getPublicUrl()
    {
        return $this->publicUrl;
    }

    /**
     * @param string $publicUrl
     * @return void
     */
    public function setPublicUrl($publicUrl)
    {
        $this->publicUrl = $publicUrl;
    }

    /*public function getFile($url)
    {
        $result = $this->getHttp()->get($url);

        if($result['code'] != 200) {
            throw new \Exception("YaDisk returned ${$result['code']}", $result['code']);
        }

        return $result['data'];
    }*/
}