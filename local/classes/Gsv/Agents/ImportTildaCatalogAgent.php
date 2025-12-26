<?php

namespace Gsv\Agents;



use Bitrix\Catalog\ProductTable;

class ImportTildaCatalogAgent extends \Gsv\Bitrix\Agent
{
    use \Gsv\Bitrix\ImportTraits\HelperTrait;

    public const DEBUG = false;
    public const URL = 'https://store.tildaapi.com/api/getproductslist/?storepartuid=998871465181&recid=323824198';
    public const FILE_HASH_COMPARE = false;

    //We DO initialize them at runtime!
    public const PRODUCT_IBLOCK_ID = CATALOG_IBLOCK_ID;
    public const OFFER_IBLOCK_ID = CATALOG_OFFERS_IBLOCK_ID;

    //public const IN_STOCK = 'in stock';
    public const IN_STOCK_QUANTITY = 100;

    public const TRANSLIT_LANG = 'ru'; //?
    public const TRANSLIT_PARAMS = array(
        "max_len" => 100,
        "change_case" => 'L', // 'L' - toLower, 'U' - toUpper, false - do not change
        "replace_space" => '-',
        "replace_other" => '-',
        "delete_repeat_replace" => true,
        "safe_chars" => '',
    );

    protected const PRODUCT_MAPPING = [
        'XML_ID' => [
            'XML_ID',
        ],
        'CODE' => [
            'CODE',
        ],
        'NAME_RU' => [
            'NAME',
        ],
        'NAME_EN' => [
            'PROPERTY_NAME_EN',
        ],
        'INFO_RU' => [
            'PREVIEW_TEXT',
        ],
        'INFO_EN' => [
            'PROPERTY_PREVIEW_TEXT_EN',
        ],
        'DESCRIPTION_RU' => [
            'DETAIL_TEXT',
        ],
        'DESCRIPTION_EN' => [
            'PROPERTY_DETAIL_TEXT_EN',
        ],
        'PROPERTY_MATERIAL' => [
            'PROPERTY_MATERIAL',
        ],
        /*'' => [
            'IBLOCK_SECTION_ID',
            'IBLOCK_SECTION',
        ],*/
        'OFFERS' => [
            'OFFERS',
        ],
    ];

    protected const OFFER_MAPPING = [
        'XML_ID' => [
            'XML_ID'
        ],
        'PROPERTY_CML2_LINK' => [
            'PROPERTY_CML2_LINK',
        ],
        'CODE' => [
            'CODE',
        ],
        'PROPERTY_METAL' => [
            'PROPERTY_METAL',
        ],
        'PROPERTY_COLOR' => [
            'PROPERTY_COLOR',
        ],
        'PRICE_RU' => [
            'PRICE_BASE',
        ],
        'PRICE_EN' => [
            'PRICE_USD',
        ],
    ];

    const SECTION_FIELDS = ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'UF_NAME_EN', 'UF_NAME_RU',];

    const ROOT_SECTIONS = [
        'SECTION' => [
            [
                'NAME' => 'Каталог',
                'UF_NAME_EN' => 'Jewelry',
            ],
        ],
        'CATALOG' => [],
    ];

    const MAX_PREVIEW_TEXT_LEN = 50;

    /** @var int $step */
    protected $step = 0;


    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);
        $arModules[] = 'iblock';
        $arModules[] = 'catalog';
        $arModules[] = 'sale';
    }

    protected function adjustProduct($arProduct)
    {
        $this->adjustSections($arProduct);
        $arProduct['OFFERS'] = $this->createOffers($arProduct);

        $arProduct['CODE'] = static::translit($arProduct['CODE']);
        $arProduct['PREVIEW_TEXT'] = $this->getTextParser()->html_cut($arProduct['PREVIEW_TEXT'], static::MAX_PREVIEW_TEXT_LEN);

        $arProduct['ACTIVE'] = 'Y';
        $arProduct['PRODUCT']['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_SKU;
        return $arProduct;
    }

    protected function adjustOffer($arOffer)
    {
        $arOffer['ACTIVE'] = 'Y';
        $arOffer['PRODUCT']['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_OFFER;
        return $arOffer;
    }

    protected function adjustSections(&$arProduct)
    {

    }

    protected function createOffers($arProduct)
    {
        $arOffers = [];
        foreach ($arProduct['OFFERS'] as $arOffer) {

            $arOffer = $this->map($this->arOfferTemplate, $arOffer, $this->arOfferProps, static::OFFER_MAPPING);
            $arOffer = $this->adjustOffer($arOffer);

            $arOffers[] = $arOffer;
        }

        return $arOffers;
    }


    public function initAgent()
    {
        parent::initAgent();
        $this->initAll();
    }

    protected function readXml()
    {
        $arResult = [];

        //$recid = '323824198';

        $arQuery = [
            //'recid' => $recid,
            'c' => round(microtime() / 1000),
            'getparts' => 'true',
            'getoptions' => 'true',
            'slice' => $this->step,
            'size' => 36,
        ];

        $url = static::URL;
        if(strpos($url, '?') !== false) {
            $url .= '&';
        } else {
            $url .= '?';
        }
        $url .= http_build_query($arQuery);

        $obHttp = $this->getHttp();

        $arResponse = $obHttp->get($url);

        if($arResponse['code'] == 200) {
            $arResponse = $arResponse['data'];
            if(is_string($arResponse)) {
                $arResponse = json_decode($arResponse, true);
            }
            foreach ($arResponse['products'] as &$item) {

                foreach (['gallery', 'json_options',] as $jsonField) {
                    if (is_string($item[$jsonField])) {
                        $item[$jsonField] = json_decode($item[$jsonField], true);
                    }
                }

                $postfix = 'EN';

                $arProduct = [
                    'XML_ID' => $item['uid'] . '.' . $item['externalid'],
                    "NAME_$postfix" => $item['title'],
                    "INFO_$postfix" => $item['descr'],
                    "DESCRIPTION_$postfix" => $item['text'],
                    'PROPERTY_MATERIAL' => '',
                    'OFFERS' => [],
                ];

                foreach ($item['characteristics'] as $char) {
                    $prop = 'PROPERTY_' . mb_strtoupper($char['title']);
                    if(isset($arProduct[$prop])) {
                        $arProduct[$prop] = $char['value'];
                    } else {

                    }
                }

                $arProduct['PROPERTY_PHOTO'] = [];
                foreach ($item['gallery'] as $img) {
                    $arProduct['PROPERTY_PHOTO'][] = $img['img'];
                }

                //if($postfix == 'EN') {
                    //$arProduct['CODE'] = static::translit($item['title']);
                    $url = $item['url'];
                    $url = explode('/', $url);
                    $code = '';
                    while(!$code && $url) {
                        $code = array_pop($url);
                    }
                    $arProduct['CODE'] = $code;
                //}

                foreach ($item['editions'] as $offer) {
                    $price = $offer['price'];
                    $price = str_replace(' ', '', $price);
                    $price = floatval($price);

                    $arOffer = [
                        'XML_ID' => $arProduct['XML_ID'] . '#' . $offer['uid'] . '.' . $offer['externalid'],
                        'PROPERTY_METAL' => $offer['Metal'],
                        'PROPERTY_COLOR' => $offer['Type'],
                        "PRICE_$postfix" => $price,
                        'PROPERTY_CML2_LINK' => $arProduct['XML_ID'],
                    ];
                    $arProduct['OFFERS'][] = $arOffer;
                }

                $arResult[] = $arProduct;
            }
            unset($item);
        }


        return $arResult;
    }

    public function execute(...$params)
    {
        $this->step = intval($params[0]);
        if($this->step <= 0) {
            $this->step = 1;
        }

        $this->applyAll();

        /*$postProcessing = !!$params[0];
        $forceYaImg = !!$params[1];
        if($postProcessing) {
            static::registerYaImg([0, $forceYaImg, 5], 'N', 1);
            AdjustCsvColorAgent::register([0, false, 15], 'N', 1);
            return false;
        }*/

        return true;
    }

    /*public function executeInit(...$params)
    {
        $this->lastId = 0;
        $this->timestamp = null;
        $this->bFileForceReload = false;

        $this->arData = $this->readXml();

        $this->initXmlCatalog();
    }*/

    protected function convertCml2Link($arIds)
    {
        foreach ($this->arOffers as &$arOffer) {
            if(array_key_exists($arOffer['PROPERTIES']['CML2_LINK'], $arIds)) {
                $arOffer['PROPERTIES']['CML2_LINK'] = $arIds[$arOffer['PROPERTIES']['CML2_LINK']];
            } else {
                $arOffer['PROPERTIES']['CML2_LINK'] = false;
            }
        }
        unset($arOffer);
    }

    public static function dump($data)
    {
        gsv_dump($data);
    }

    protected function log($message, $params = [])
    {
        if($params) {
            foreach ($params as &$param) {
                if(!is_string($param) && !is_numeric($param)) {
                    $param = var_export($param, true);
                }
            }
            $message = sprintf($message, ...$params);
        }

        $filename = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/import*.log';
        gsv_dump($message, $filename);
    }

    protected function makeFileArray($path)
    {
        /*if(is_string($path)) {
            $arPath = parse_url($path);
            $arBasePath = parse_url(SyncRestApi::URL);

            if (empty($arPath['host'])) {
                $arPath['scheme'] = $arBasePath['scheme'];
                $arPath['host'] = $arBasePath['host'];
                if ($arBasePath['post']) {
                    $arPath['port'] = $arBasePath['port'];
                } else {
                    unset($arPath['port']);
                }
                $path = build_url($arPath);
                //gsv_dump($path);
            }

            $client = new \Gsv\Bitrix\Http\RawBitrixHttpClient(["redirect" => false]);

            if($arPath['host'] == $arBasePath['host']) {
                $arHeaders = SyncRestApi::AUTH;
            } else {
                $arHeaders = [];
            }

            while (true) {
                $g = $client->get($path, [], $arHeaders);
                //gsv_dump($g);
                if($g['code'] >= 300 && $g['code'] < 400) {
                    $arHeaders = [];
                    foreach ($g['headers'] as $k => $v) {
                        if(strtolower($k) == 'location') {
                            $path = $v[0];
                            break;
                        }
                    }
                } else {
                    break;
                }
            }

            $arPath = parse_url($path);
            $tmpFile = \CTempFile::GetFileName(basename($arPath['path']));
            $dir = dirname($tmpFile);
            if(!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $r = file_put_contents($tmpFile, $g['data']);
            $path = $tmpFile;
            //gsv_dump($path);
            //gsv_dump($r);
        }*/

        $arFile =  \CFile::MakeFileArray($path);
        //gsv_dump($arFile);
        //die();
        return $arFile;
    }

    public function executeYaImg(...$params)
    {
        if($params) {
            $this->step = intval($params[0]);
        } else {
            $this->step = 0;
        }

        if(isset($params[1])) {
            $this->bFileForceReload = !!($params[1]);
        } else {
            $this->bFileForceReload = false;
        }

        if(isset($params[2])) {
            $limit = intval($params[2]);
            if($limit <= 0 ) {
                $limit = static::PHOTO_UPDATE_CNT;
            }
        } else {
            $limit = static::PHOTO_UPDATE_CNT;
        }

        $IBLOCK_ID = static::PRODUCT_IBLOCK_ID;
        $dbElements = \CIBlockElement::GetList(
            [
                'ID' => 'ASC',
            ],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                '>ID' => $this->step,
            ],
            false,
            ['nTopCount' => $limit],
            ['ID', 'IBLOCK_ID']
        );
        $arElements = [];
        $this->step = null;
        while($arElement = $dbElements->Fetch()) {
            $this->step = intval($arElement['ID']);
            $arElement['PROPERTIES'] = [];
            $arElements[$arElement['ID']] = $arElement;
        }

        if($arElements) {
            \CIBlockElement::GetPropertyValuesArray(
                $arElements,
                $IBLOCK_ID,
                ['ID' => array_keys($arElements)],
                ['CODE' => ['PHOTO', 'ARTICLE']]
            );
        }

        foreach ($arElements as &$arElement) {
            if(empty($arElement["PROPERTIES"]['PHOTO']['VALUE']) || $this->bFileForceReload || empty($arElement["PROPERTIES"]['VIDEO']['VALUE'])) {
                $article = $arElement["PROPERTIES"]['ARTICLE']['VALUE'];
                if(empty($article)) {
                    continue;
                }

                $arFiles = $this->listYaDisk("/$article");

                $arPhotos = [];
                $arVideos = [];
                foreach ($arFiles as $arFile) {
                    if($arFile['type'] == 'dir') {
                        //Or should we visit it too?
                        continue;
                    }
                    if($arFile['type'] != 'file') {
                        continue;
                    }
                    if($arFile['media_type'] != 'image' && $arFile['media_type'] != 'video') {
                        continue;
                    }
                    //We should preserve the name so...
                    $tmpname = \CTempFile::GetFileName($arFile['name']);
                    $tmpdir = dirname($tmpname);
                    if(!is_dir($tmpdir)) {//We should fix that bug.
                        mkdir($tmpdir, 0777, true);
                    }
                    //gsv_dump($arFile);
                    $content = file_get_contents($arFile['file']);
                    file_put_contents($tmpname, $content);
                    $arMedia = static::MakeFileArray($tmpname);
                    if($arFile['media_type'] == 'image') {
                        $arPhotos[] = $arMedia;
                    } else /*if($arFile['media_type'] == 'video')*/ {
                        $arVideos[] = $arMedia;
                    }
                }
                //Do we need that? Is SetPropertyValuesEx() safe?
                if(empty($arPhotos)) {
                    $arPhotos = ['del' => 'Y'];
                }
                if(empty($arVideos)) {
                    $arVideos = ['del' => 'Y'];
                }
                $arProps = [];
                if(empty($arElement["PROPERTIES"]['PHOTO']['VALUE']) || $this->bFileForceReload) {
                    $arProps['PHOTO'] = $arPhotos;
                }
                if($this->bFileForceReload || empty($arElement["PROPERTIES"]['VIDEO']['VALUE'])) {
                    $arProps['VIDEO'] = $arVideos;
                }
                //At least one property is updated each time! So we can avoid the check "if($arProps)"
                \CIBlockElement::SetPropertyValuesEx($arElement['ID'], $arElement['IBLOCK_ID'], $arProps);
            }
        }
        unset($arElement);

        if($this->step === null) {
            return false;
        } else {
            return [$this->step, $this->bFileForceReload, $limit];
        }
    }

    protected function getYaDisk($method, $params)
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
            throw new \Exception("YaDisk return ${response['code']}", $response['code']);
        }

        return $response['data'];
    }
}