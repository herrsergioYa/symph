<?php

namespace Gsv\Agents;

use Gsv\Helpers\SyncRestApi;

class ImportCatalogAgent extends \Gsv\Bitrix\Agent
{
    public const DEBUG = false;
    public const FILE_HASH_COMPARE = false;

    //We DO initialize them at runtime!
    public const PRODUCT_IBLOCK_ID = PRODUCT_IBLOCK_ID;
    public const OFFER_IBLOCK_ID = OFFER_IBLOCK_ID;

    protected $bFileForceReload = false;

    //public const IN_STOCK = 'in stock';
    //public const IN_STOCK_QUANTITY = 1;

    public const TRANSLIT_LANG = 'ru'; //?
    public const TRANSLIT_PARAMS = array(
        "max_len" => 100,
        "change_case" => 'L', // 'L' - toLower, 'U' - toUpper, false - do not change
        "replace_space" => '-',
        "replace_other" => '-',
        "delete_repeat_replace" => true,
        "safe_chars" => '',
    );

    protected $arProductProps = [];
    protected $arOfferProps = [];

    protected $arIblockCache = [];

    protected $arEnum = [];
    protected $arEnumXmlId = [];
    protected $arIblocks = [];

    protected $arPrices = [];
    protected $arStores = [];

    protected $arData;

    protected $arProducts;
    protected $arOffers;

    protected $arProductTemplate = null;
    protected $arOfferTemplate = null;

    protected $arExistingProducts;
    protected $arExistingOffers;

    protected const PRODUCT_MAPPING = [
        'PRODUCT_XML_ID' => [
            'XML_ID',
        ],
        'PROPERTY_PRODUCT_CML2_ARTICLE' => [
            'PROPERTY_CML2_ARTICLE',
        ],
        'PROPERTY_PRODUCT_ARTICLE_COLOR' => [
            'PROPERTY_ARTICLE_COLOR',
        ],
        'PROPERTY_OFFER_TSVET' => [
            'PROPERTY_COLOR',
        ],
        'PROPERTY_OFFER_MORE_PHOTO' => [
            'PROPERTY_PHOTO',
            //'PROPERTY_PHOTO_MEN',
        ],
        'PRODUCT_DETAIL_TEXT' => [
            'DETAIL_TEXT',
        ],
        'PRODUCT_NAME' => [
            'NAME',
        ],
        'PRODUCT_CODE' => [
            'CODE',
        ],
    ];

    protected const OFFER_MAPPING = [
        'OFFER_XML_ID' => [
            'XML_ID'
        ],
        'PRODUCT_XML_ID' => [
            'PROPERTY_CML2_LINK',
        ],
        'PROPERTY_OFFER_RAZMER' => [
            'PROPERTY_SIZE',
        ],
        /*'PROPERTY_OFFER_TSVET' => [
            'PROPERTY_COLOR',
        ],*/
        'OFFER_NAME' => [
            'NAME',
        ],
        'OFFER_CODE' => [
            'CODE',
        ],
        'QUANTITY' => [
            'PRODUCT_QUANTITY'
        ],
       /* 'PROPERTY_OFFER_MORE_PHOTO' => [
            'PROPERTY_PHOTO',
        ],*/
        /* TEMPORARY!!
        'PROPERTY_MORE_PHOTO_MALE' => [
            'PROPERTY_PHOTO_MEN',
        ],
        'PROPERTY_DETAIL_TEXT_EN' => [
            'PROPERTY_DESCRIPTION_EN',
        ],
        'PROPERTY_DETAIL_TEXT_RU' => [
            'PROPERTY_DESCRIPTION_RU',
        ],*/
        'PRICE_1' => [
            'PRICE_BASE',
        ],
        /*'PRICE_19' => [
            'PRICE_RUB',
        ],
        'PRICE_20' => [
            'PRICE_KZT',
        ],*/
        'STORE_1' => [
            'STORE_1',
        ],
    ];

    //public const PRODUCT_XML_PROPERTIES = ['TSVET'];
    //public const OFFER_XML_PROPERTIES = ['RAZMER'];

    /** @var @var \CIBlock $obIblock */
    protected $obIblock = null;

    /** @var @var \CIBlockProperty $obProperty */
    protected $obProperty = null;

    /** @var @var \CIBlockPropertyEnum $obEnum */
    protected $obEnum = null;

    /** @var \CIBlockElement $obElement */
    protected $obElement = null;

    /** @var ?SyncRestApi $restApi  */
    protected ?SyncRestApi $restApi = null;

    /** @var int $lastId */
    protected $lastId = 0;

    /** @var ?int $timestamp */
    protected $timestamp = null;

    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);
        $arModules[] = 'iblock';
        $arModules[] = 'catalog';
    }

    public static function asProp($target)
    {
        $pos = strpos($target, 'PROPERTY_');
        if($pos === 0) {
            return substr($target, strlen('PROPERTY_'));
        } else {
            return false;
        }
    }

    public static function asProd($target)
    {
        $pos = strpos($target, 'PRODUCT_');
        if($pos === 0) {
            return substr($target, strlen('PRODUCT_'));
        } else {
            return false;
        }
    }


    public static function asPrice($target)
    {
        $pos = strpos($target, 'PRICE_');
        if($pos === 0) {
            return substr($target, strlen('PRICE_'));
        } else {
            return false;
        }
    }

    public static function asStoreAmount($target)
    {
        $pos = strpos($target, 'STORE_');
        if($pos === 0) {
            return substr($target, strlen('STORE_'));
        } else {
            return false;
        }
    }

    public static function asField($target)
    {
        if(static::asProp($target) || static::asPrice($target) || static::asProd($target) || static::asStoreAmount($target)) {
            return false;
        } else {
            return $target;
        }
    }

    protected function map(&$arTemplate, $item, $arProperties, $MAPPING)
    {
        if($arTemplate === null) {
            $arItem = [
                'PROPERTIES' => [],
                'PRICES' => [],
                'PRODUCT' => [],
                'STORES' => [],
            ];
            foreach ($arProperties as $arProperty) {
                if(!$arProperty) {
                    continue;
                }
                $prop = $arProperty['CODE'];
                if ($arProperty['MULTIPLE'] == 'Y') {
                    $arItem['PROPERTIES'][$prop] = [];
                } else {
                    $arItem['PROPERTIES'][$prop] = false;
                }
            }
            $arTemplate = $arItem;
        } else {
            $arItem = $arTemplate;
        }
        $data = [];
        foreach ($item as $key => $value) {
            if($key == 'PROPERTIES') {
                foreach ($value as $code => $arProp) {
                    $arEnumXmlId = false;
                    if($code == 'TSVET' && $arProp['PROPERTY_TYPE'] == 'L') {
                        $propId = $this->arProductProps['COLOR']['ID'];
                        if(!array_key_exists($propId, $this->arEnumXmlId)) {
                            $this->arEnumXmlId[$propId] = [];
                        }
                        $arEnumXmlId = &$this->arEnumXmlId[$propId];
                    }
                    if($code == 'RAZMER' && $arProp['PROPERTY_TYPE'] == 'L') {
                        $propId = $this->arOfferProps['SIZE']['ID'];
                        if(!array_key_exists($propId, $this->arEnumXmlId)) {
                            $this->arEnumXmlId[$propId] = [];
                        }
                        $arEnumXmlId = &$this->arEnumXmlId[$propId];
                    }
                    if($arProp['MULTIPLE']) {
                        foreach ($arProp['VALUE'] as $i => $value) {
                            $data[] = [
                                "PROPERTY_$code",
                                $value,
                            ];
                            if($arEnumXmlId !== false) {
                                $arEnumXmlId[$value] = $arProp['VALUE_XML_ID'][$i];
                            }
                        }
                    } else {
                        $value = $arProp['VALUE'];
                        $data[] = [
                            "PROPERTY_$code",
                            $value,
                        ];
                        if($arEnumXmlId !== false) {
                            $arEnumXmlId[$value] = $arProp['VALUE_XML_ID'];
                        }
                    }
                    unset($arEnumXmlId);
                }
            } else if($key == 'OFFERS') {

            } else if($key == 'PRICES') {
                foreach ($value as $code => $arProp) {
                    $data[] = [
                        "PRICE_$code",
                        $arProp,
                    ];
                }
            } else if($key == 'STORES') {
                foreach ($value as $code => $amount) {
                    $data[] = [
                        "STORE_$code",
                        $amount,
                    ];
                }
            } else {
                $data[] = [
                    $key,
                    $value,
                ];
            }
        }
        foreach ($data as $pair) {
            list($key, $val) = $pair;
            if(!array_key_exists($key, $MAPPING)) {
                continue;
            }
            foreach ($MAPPING[$key] as $target) {
                if ($prop = static::asProp($target)) {
                    if(empty($arProperties[$prop])) {
                        $arItem['PROPERTIES'][$prop][] = $val;
                        continue;
                    }
                    $arProperty = $arProperties[$prop];
                    if ($arProperty['MULTIPLE'] == 'Y') {
                        $arItem['PROPERTIES'][$prop][] = $val;
                    } else {
                        $arItem['PROPERTIES'][$prop] = $val;
                    }
                } else if ($price = static::asPrice($target)) {
                    $arItem['PRICES'][$price] = $val;
                } else if ($store = static::asStoreAmount($target)) {
                    $arItem['STORES'][$store] = $val;
                } else if ($prod = static::asProd($target)) {
                    $arItem['PRODUCT'][$prod] = $val;
                } else { //Field?
                    $arItem[$target] = $val;
                }
            }
        }
        return $arItem;
    }

    protected function adjustProduct($arProduct)
    {
        $arProduct['PRODUCT']['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_SKU;
        return $arProduct;
    }

    protected function adjustOffer($arOffer)
    {
        $arOffer['PRODUCT']['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_OFFER;
        return $arOffer;
    }

    protected function adjustCatalog()
    {

    }

    protected function adjustNewElement(&$arElement)
    {
        //$arElement['ACTIVE'] = 'N';
    }

    protected static function translit($text)
    {
        return \CUtil::translit($text, static::TRANSLIT_LANG, static::TRANSLIT_PARAMS);
    }

    protected function adjustXmlProperties(&$arItems, $arProperties)
    {
        foreach ($arItems as &$arItem) {
            if(static::FILE_HASH_COMPARE) {
                foreach ($arItem as $fieldCode => &$value) {
                    if (substr($fieldCode, -8) == '_PICTURE') {
                        if ($value) {
                            $value = static::makeFileArray($value);
                        } else {
                            $value = false;
                        }
                    }
                }
                unset($value);
            }
            foreach ($arItem['PROPERTIES'] as $code => &$value) {
                if($code == 'CML2_LINK') {
                    continue;
                }
                $arProperty = $arProperties[$code];
                $value = $this->applyProperty($value, $arProperty);
            }
            unset($value);
        }
        unset($arItem);
    }

    protected function fixFileProperties($arProperties, $arProps)
    {
        if(is_array($arProps)) {
            foreach ($arProperties as $code => &$value) {
                $arProperty = $arProps[$code];
                if ($arProperty['PROPERTY_TYPE'] == 'F') {
                    if(!static::FILE_HASH_COMPARE) {
                        $value = $this->unifyProp($value, $arProperty);
                        foreach ($value as $k => &$val) {
                            if($val) {
                                $val = static::makeFileArray($val);
                            }
                            if(!$val) {
                                unset($value[$k]);
                            }
                            if(is_string(\CFile::CheckImageFile($val))) {
                                unset($value[$k]);
                            }
                        }
                        unset($val);
                        $value = $this->deunifyProp($value, $arProperty);
                    }
                    if (empty($value)) {
                        $value = ['del' => 'Y'];
                    }
                } else {
                    $value = $this->unifyProp($value, $arProperty);
                    foreach ($value as $k => &$val) {
                        if(!$val) {
                            unset($value[$k]);
                            continue;
                        }
                        if(is_array($val)) {
                            if(!array_key_exists('VALUE', $val)) {
                                $val = array("VALUE"=>$val,"DESCRIPTION"=>"");
                            }
                        }
                    }
                    unset($val);
                    $value = $this->deunifyProp($value, $arProperty);
                }
            }
            unset($value);
        } else {
            foreach ($arProperties as $code => &$value) {;
                if (substr($code, -8) == '_PICTURE') {
                    if(!static::FILE_HASH_COMPARE) {
                        if($value) {
                            $value = static::makeFileArray($value);
                        }
                    }
                    if (empty($value)) {
                        $value = ['del' => 'Y'];
                    }
                }
            }
            unset($value);
        }
        return $arProperties;
    }

    protected static function getPropertyCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($prop = static::asProp($target)) {
                    $arResult[] = $prop;
                }
            }
        }
        return array_unique($arResult);
    }

    protected static function getProductCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($prod = static::asProd($target)) {
                    $arResult[] = $prod;
                }
            }
        }
        return array_unique($arResult);
    }

    protected static function getFieldCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($field = static::asField($target)) {
                    $arResult[] = $field;
                }
            }
        }
        return array_unique($arResult);
    }

    protected static function getPriceCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($price = static::asPrice($target)) {
                    $arResult[] = $price;
                }
            }
        }
        return array_unique($arResult);
    }

    protected static function getStoreCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($store = static::asStoreAmount($target)) {
                    $arResult[] = $store;
                }
            }
        }
        return array_unique($arResult);
    }

    protected static function getProperty($IBLOCK_ID, $propCode)
    {
        $arFilter = ['IBLOCK_ID' => $IBLOCK_ID];
        $propId = intval($propCode);
        if($propId <= 0) {
            $arFilter['CODE'] = $propCode;
        } else {
            $arFilter['ID'] = $propId;
        }
        $dbProp = \CIBlockProperty::GetList(
            [],
            $arFilter
        );
        if($arProp = $dbProp->Fetch()) {
            return $arProp;
        }
        return false;
    }

    protected static function getProperties($IBLOCK_ID, $propCodes)
    {
        $arResult = [];
        foreach ($propCodes as $propCode) {
            $arResult[$propCode] = static::getProperty($IBLOCK_ID, $propCode);
        }
        return $arResult;
    }

    protected function fillEnumProperty($property)
    {
        if(empty($property) || $property['PROPERTY_TYPE'] != 'L') {
            return false;
        }

        $arItems = [];
        $dbEnum = \CIBlockPropertyEnum::GetList(
            [],
            [
                'IBLOCK_ID' => $property['IBLOCK_ID'],
                'PROPERTY_ID' => $property['ID'],
            ]
        );
        while($arEnum = $dbEnum->Fetch()) {
            $name = static::clearEnumValue($arEnum['VALUE']);
            $arItems[$name] = $arEnum;
        }
        return $arItems;
    }

    protected function fillEnumProperties($properties)
    {
        foreach ($properties as $property) {
            if($property['PROPERTY_TYPE'] == 'L') {
                $this->arEnum[$property['ID']] = $this->fillEnumProperty($property);
            }
        }
    }

    protected function findEnumProperty($value, $arProperty)
    {
        $arEnum = $this->arEnum[$arProperty['ID']];
        if(!is_array($arEnum)) {
            return false;
        }

        if(empty($value)) {
            return false;
        }

        $val = static::clearEnumValue($value);

        if(empty($val)) {
            return false;
        }

        if(array_key_exists($val, $arEnum)) {
            return $arEnum[$val]['ID'];
        } else if($this->canCreateEnum($arProperty)) {
            if($this->obEnum === null) {
                $this->obEnum = new \CIBlockPropertyEnum();
            }

            $arFields = [
                'PROPERTY_ID' => $arProperty['ID'],
                'VALUE' => trim($value),
            ];

            if(isset($this->arEnumXmlId[$arProperty['ID']][$value])) {
                $arFields['XML_ID'] = $this->arEnumXmlId[$arProperty['ID']][$value];
            }

            $ID = $this->obEnum->Add($arFields);

            $arEnum[$val] = [
                'ID' => $ID,
            ] + $arFields;

            $this->arEnum[$arProperty['ID']] = $arEnum;
            return $arEnum[$val]['ID'];
        } else {
            return false;
        }
    }

    protected function canCreateEnum($arProperty)
    {
        return true;
    }

    protected function fillIblockProperty($property)
    {
        if(empty($property)  || $property['PROPERTY_TYPE'] != 'E') {
            return false;
        }
        if(empty($property['LINK_IBLOCK_ID'])) {
            $property['LINK_IBLOCK_ID'] = $this->fixLinkIblock($property);
            if(empty($property['LINK_IBLOCK_ID'])) {
                return [];
            }
        }

        $arItems = [];
        $dbElem = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $property['LINK_IBLOCK_ID'],
            ],
            false,
            false,
            ['ID', 'NAME']
        );
        while($arElem = $dbElem->Fetch()) {
            $name = static::clearEnumValue($arElem['NAME']);
            //$id = intval($arElem['ID']);
            $arItems[$name] = $arElem;
        }
        return $arItems;
    }

    protected function fillIblockProperties($properties)
    {
        foreach ($properties as $property) {
            if($property['PROPERTY_TYPE'] == 'E') {
                if(empty($property['LINK_IBLOCK_ID'])) {
                    $property['LINK_IBLOCK_ID'] = $this->fixLinkIblock($property);
                    if(empty($property['LINK_IBLOCK_ID'])) {
                        continue;
                    }
                }
                $IBLOCK_ID = intval($property['LINK_IBLOCK_ID']);
                if(array_key_exists($IBLOCK_ID, $this->arIblocks)) {
                    continue;
                }
                if($property['CODE'] == 'CML2_LINK') {
                    continue;
                }
                $this->arIblocks[$IBLOCK_ID] = $this->fillIblockProperty($property);
            }
        }
    }

    protected function findIblockProperty($value, $arProperty)
    {
        $IBLOCK_ID = intval($arProperty['LINK_IBLOCK_ID']);
        if($IBLOCK_ID <= 0) {
            $IBLOCK_ID = $this->fixLinkIblock($arProperty);
            if(empty($IBLOCK_ID)) {
                return false;
            }
        }

        $arIblock = $this->arIblocks[$IBLOCK_ID];
        if(!is_array($arIblock)) {
            return false;
        }

        if(empty($value)) {
            return false;
        }

        $val = static::clearEnumValue($value);

        if(empty($val)) {
            return false;
        }

        if(array_key_exists($val, $arIblock)) {
            return $arIblock[$val]['ID'];
        } else if($this->canCreateIblockElement($arProperty)) {
            if($this->obElement === null) {
                $this->obElement = new \CIBlockElement();
            }

            $arFields = [
                'IBLOCK_ID' => $IBLOCK_ID,
                'NAME' => trim($value),
            ];

            $this->adjustNewElement($arFields);

            $ID = $this->obElement->Add($arFields);

            $arIblock[$val] = [
                'ID' => $ID,
            ] + $arFields;

            $this->arIblocks[$IBLOCK_ID] = $arIblock;

            return $arIblock[$val]['ID'];
        } else {
            return false;
        }
    }

    protected function canCreateIblockElement($arProperty)
    {
        return false;
    }

    protected function fillPrices($prices)
    {
        $dbPrices = \Bitrix\Catalog\GroupTable::getList([
            'filter' => [
                '=NAME' => array_unique($prices),
            ],
            'select' => [
                'NAME', 'ID'
            ],
        ]);
        while($arPrice = $dbPrices->fetch()) {
            $this->arPrices[$arPrice['NAME']] = $arPrice;
        }
    }

    protected function fillStores($stores)
    {
        $dbStores = \Bitrix\Catalog\StoreTable::getList([
            'filter' => [
                '=ID' => array_unique($stores),
            ],
            'select' => [
                'XML_ID', 'ID'
            ],
        ]);
        while($arStore = $dbStores->fetch()) {
            $this->arStores[$arStore['ID']] = $arStore;
        }
    }

    public function initAgent()
    {
        parent::initAgent();

        $this->arProductProps = static::getProperties(static::PRODUCT_IBLOCK_ID,
            static::getPropertyCodes(self::PRODUCT_MAPPING));

        $this->arOfferProps = static::getProperties(static::OFFER_IBLOCK_ID,
            static::getPropertyCodes(self::OFFER_MAPPING));

        $this->fillEnumProperties($this->arProductProps);
        $this->fillEnumProperties($this->arOfferProps);

        $this->fillIblockProperties($this->arProductProps);
        $this->fillIblockProperties($this->arOfferProps);

        $this->fillPrices(static::getPriceCodes(self::OFFER_MAPPING));
        $this->fillStores(static::getStoreCodes(self::OFFER_MAPPING));
    }

    protected static function clearEnumValue($value)
    {
        if(!empty($value)) {
            return preg_replace('/\s+/', ' ', mb_strtolower(trim($value)));
        } else {
            return $value;
        }
    }

    protected function readXml($short = false)
    {
        $restApi = $this->getRestApi();
        $arReq = ['lastId' => $this->lastId];
        if($this->timestamp !== null) {
            $arReq['timestamp'] = $this->timestamp;
        }

        if(static::DEBUG) {
            $cache = __DIR__ . '/temp.json';

            if (file_exists($cache)) {
                $arCatalog = json_decode(file_get_contents($cache), true);
            } else {
                $arCatalog = $restApi->updates($arReq);
                file_put_contents($cache, json_encode($arCatalog));
            }
        } else {
            $arCatalog = $restApi->updates($arReq);
        }

        if(isset($arCatalog['lastId']) && is_numeric($arCatalog['lastId'])) {
            $this->lastId = intval($arCatalog['lastId']);
        } else {
            $this->lastId = null;
        }
        if($arCatalog['result']) {
            $arCatalog = $arCatalog['result'];
        } else {
            $arCatalog = [];
        }

        if($short) {
            return $arCatalog;
        }

        $arXmlIds = [];
        foreach ($arCatalog as &$arProduct) {
            if (empty($arProduct['OFFERS'])) {
                continue;
            }
            foreach ($arProduct['OFFERS'] as &$arOffer) {
                $arOffer['PRICES'] = [];
                $arOffer['STORES'] = [];
                $arXmlIds[] = $arOffer['XML_ID'];
            }
            unset($arOffer);
        }
        unset($arProduct);
        $arPriceList = $restApi->priceList(['XML_ID' => $arXmlIds]);
        if($arPriceList && $arPriceList['result']) {
            $arPriceList = $arPriceList['result'];
        } else {
            $arPriceList = [];
        }
        $arAmountList = $restApi->storeAmountList(['XML_ID' => $arXmlIds]);
        if($arAmountList && $arAmountList['result']) {
            $arAmountList = $arAmountList['result'];
        } else {
            $arAmountList = [];
        }
        foreach ($arCatalog as &$arProduct) {
            if(empty($arProduct['OFFERS'])) {
                continue;
            }
            foreach ($arProduct['OFFERS'] as &$arOffer) {
                $arPrices = $arPriceList[$arOffer['XML_ID']] ?? [];
                foreach ($arPrices as $id => $arValue) {
                    $arOffer['PRICES'][$id] = $arValue;
                }
                $arAmounts = $arAmountList[$arOffer['XML_ID']] ?? [];
                foreach ($arAmounts as $id => $arValue) {
                    $arOffer['STORES'][$id] = $arValue;
                }
            }
            unset($arOffer);
        }
        unset($arProduct);

        return $arCatalog;
    }

    protected function getRestApi()
    {
        if($this->restApi === null)
        {
            $this->restApi = new SyncRestApi();
        }
        return $this->restApi;
    }

    protected function readXmlCatalog()
    {
        $this->arProducts = [];
        $this->arOffers = [];

        $arData = [];

        foreach ($this->arData as $item) {

            $arProducts = [];
            $arProduct = $item;
            unset($arProduct['OFFERS']);

            foreach ($item['OFFERS'] as $itemOffer) {
                //$itemOffer['PRODUCT_ID'] = $item['XML_ID'];

                $color = '';
                $colorXmlId = '';
                if(is_array($itemOffer['PROPERTIES']['TSVET'])) {
                    if($itemOffer['PROPERTIES']['TSVET']) {
                        $color = $itemOffer['PROPERTIES']['TSVET']['VALUE'];
                        $colorXmlId = $itemOffer['PROPERTIES']['TSVET']['VALUE_XML_ID'];
                    }
                }

                if(!array_key_exists($colorXmlId, $arProducts)) {
                    $arProducts[$colorXmlId] = $arProduct;
                    $arProducts[$colorXmlId]['XML_ID'] .= '#'. $colorXmlId;
                    $arProducts[$colorXmlId]['PROPERTIES']['ARTICLE_COLOR']
                        = $arProducts[$colorXmlId]['PROPERTIES']['CML2_ARTICLE'];
                    $arProducts[$colorXmlId]['PROPERTIES']['ARTICLE_COLOR']['CODE'] = 'ARTICLE_COLOR';
                    $arProducts[$colorXmlId]['PROPERTIES']['ARTICLE_COLOR']['XML_ID'] = 'ARTICLE_COLOR';
                    if($color) {
                        $trColor = static::translit($color);
                        $arProducts[$colorXmlId]['CODE'] .= '-';
                        $arProducts[$colorXmlId]['CODE'] .= $trColor;
                        $arProducts[$colorXmlId]['PROPERTIES']['ARTICLE_COLOR']['VALUE'] .= '-';
                        $arProducts[$colorXmlId]['PROPERTIES']['ARTICLE_COLOR']['VALUE'] .= $trColor;
                        //gsv_dump($arProducts[$colorXmlId]);die();
                    }
                }

                $arProducts[$colorXmlId]['OFFERS'][] = $itemOffer;
            }

            foreach ($arProducts as $arProduct) {
                $arData[] = $arProduct;
            }
        }

//        gsv_dump($arData);
//        die();

        foreach ($arData as $item) {

            $arItem = static::merge($item, [], false);
            foreach ($item['OFFERS'] as $itemOffer) {
                $arItem = static::merge($item, $itemOffer, false);
                break;
            }

            $arProduct = $this->map($this->arProductTemplate, $arItem, $this->arProductProps, static::PRODUCT_MAPPING);
            $arProduct = $this->adjustProduct($arProduct);

            //gsv_dump($arProduct);die();

            foreach ($item['OFFERS'] as $itemOffer) {
                $arItemOffer = static::merge($item, $itemOffer, true);
                $arOffer = $this->map($this->arOfferTemplate, $arItemOffer, $this->arOfferProps, static::OFFER_MAPPING);
                //$arOffer['PRODUCT_ID'] = $arProduct['XML_ID'];
                $arOffer = $this->adjustOffer($arOffer);

                $arOffer['PROPERTIES']['CML2_LINK'] = $arProduct['XML_ID'];

                $this->arProducts[$arProduct['XML_ID']] = $arProduct;
                $this->arOffers[$arOffer['XML_ID']] = $arOffer;
                //gsv_dump($arOffer);die();
            }

        }

        $this->adjustCatalog();
    }

    protected static function merge($arProduct, $arOffer, $isOffer)
    {
        $arResult = [
            'PROPERTIES' => [],
            'PRICES' => [],
            'PRODUCT' => [],
            'STORES' => [],
        ];

        foreach ($arProduct as $k => $v) {
            if($k == 'PROPERTIES') {
                foreach ($v as $code => $val) {
                    $arResult['PROPERTIES']["PRODUCT_$code"] = $val;
                }
            } else if($k == 'PRICES') {
//                foreach ($v as $code => $val) {
//                    $arResult['PRICES']["$code"] = $val;
//                }
            } else if($k == 'OFFERS') {
            } else if($k == 'PRODUCT') {
                $arResult['PRODUCT'] = $v;
            } else if($k == 'STORES') {
//                foreach ($v as $code => $val) {
//                    $arResult['STORES']["$code"] = $val;
//                }
            } else if($k == 'QUANTITY') {

            } else {
                $arResult["PRODUCT_$k"] = $v;
            }
        }

        foreach ($arOffer as $k => $v) {
            if($k == 'PROPERTIES') {
                foreach ($v as $code => $val) {
                    $arResult['PROPERTIES']["OFFER_$code"] = $val;
                }
            } else if($k == 'PRICES') {
                if($isOffer) {
                    foreach ($v as $code => $val) {
                        $arResult['PRICES']["$code"] = $val;
                    }
                }
            } else if($k == 'PRODUCT') {
                if($isOffer) {
                    $arResult['PRODUCT'] = $v;
                }
            } else if($k == 'STORES') {
                if($isOffer) {
                    foreach ($v as $code => $val) {
                        $arResult['STORES']["$code"] = $val;
                    }
                }
            } else if($k == 'QUANTITY') {
                if($isOffer) {
                    $arResult['QUANTITY'] = $v;
                }
            } else {
                $arResult["OFFER_$k"] = $v;
            }
        }
        //gsv_dump($arResult);
        return $arResult;
    }

    protected function initXmlCatalog()
    {
        /*foreach ($this->arProducts as $item) {
            foreach ($item['PROPERTIES'] as $code => $arSrcProperty) {
                static::ensureProps($arSrcProperty, static::PRODUCT_IBLOCK_ID, $this->arProductProps, static::PRODUCT_MAPPING);
            }
        }*/
        /*foreach (static::PRODUCT_MAPPING as $source => $arMapping) {

        }*/
        foreach ($this->arData as $item) {
            foreach ($item['PROPERTIES'] as $code => $arSrcProperty) {
                static::ensureProps($arSrcProperty, static::PRODUCT_IBLOCK_ID, $this->arProductProps, static::PRODUCT_MAPPING);
            }
            //gsv_dump($item);
            foreach ($item['OFFERS'] as $offer) {
                foreach ($offer['PROPERTIES'] as $code => $arSrcProperty) {
                    static::ensureProps($arSrcProperty, static::OFFER_IBLOCK_ID, $this->arOfferProps, static::OFFER_MAPPING);
                }
            }
        }
        /*foreach ($this->arOffers as $item) {
            foreach ($item['PROPERTIES'] as $code => $arSrcProperty) {
                static::ensureProps($arSrcProperty, static::OFFER_IBLOCK_ID, $this->arOfferProps, static::OFFER_MAPPING);
            }
        }*/
    }

    protected function ensureProps($arSrcProperty, $IBLOCK_ID, &$arProductProps, $MAPPING)
    {
        if(is_string($arSrcProperty)) {
            $arSrcProperty = [
                'CODE' => $arSrcProperty,
                'PROPERTY_TYPE' => substr($arSrcProperty, -8) == '_PICTURE' ? 'F' : 'S',
                'MULTIPLE' => 'N',
            ];
            $srcCode = $arSrcProperty['CODE'];
        } else {
            $srcCode = "PROPERTY_" . $arSrcProperty['CODE'];
        }
        $code = $arSrcProperty['CODE'];

//gsv_dump($code);
//gsv_dump($arProductProps);
//        if(isset($arProductProps[$code]) && is_array($arProductProps[$code])) {
//            return true;
//        }
//        gsv_dump($MAPPING[$srcCode]);
        if(isset($MAPPING[$srcCode])) {
            $arMappings = $MAPPING[$srcCode];
        } else {
            return true;
        }
        //gsv_dump($arMappings);

        $arFields = [
            //'NAME' => $tgtProperty,
            //'CODE' => $tgtProperty,
            'IBLOCK_ID' => $IBLOCK_ID,
            'PROPERTY_TYPE' => $arSrcProperty['PROPERTY_TYPE'],
            'MULTIPLE' => $arSrcProperty['MULTIPLE'] ? 'Y' : 'N',
        ];

        if($arFields['PROPERTY_TYPE'] == 'E' || $arFields['PROPERTY_TYPE'] == 'G') {

            if($this->obIblock === null) {
                $this->obIblock = new \CIBlock();
            }

            $LINK_IBLOCK_ID = $this->obIblock->Add([
                'NAME' => $code,
                "IBLOCK_TYPE_ID" => $this->getIblock($IBLOCK_ID)['IBLOCK_TYPE_ID'],
            ]);

            $arFields['LINK_IBLOCK_ID'] = $LINK_IBLOCK_ID;
        }

        foreach ($arMappings as $target) {

            $tgtProperty = static::asProp($target);

            if(empty($tgtProperty)) {
                continue;
            }

            if(isset($arProductProps[$tgtProperty]) && is_array($arProductProps[$tgtProperty])) {
                continue;
            }

            $arFields['NAME'] = $arFields['CODE'] = $arFields['XML_ID'] = $tgtProperty;

            if($this->obProperty === null) {
                $this->obProperty = new \CIBlockProperty();
            }

            $PROP_ID = $this->obProperty->Add($arFields);

            if($PROP_ID) {
                $arProductProps[$tgtProperty] = $arFields;
            }
        }

        return true;//$PROP_ID;
    }

    protected function getIblock($IBLOCK_ID)
    {
        if(!isset($this->arIblockCache[$IBLOCK_ID])) {
            $dbIblock = \CIBlock::GetByID($IBLOCK_ID);
            $this->arIblockCache[$IBLOCK_ID] = $dbIblock->Fetch();
        }
        return $this->arIblockCache[$IBLOCK_ID];
    }

    protected function applyXmlProperties()
    {
        $this->adjustXmlProperties($this->arProducts, $this->arProductProps);
        $this->adjustXmlProperties($this->arOffers, $this->arOfferProps);
    }

    protected function readCatalog()
    {
        $this->arExistingProducts = $this->readIblockCatalog(static::PRODUCT_IBLOCK_ID, static::PRODUCT_MAPPING, array_keys($this->arProducts));
        $this->arExistingOffers = $this->readIblockCatalog(static::OFFER_IBLOCK_ID, static::OFFER_MAPPING, array_keys($this->arOffers));

        $this->readCatalogCatalog($this->arExistingProducts, $this->arExistingOffers);

        $this->readCatalogPrices($this->arExistingOffers);
        $this->readCatalogStoreAmount($this->arExistingOffers);
    }

    protected function readIblockCatalog($IBLOCK_ID, $MAPPING, $xmlIds)
    {
        $arSelect = ['ID', 'XML_ID'];
        $arPropCodes = [];
        if(!$xmlIds) {
            return [];
        }

        $arSelect = array_merge($arSelect, static::getFieldCodes($MAPPING));
        $arPropCodes = array_merge($arPropCodes, static::getPropertyCodes($MAPPING));

        $arItems = [];
        $dbItems = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                '=XML_ID' => $xmlIds,
            ],
            false,
            false,
            $arSelect
        );
        while($arItem = $dbItems->Fetch()) {
            $arItem['PROPERTIES'] = [];
            if(static::FILE_HASH_COMPARE) {
                foreach ($arItem as $field => $value) {
                    if(substr($field, -8) == '_PICTURE') {
                        if ($value) {
                            $arItem[$field] = \CFile::GetFileArray($value);
                        } else {
                            $arItem[$field] = false;
                        }
                    }
                }
            }
            $arItems[$arItem['ID']] = $arItem;
        }

        if($arItems) {
            \CIBlockElement::GetPropertyValuesArray($arItems, $IBLOCK_ID,
                ['ID' => array_keys($arItems)],
                ['CODE' => $arPropCodes],
                ['GET_RAW_DATA' => 'Y']
            );

            if(static::FILE_HASH_COMPARE) {
                foreach ($arItems as &$arItem) {
                    foreach ($arItem['PROPERTIES'] as &$arProp) {
                        if ($arProp['PROPERTY_TYPE'] == 'F') {
                            $arProp['VALUE'] = $this->unifyProp($arProp['VALUE'], $arProp);
                            foreach ($arProp['VALUE'] as $k => &$val) {
                                if (empty($val)) {
                                    unset($arProp['VALUE'][$k]);
                                    continue;
                                }
                                $val = \CFile::GetFileArray($val);
                                if (empty($val)) {
                                    unset($arProp['VALUE'][$k]);
                                }
                            }
                            unset($val);
                            $arProp['VALUE'] = $this->deunifyProp($arProp['VALUE'], $arProp);
                        }
                    }
                    unset($arProp);
                }
                unset($arItem);
            }
        }

        $arResult = [];

        foreach ($arItems as $arItem) {
            $arResult[$arItem['XML_ID']] = $arItem;
        }

        return $arResult;
    }

    protected function readCatalogCatalog(&$arProducts, &$arOffers)
    {
        $arIds = [];
        foreach ($arProducts as $key => $arProduct) {
            $arIds[$arProduct['ID']] = $key;
        }
        foreach ($arOffers as $key => $arOffer) {
            $arIds[$arOffer['ID']] = $key;
        }

        $arSelect = ['ID', 'TYPE'];
        $arSelect = array_merge($arSelect, static::getProductCodes(static::PRODUCT_MAPPING));
        $arSelect = array_merge($arSelect, static::getProductCodes(static::OFFER_MAPPING));
        $arSelect = array_values(array_unique($arSelect));

        $dbProd = \Bitrix\Catalog\Model\Product::getList([
            'filter' => [
                'ID' => array_keys($arIds),
            ],
            'select' => $arSelect
        ]);
        while($arProd = $dbProd->fetch()) {
            $key = $arIds[$arProd['ID']];
            if(array_key_exists($key, $arProducts)) {
                $arProducts[$key]['PRODUCT'] = $arProd;
            } else if(array_key_exists($key,  $arOffers)) {
                $arOffers[$key]['PRODUCT'] = $arProd;
            } else {
                //Невозможно!
            }
        }
    }

    protected function readCatalogPrices(&$arOffers)
    {
        $arIds = [];
        foreach ($arOffers as $k => &$arOffer) {
            $arIds[$arOffer['ID']] = $k;
            $arOffer['PRICES'] = [];
        }
        unset($arOffer);
        $arGrp = [];
        foreach ($this->arPrices as $k => $arPrice) {
            $arGrp[$arPrice['ID']] = $k;
        }

        $dbPrices = \Bitrix\Catalog\Model\Price::getList([
            'filter' => [
                '=PRODUCT_ID' => array_keys($arIds),
                '=CATALOG_GROUP_ID' => array_keys($arGrp),
            ],
            'select' => [
                'ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID',
                'PRICE', 'CURRENCY', 'PRICE_SCALE',
            ]
        ]);
        while($arPrice = $dbPrices->fetch()) {
            if(static::DEBUG && array_key_exists($arGrp[$arPrice['CATALOG_GROUP_ID']], $arOffers[$arIds[$arPrice['PRODUCT_ID']]]['PRICES'])) {
                static ::dump("Excess PRICE with ID = " . $arPrice['ID']);
                \Bitrix\Catalog\Model\Price::delete($arPrice['ID']);
                continue;
            }
            $arOffers[$arIds[$arPrice['PRODUCT_ID']]]['PRICES'][$arGrp[$arPrice['CATALOG_GROUP_ID']]] = $arPrice;
        }
    }

    protected function readCatalogStoreAmount(&$arOffers)
    {
        $arIds = [];
        foreach ($arOffers as $k => &$arOffer) {
            $arIds[$arOffer['ID']] = $k;
            $arOffer['STORES'] = [];
        }
        unset($arOffer);
        $arGrp = [];
        foreach ($this->arStores as $k => $arStore) {
            $arGrp[$arStore['ID']] = $k;
        }

        $dbAmounts = \Bitrix\Catalog\StoreProductTable::getList([
            'filter' => [
                '=PRODUCT_ID' => array_keys($arIds),
                '=STORE_ID' => array_keys($arGrp),
            ],
            'select' => [
                'ID', 'PRODUCT_ID', 'STORE_ID',
                'AMOUNT',
            ]
        ]);
        while($arAmount = $dbAmounts->fetch()) {
            if(static::DEBUG && array_key_exists($arGrp[$arAmount['STORE_ID']], $arOffers[$arIds[$arAmount['PRODUCT_ID']]]['STORES'])) {
                static ::dump("Excess STORE AMOUNT with ID = " . $arAmount['ID']);
                \Bitrix\Catalog\StoreProductTable::delete($arAmount['ID']);
                continue;
            }
            $arOffers[$arIds[$arAmount['PRODUCT_ID']]]['STORES'][$arGrp[$arAmount['STORE_ID']]] = $arAmount;
        }
    }

    protected function applyProperty($value, $arProperty) {

        $value = $this->unifyProp($value, $arProperty);

        foreach ($value as $i => &$val) {
            if($arProperty['PROPERTY_TYPE'] == 'L') {
                $val = $this->findEnumProperty($val, $arProperty);
            } else if($arProperty['PROPERTY_TYPE'] == 'E') {
                $val = $this->findIblockProperty($val, $arProperty);
            } else if($arProperty['PROPERTY_TYPE'] == 'N') {

            } else if($arProperty['PROPERTY_TYPE'] == 'S' && $arProperty['USER_TYPE'] == 'HTML') {
                if(!is_array($val)) {
                    $val = [
                        'TYPE' => 'TEXT',
                        'TEXT' => $val,
                    ];
                }
            } else if($arProperty['PROPERTY_TYPE'] == 'S') {

            } else if($arProperty['PROPERTY_TYPE'] == 'F') {
                if(static::FILE_HASH_COMPARE) {
                    if ($val) {
                        $val = static::makeFileArray($val);
                    } else {
                        $val = false;
                    }
                }
            }

            if(empty($val)) {
                unset($value[$i]);
            }
        }
        unset($val);

        $value = $this->deunifyProp($value, $arProperty);

        return $value;
    }

    protected function unifyProp($value, $arProperty)
    {
        if($arProperty['MULTIPLE'] != 'Y') {
            if($value) {
                $value = [$value];
            } else {
                $value = [];
            }
        } else if(empty($value)) {
            $value = [];
        }
        return $value;
    }

    protected function deunifyProp($value, $arProperty)
    {
        if($arProperty['MULTIPLE'] != 'Y') {
            if($value) {
                $value = $value[0];
            } else {
                $value = false;
            }
        }
        return $value;
    }

    protected function applyItems($IBLOCK_ID, $arItems, $arExistingItems)
    {
        $arResult = [];
        if($IBLOCK_ID == static::PRODUCT_IBLOCK_ID) {
            $arProps = $this->arProductProps;
        } else if($IBLOCK_ID == static::OFFER_IBLOCK_ID) {
            $arProps = $this->arOfferProps;
        } else {
            $arProps = [];
        }

        foreach ($arItems as $key => $arElement) {

            if(array_key_exists($key, $arExistingItems)) {
                $arExistingItem = $arExistingItems[$key];
                $ID = $arExistingItem['ID'];

                $arProperties = $arElement['PROPERTIES'];
                unset($arElement['PROPERTIES']);
                $arProduct = $arElement['PRODUCT'];
                unset($arElement['PRODUCT']);
                $arPrices = $arElement['PRICES'];
                unset($arElement['PRICES']);
                $arAmounts = $arElement['STORES'];
                unset($arElement['STORES']);

                $arExistingProperties = $arExistingItem['PROPERTIES'];
                unset($arExistingItem['PROPERTIES']);
                $arExistingProduct = $arExistingItem['PRODUCT'];
                unset($arExistingItem['PRODUCT']);
                $arExistingPrices = $arExistingItem['PRICES'];
                unset($arExistingItem['PRICES']);
                $arExistingAmounts = $arExistingItem['STORES'];
                unset($arExistingItem['STORES']);

                unset($arElement['ID']);
                unset($arElement['IBLOCK_ID']);
                //JUST IN CASE!!
                unset($arElement['PROPERTY_VALUES']);

                $arElement = $this->filterChangedProperties($arExistingItem, $arElement, []);
                $arElement = $this->fixFileProperties($arElement, false);

                if($arElement) {
                    if($this->obElement === null) {
                        $this->obElement = new \CIBlockElement();
                    }

                    $result = $this->obElement->Update($ID, $arElement);
                }

                $arProperties = $this->filterChangedProperties($arExistingProperties, $arProperties, $arProps);
                //gsv_dump($arProperties);

                $arProperties = $this->fixFileProperties($arProperties, $arProps);
                //gsv_dump($arProperties);

                if($arProperties) {
                    //gsv_dump($ID);
                    \CIBlockElement::SetPropertyValuesEx($ID, $IBLOCK_ID, $arProperties);
                }

            } else {
                if($this->obElement === null) {
                    $this->obElement = new \CIBlockElement();
                }

                unset($arElement['ID']);
                $arElement['IBLOCK_ID'] = $IBLOCK_ID;

                $this->adjustNewElement($arElement);

                $arProperties = $arElement['PROPERTIES'];
                unset($arElement['PROPERTIES']);
                $arProduct = $arElement['PRODUCT'];
                unset($arElement['PRODUCT']);
                $arPrices = $arElement['PRICES'];
                unset($arElement['PRICES']);
                $arAmounts = $arElement['STORES'];
                unset($arElement['STORES']);

                $arElement = $this->fixFileProperties($arElement, false);
                $arProperties = $this->fixFileProperties($arProperties, $arProps);

                if($arProperties) {
                    //Only this time!!
                    $arElement['PROPERTY_VALUES'] = $arProperties;
                }

                $ID = $this->obElement->Add($arElement);

                if(empty($ID)) {
                    //TODO: Придумать, что делать!
                    continue;
                }

                $arExistingItem = [];
                $arExistingProduct = false;
                $arExistingPrices = [];
                $arExistingAmounts = [];
                $arExistingProperties = false;
            }

            $arResult[$key] = $ID;

            if(empty($arExistingProduct)) {

                $arProduct['ID'] = $ID;
                if (!array_key_exists('TYPE', $arProduct)) {
                    $arProduct['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_PRODUCT;
                }
                if (!array_key_exists('QUANTITY', $arProduct)) {
                    $arProduct['QUANTITY'] = 0;
                }

                $result = \Bitrix\Catalog\Model\Product::add($arProduct);

                if (!$result->isSuccess()) {
                    //TODO: Придумать, что делать!
                    continue;
                }

                if ($result->getId() != $ID) {
                    //TODO: Невозможно! Я надеюсь...
                    continue;
                }
            } else {

                if($arExistingProduct['ID'] != $ID) {
                    //TODO: Невозможно! Я надеюсь...
                    continue;
                }

                unset($arProduct['ID']);
                unset($arProduct['TYPE']);
                $arProduct = $this->filterChangedProperties($arExistingProduct, $arProduct, []);

                if($arProduct) {
                    $result = \Bitrix\Catalog\Model\Product::update($ID, $arProduct);
                }
            }

            //TODO: Обрабатывать цены только для TYPE_PRODUCT & TYPE_OFFER?
            $this->applyPrices($ID, $arExistingPrices, $arPrices);
            $this->applyAmounts($ID, $arExistingAmounts, $arAmounts);
        }

        return $arResult;
    }

    protected function applyColors($arXmlIds)
    {
        if(empty($arXmlIds)) {
            return;
        }

        $arGroups = [];

        foreach ($arXmlIds as $xmlId => $ID) {
            $matches = [];
            if(preg_match('/^([^#]+)#([^#]+)$/', $xmlId, $matches)) {
                if(!array_key_exists($matches[1], $arGroups)) {
                    $arGroups[$matches[1]] = [];
                }
                $arGroups[$matches[1]][] = $ID;
            }
        }

        $IBLOCK_ID = static::PRODUCT_IBLOCK_ID;

        $arElems = [];
        $dbElem = \CIBlockElement::GetList(
            [],
            [
                'ID' => array_values($arXmlIds),
                'IBLOCK_ID' => $IBLOCK_ID,
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID']
        );
        while($arElem = $dbElem->Fetch()) {
            $arElem['PROPERTIES'] = [];
            $arElems[$arElem['ID']] = $arElem;
        }

        if($arElems) {
            \CIBlockElement::GetPropertyValuesArray(
                $arElems,
                $IBLOCK_ID,
                ['ID' => array_values($arXmlIds)],
                ['CODE' => 'COLORS'],
                ['GET_RAW_DATA' => 'Y']
            );
        }

        foreach ($arGroups as $arGroup) {
            foreach ($arGroup as $ID) {
                if(!array_key_exists($ID, $arElems)) {
                    continue;
                }
                $arElem = $arElems[$ID];
                if ($arElem['PROPERTIES']['COLORS']) {
                    if ($arElem['PROPERTIES']['COLORS']['VALUE']) {
                        if (count(array_diff($arElem['PROPERTIES']['COLORS']['VALUE'], $arGroup))
                            + count(array_diff($arGroup, $arElem['PROPERTIES']['COLORS']['VALUE'])) == 0) {
                            continue;
                        }
                    }
                }
                \CIBlockElement::SetPropertyValuesEx($ID, $IBLOCK_ID, [
                    'COLORS' => $arGroup,
                ]);
            }
        }
    }

    protected function applyPrices($PRODUCT_ID, $arExistingPrices, $arPrices)
    {
        foreach ($arPrices as $name => $arPrice) {
            list('PRICE' => $price, 'CURRENCY' => $currency) = $arPrice;
            if(array_key_exists($name, $arExistingPrices)) {
                $arExistingPrice = $arExistingPrices[$name];
                if(abs(floatval($arExistingPrice['PRICE']) - $price) < 0.001 && $arExistingPrice['CURRENCY'] == $currency) {
                    unset($arExistingPrices[$name]);
                    continue;
                }
                $arFields = [
                    'PRICE' => $price,
                    'CURRENCY' => $currency,
                ];
                $result = \Bitrix\Catalog\Model\Price::update($arExistingPrice['ID'], $arFields);
                unset($arExistingPrices[$name]);
            } else {
                $arFields = [
                    'PRODUCT_ID' => intval($PRODUCT_ID),
                    'CATALOG_GROUP_ID' => $this->arPrices[$name]['ID'],
                    'PRICE' => $price,
                    'CURRENCY' => $currency,
                ];
                $result = \Bitrix\Catalog\Model\Price::add($arFields);
            }
        }

        //А это все можно удалить...
        foreach ($arExistingPrices as $arExistingPrice) {
            \Bitrix\Catalog\Model\Price::delete($arExistingPrices['ID']);
        }
    }

    protected function applyAmounts($PRODUCT_ID, $arExistingAmounts, $arAmounts)
    {
        foreach ($arAmounts as $id => $amount) {
            if(array_key_exists($id, $arExistingAmounts)) {
                $arExistingAmount = $arExistingAmounts[$id];
                if($arExistingAmount['AMOUNT'] == $amount) {
                    unset($arExistingAmounts[$id]);
                    continue;
                }
                $arFields = [
                    'AMOUNT' => $amount,
                ];
                $result = \Bitrix\Catalog\StoreProductTable::update($arExistingAmount['ID'], $arFields);
                unset($arExistingAmounts[$id]);
            } else {
                $arFields = [
                    'PRODUCT_ID' => intval($PRODUCT_ID),
                    'STORE_ID' => $this->arStores[$id]['ID'],
                    'AMOUNT' => $amount,
                ];
                $result = \Bitrix\Catalog\StoreProductTable::add($arFields);
            }
        }

        //А это все можно удалить...
        foreach ($arExistingAmounts as $arExistingAmount) {
            \Bitrix\Catalog\StoreProductTable::delete($arExistingAmount['ID']);
        }
    }

    protected function filterChangedProperties($arOriginal, $arNew, $arProperties)
    {
        foreach ($arNew as $code => $arProp) {
            if(!array_key_exists($code, $arOriginal)) {
                continue;
            }
            if(array_key_exists($code, $arProperties)) {
                $arProperty = $arProperties[$code];
                if($arProperty['PROPERTY_TYPE'] == 'L') {
                    $arOrig = $arOriginal[$code]['VALUE_ENUM_ID'];
                } else {
                    $arOrig = $arOriginal[$code]['VALUE'];
                }
            } else {
                $arProperty = $code;
                $arOrig = $arOriginal[$code];
            }

            if(!$this->isChangedProperty($arOrig, $arProp, $arProperty)) {
                unset($arNew[$code]);
            }
        }
        return $arNew;
    }

    protected function isChangedProperty($arOriginal, $arNew, $arProperty = false)
    {
        if(empty($arProperty)) {
            $arProperty = [
                'MULTIPLE' => 'N',
                'PROPERTY_TYPE' => 'S',
            ];
        } else if(is_string($arProperty)) {
            if(substr($arProperty, -8) == '_PICTURE') {
                $arProperty = [
                    'MULTIPLE' => 'N',
                    'PROPERTY_TYPE' => 'F',
                ];
            } else {
                $arProperty = [
                    'MULTIPLE' => 'N',
                    'PROPERTY_TYPE' => 'S',
                ];
            }
        }

        $arOriginal = $this->unifyProp($arOriginal, $arProperty);
        $arNew = $this->unifyProp($arNew, $arProperty);

        if($arProperty['PROPERTY_TYPE'] == 'F') {

            if($this->bFileForceReload) {
                return true;
            }

            if(empty($arNew)) {
                return !empty($arOriginal);
            }

            if(count($arNew) != count($arOriginal)) {
                return true;
            }

            if(!static::FILE_HASH_COMPARE) {
                return false;
            }

            $arOriginalHashes = [];
            foreach ($arOriginal as $arFile) {
                $file = $_SERVER['DOCUMENT_ROOT'] . $arFile['SRC'];
                //static ::dump([$file, $arFile]);
                if(!file_exists($file)) {
                    //static ::dump('NOT exists!');
                    return true;
                }
                $hash = $arFile["FILE_SIZE"] . '-' . hash_file('sha512', $file);
                $arOriginalHashes [] = $hash;
            }

            $arNewHashes = [];
            foreach ($arNew as $arFile) {
                $file = $arFile["tmp_name"];
                if(!file_exists($file)) {
                    //Should it be so!?
                    return false;
                }
                $hash = $arFile["size"] . '-' . hash_file('sha512', $file);
                $arNewHashes [] = $hash;
            }

            return (array_diff($arOriginalHashes, $arNewHashes) || array_diff($arNewHashes, $arOriginalHashes));
        }

        if(count($arOriginal) != count($arNew)) {
            return true;
        }

        if($arProperty['PROPERTY_TYPE'] == 'S' && $arProperty['USER_TYPE'] == 'HTML') {
            $arOriginalCopy = $arOriginal;
            $arNewCopy = $arNew;
            foreach ($arOriginalCopy as $originalCopy) {
                foreach ($arNewCopy as $j => $newCopy) {
                    if($originalCopy['TYPE'] == $newCopy['TYPE']
                        && $originalCopy['TEXT'] == $newCopy['TEXT']) {
                        unset($arNewCopy[$j]);
                        continue 2;
                    }
                }
                return true;
            }
        }

        if(empty(array_diff($arOriginal, $arNew)) && empty(array_diff($arNew, $arOriginal))) {
            return false;
        }

        return true;
    }

    protected function fixLinkIblock($property)
    {
        return false;
    }

    public function execute(...$params)
    {
        if($params) {
            $this->lastId = intval($params[0]);
        } else {
            $this->lastId = 0;
        }

        if(isset($params[1])) {
            $this->timestamp = intval($params[1]);
        } else {
            $this->timestamp = null;
        }

        if(isset($params[2])) {
            $this->bFileForceReload = !!($params[2]);
        } else {
            $this->bFileForceReload = false;
        }

        $this->arData = $this->readXml();
//gsv_dump($this->arData);die();
        $this->readXmlCatalog();
        //gsv_dump($this->arProducts);
        //gsv_dump($this->arOffers);
        $this->readCatalog();

        $this->applyXmlProperties();

        $arIds = $this->applyItems(static::PRODUCT_IBLOCK_ID, $this->arProducts, $this->arExistingProducts);

        $this->convertCml2Link($arIds);

        $this->applyItems(static::OFFER_IBLOCK_ID, $this->arOffers, $this->arExistingOffers);

        $this->applyColors($arIds);

        if($this->lastId === null) {
            return false;
        } else {
            return [$this->lastId, $this->timestamp, $this->bFileForceReload];
        }
    }

    public function executeInit(...$params)
    {
        $this->lastId = 0;
        $this->timestamp = null;
        $this->bFileForceReload = false;

        $this->arData = $this->readXml();

        $this->initXmlCatalog();
    }

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

        gsv_dump($message, true);
    }

    protected function makeFileArray($path)
    {
        if(is_string($path)) {
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
        }

        $arFile =  \CFile::MakeFileArray($path);
        //gsv_dump($arFile);
        //die();
        return $arFile;
    }
}