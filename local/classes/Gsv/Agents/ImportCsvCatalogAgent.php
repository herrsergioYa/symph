<?php

namespace Gsv\Agents;



class ImportCsvCatalogAgent extends \Gsv\Bitrix\Agent
{
    public const DEBUG = false;
    public const FILE_NAME = 'local/import/Проект Ameno v.2 - Артикулы с атрибутами.csv';
    public const FILE_HASH_COMPARE = false;

    //We DO initialize them at runtime!
    public const PRODUCT_IBLOCK_ID = CATALOG_IBLOCK_ID;
    public const OFFER_IBLOCK_ID = CATALOG_OFFERS_IBLOCK_ID;

    protected $bFileForceReload = false;

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

    protected $arProductSections = [];

    protected $arProductProps = [];
    protected $arOfferProps = [];

    protected $arIblockCache = [];

    protected $arEnum = [];
    //protected $arEnumXmlId = [];
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
        'Артикул' => [
            'XML_ID',
            'PROPERTY_ARTICLE',
            'CODE',
        ],
        'Нейминг рус' => [
            'NAME',
        ],
        'Нейминг англ' => [
            'PROPERTY_NAME_EN',
        ],
        'Группировки (модели)' => [
            'PROPERTY_NAME_MODEL',
        ],
        'Название цвета' => [
            'PROPERTY_COLOR',
        ],
        'Код цвета' => [
            'EXTRA_COLOR_CODE',
        ],
        'Категория' => [
            'EXTRA_SECTION',
        ],
        'Category' => [
            'EXTRA_SECTION_EN',
        ],
        'Кампейн (в By Collection в меню)' => [
            'EXTRA_CAMPAIGN',
        ],
        'Описание RU' => [
            'PREVIEW_TEXT',
            'DETAIL_TEXT',
            'PROPERTY_DESCRIPTION_RU',
        ],
        'Описание ENG' => [
            'PROPERTY_DESCRIPTION_EN',
        ],
        'Состав ткани' => [
            'PROPERTY_COMPOSITE'
        ],
        'Размер' => [
            'EXTRA_SIZES'
        ],
        'Описание для ухода' => [
            "PROPERTY_WASHING"
        ],
        'Цена' => [
            'EXTRA_PRICES',
        ],
        'Категория товара (A,B,C)' => [
            'PROPERTY_CATEGORY',
        ],
        'Наличие?' => [
            'EXTRA_STOCK',
        ],
        '' => [
            'IBLOCK_SECTION_ID',
            'IBLOCK_SECTION',
        ],
    ];

    protected const OFFER_MAPPING = [
        'xmlId' => [
            'XML_ID'
        ],
        'product' => [
            'PROPERTY_CML2_LINK',
        ],
        'name' => [
            'NAME',
        ],
        'code' => [
            'CODE',
        ],
        'size' => [
            'PROPERTY_SIZE',
        ],
        'size_cup' => [
            'PROPERTY_SIZE_CUP',
        ],
        'size_skirt' => [
            'PROPERTY_SIZE_SKIRT',
        ],
        'size_top' => [
            'PROPERTY_SIZE_TOP',
        ],
        'quantity' => [
            'PRODUCT_QUANTITY',
            'STORE_1',
        ],
        'rub' => [
            'PRICE_BASE',
        ],
        'usd' => [
            'PRICE_USD',
        ],
    ];

    const SECTION_FIELDS = ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'UF_NAME_EN', 'UF_NAME_RU',];

    const ROOT_SECTIONS = [
        'SECTION' => [
            'NAME' => 'Каталог',
            'UF_NAME_EN' => 'Shop',
        ],
        'CAMPAIGN' => [
            'NAME' => 'Campaigns',
            'UF_NAME_EN' => 'Campaigns',
        ],
        'COLLECTION' => [
            'NAME' => 'Подборки',
            'UF_NAME_EN' => 'Collection',
        ],
    ];

    const MAX_PREVIEW_TEXT_LEN = 50;

    const YA_DISK_URL = 'https://cloud-api.yandex.net:443/v1/disk/public/resources';
    const YA_DISK_PUBLIC_KEY = 'https://yadi.sk/d/gisXAEUQ3XCc1Q';
    const PHOTO_UPDATE_CNT = 5;

    /** @var @var \CIBlock $obIblock */
    protected $obIblock = null;

    /** @var @var \CIBlockProperty $obProperty */
    protected $obProperty = null;

    /** @var @var \CIBlockPropertyEnum $obEnum */
    protected $obEnum = null;

    /** @var \CIBlockElement $obElement */
    protected $obElement = null;

    /** @var \CIBlockSection $obElement */
    protected $obSection = null;

    /** @var \CTextParser $obTextParser */
    protected $obTextParser = null;

    /** @var \Gsv\Util\Http\HttpClient $obHttp */
    protected $obHttp = null;

    /** @var int $lastId */
    protected $lastId = 0;

    public function getModulesToLoad(&$arModules)
    {
        parent::getModulesToLoad($arModules);
        $arModules[] = 'iblock';
        $arModules[] = 'catalog';
        $arModules[] = 'sale';
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

    public static function asExtra($target)
    {
        $pos = strpos($target, 'EXTRA_');
        if($pos === 0) {
            return substr($target, strlen('EXTRA_'));
        } else {
            return false;
        }
    }

    public static function asField($target)
    {
        if(static::asProp($target) || static::asPrice($target) || static::asProd($target) || static::asStoreAmount($target) || static::asExtra($target)) {
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
                'EXTRA' => [],
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
            if(is_array($value)) {
                foreach ($value as &$val) {
                    $val = trim($val);
                }
                unset($val);
            } else {
                $value = trim($value);
            }
            $data[] = [
                $key,
                $value,
            ];
        }

        foreach ($data as $pair) {
            list($key, $val) = $pair;
            if(!array_key_exists($key, $MAPPING)) {
                if(isset($arItem['EXTRA']['PRICES']) && $key) {
                    //HACK: These are the last columns
                    if($val) {
                        $arItem['EXTRA']['COLLECTION'][] = $key;
                    }
                }
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
                } else if ($extra = static::asExtra($target)) {
                    $arItem['EXTRA'][$extra] = $val;
                } else { //Field?
                    $arItem[$target] = $val;
                }
            }
        }
        return $arItem;
    }

    protected function adjustProduct($arProduct)
    {
        $this->adjustSections($arProduct);
        $arProduct['OFFERS'] = $this->createOffers($arProduct);

        $arProduct['CODE'] = static::translit($arProduct['CODE']);

        if($this->obTextParser === null)
            $this->obTextParser = new \CTextParser();
        $arProduct['PREVIEW_TEXT'] = $this->obTextParser->html_cut($arProduct['PREVIEW_TEXT'], static::MAX_PREVIEW_TEXT_LEN);

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
        static $arSect = null;// ['SECTION', 'CAMPAIGN', 'COLLECTION',];
        static $postfixes = ['_EN'];
        if($arSect === null) {
            $arSect = array_keys(static::ROOT_SECTIONS);
        }

        foreach ($arSect as $sectGroup) {
            foreach (['', ...$postfixes] as $postfix) {
                $sect = $sectGroup . $postfix;
                if(isset($arProduct['EXTRA'][$sect])) {
                    if(is_array($arProduct['EXTRA'][$sect])) {
                        continue;
                    }
                    $arProduct['EXTRA'][$sect] = trim($arProduct['EXTRA'][$sect]);
                    if($arProduct['EXTRA'][$sect] == '' || $arProduct['EXTRA'][$sect] == '-') {
                        $arProduct['EXTRA'][$sect] = [];
                        continue;
                    }
                    $arProduct['EXTRA'][$sect] = preg_split("/\n/", $arProduct['EXTRA'][$sect]);
                }
            }

            $arSections = [];
            foreach ($arProduct['EXTRA'][$sectGroup] as $lvl => $sectName) {

                //HACK: It should be ...
                $sectName = $this->fixSectionName($sectName);

                if($sectName == '') {
                    continue;
                }

                $arSection = [
                    'NAME' => trim($sectName),
                    //'UF_NAME_EN' => trim($arProduct['EXTRA']['SECTION_EN'][$lvl]),
                ];

                foreach ($postfixes as $postfix) {
                    $sectName = trim($arProduct['EXTRA'][$sectGroup . $postfix][$lvl]);
                    if(empty($sectName)) {
                        $sectName = $arSection['NAME'];
                    }
                    $arSection['UF_NAME' . $postfix] = $sectName;
                    unset($arProduct['EXTRA'][$sectGroup . $postfix]);
                }

                $arSections[$lvl] = $arSection;
            }

            $arProduct['EXTRA'][$sectGroup] = $arSections;
        }
    }

    protected function fixSectionName($sectName)
    {
        //HACK: It should be ...
        $sectName = trim($sectName);
        $sectName = trim($sectName, '-+.');
        $sectName = ltrim($sectName, '-+.,');
        $sectName = preg_replace('/\s+/', ' ',  $sectName);
        //$sectName = mb_ucfirst($sectName);
        $sectName = mb_strtoupper(mb_substr($sectName, 0, 1)) . mb_substr($sectName, 1);

        return $sectName;
    }

    protected function createOffers($arProduct)
    {
        $arOffers = [];

        $arPrices = $arProduct['EXTRA']['PRICES'];
        $arPrices = trim($arPrices);
        $arPrices = preg_split("/\n/", $arPrices);

        $arProduct['EXTRA']['PRICES'] = [];
        foreach ($arPrices as $price) {
            $price = trim($price);
            $matches = [];
            if(preg_match('/^([\s\S]+)\$$/', $price, $matches)) {
                $price = $matches[1];
                $currency = 'usd';
            } else {
                $currency = 'rub';
            }

            $price = preg_replace('/[^\d\.\,]+/', '', $price);

            $arProduct['EXTRA']['PRICES'][$currency] = [
                'PRICE' => $price,
                'CURRENCY' => strtoupper($currency),
            ];
        }

        $arStock = $arProduct['EXTRA']['STOCK'];
        $stock = trim($arStock);

        $arProduct['EXTRA']['STOCK'] = [];
        //foreach ($arStock as $stock) {
            if($arSizes = $this->preparseSizes($stock)) {
                foreach ($arSizes as $arSize) {
                    $arProduct['EXTRA']['STOCK'][''][''][$arSize['size_skirt']][$arSize['size_top']] = true;
                }
            } else {
                $arSizes = $this->parseSizes($stock);
                foreach ($arSizes as $arSize) {
                    if ($arSize['size_top'] || $arSize['size_skirt']) {
                        continue;//Impossible for now
                    }
                    $arProduct['EXTRA']['STOCK'][$arSize['size']][$arSize['size_cup']][''][''] = true;
                    //HACK: For preSizes
                    if ($arSize['size_cup'] == '') {
                        foreach ($arSizes as $arSize2) {
                            if ($arSize2['size_top'] || $arSize2['size_skirt']) {
                                continue;//Impossible for now
                            }
                            if ($arSize2['size_cup']) {
                                continue;//Impossible if the data are correct?
                            }
                            $arProduct['EXTRA']['STOCK'][''][''][$arSize['size']][$arSize2['size']] = true;
                        }
                    }
                }
            }
        //}

        $arSizes = $arProduct['EXTRA']['SIZES'];
        $arSizes = trim($arSizes);

        $arSizes = $this->preparseSizes($arSizes) ?: $this->parseSizes($arSizes);

        foreach ($arSizes as $arSize) {
            $arOffer = $arSize;

            $arOffer['name'] = $arProduct['NAME'];// . ' (' . $arOffer['size'] . ', ' . $arOffer['size_cup'] . ')';
            $arOffer['xmlId'] = $arProduct['XML_ID'];// . '#' . $arOffer['size'] . '_' .  $arOffer['size_cup'];
            if($arSize['size']/* || $arOffer['size_cup']*/) {
                $arOffer['name'] .= ' (' . $arOffer['size'] . ($arOffer['size_cup'] ? ', ' . $arOffer['size_cup'] : '') . ')';
                $arOffer['xmlId'] .= '#' . $arOffer['size'] . ($arOffer['size_cup'] ? '_' . $arOffer['size_cup'] : '');
            }
            if($arSize['size_skirt'] || $arOffer['size_top']) {
                $arOffer['name'] .= ' [' . $arOffer['size_skirt'] . ', ' . $arOffer['size_top'] . ']';
                $arOffer['xmlId'] .= '@' . $arOffer['size_skirt'] . '_' .  $arOffer['size_top'];
            }
            $arOffer['code'] = static::translit($arOffer['name']);
            $arOffer['product'] = $arProduct['XML_ID'];

            $arOffer += $arProduct['EXTRA']['PRICES'];
            if($arProduct['EXTRA']['STOCK'][$arOffer['size']][$arOffer['size_cup']][$arOffer['size_skirt']][$arOffer['size_top']]) {
                $arOffer['quantity'] = static::IN_STOCK_QUANTITY;
            } else {
                $arOffer['quantity'] = 0;
            }

            $arOffer = $this->map($this->arOfferTemplate, $arOffer, $this->arOfferProps, static::OFFER_MAPPING);
            $arOffer = $this->adjustOffer($arOffer);

            $arOffers[] = $arOffer;
        }

        return $arOffers;
    }

    protected function preparseSizes($arSizes)
    {
        $arSizes = trim($arSizes);
        $arSizes = mb_strtolower($arSizes);
        if(preg_match('/(юбка|топ)\s*-/',$arSizes)) {
            $arTemp = [
                'size_skirt' => [],
                'size_top' => [],
            ];
            foreach(preg_split("/(\n)+/", $arSizes) as $arSize) {
                if(preg_match('/юбка\s*-/', $arSize)) {
                    $arSize = preg_replace('/юбка\s*-/', '', $arSize);
                    $arSize = trim($arSize);
                    foreach(explode(',', $arSize) as $size) {
                        $size = trim($size);
                        $arTemp['size_skirt'][] = mb_strtoupper($size);
                    }
                } else if(preg_match('/топ\s*-/', $arSize)) {
                    $arSize = preg_replace('/топ\s*-/', '', $arSize);
                    $arSize = trim($arSize);
                    foreach(explode(',', $arSize) as $size) {
                        $size = trim($size);
                        $arTemp['size_top'][] = mb_strtoupper($size);
                    }
                }
            }
            if(empty($arTemp['size_skirt'])) {
                $arTemp['size_skirt'][] = '';
            }
            if(empty($arTemp['size_top'])) {
                $arTemp['size_top'][] = '';
            }

            $arResult = [];
            foreach ($arTemp['size_skirt'] as $skirtSize) {
                foreach ($arTemp['size_top'] as $topSize) {
                    $arResult[] = [
                        'size' => '',
                        'size_cup' => '',
                        'size_skirt' => $skirtSize,
                        'size_top' => $topSize,
                    ];
                }
            }
            return $arResult;
        }
        return false;
     }

    protected function parseSizes($arSize)
    {
        $arTemp = preg_split("/(\n)+/", $arSize);
        $arSizes = [];
        foreach ($arTemp as $arSize) {
            $arSizes = array_merge($arSizes, $this->parseSize($arSize));
        }
        return $arSizes;
    }

    protected function parseSize($arSize)
    {
        $arSize = trim($arSize, "-, \n\t\r\v\0");//Yeah... It can be.
        $arSize = mb_strtolower($arSize);

        $arResult = [];

        $matches = [];
        //TODO: "s m" should be "s,m" or "s-m"? It changes the regexes...
        //I did avoid some changes because I do not know for sure... So it is redundant.
        if(preg_match('/^([\s\S]+?)\s*[,\-\s]+\s*([a-g])$/', $arSize, $matches)) {
            $arItem = [
                'size' => '',
                'size_cup' => mb_strtoupper(trim($matches[2])),
                'size_skirt' => '',
                'size_top' => '',
            ];
            foreach (preg_split('/[,\s]+/', $matches[1]) as $size) {
                $size = trim($size);
                if($size == '') {//Impossible if we do use the lenient quantifier?
                    continue;
                }
                $arItem['size'] = mb_strtoupper($size);
                $arResult[] = $arItem;
            }
        } else if($arSize != '') {
            $arItem = [
                'size' => '',
                'size_cup' => '',
                'size_skirt' => '',
                'size_top' => '',
            ];
            foreach (preg_split('/[,\s]+/', $arSize) as $size) {
                $size = trim($size);
                if($size == '') {//Impossible?
                    continue;
                }
                $arItem['size'] = mb_strtoupper($size);
                $arResult[] = $arItem;
            }
        }

        return $arResult;
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

    protected function applyXmlProperties()
    {
        $this->applyProperties($this->arProducts, $this->arProductProps);
        $this->applyProperties($this->arOffers, $this->arOfferProps);
    }

    protected function applyProperties(&$arItems, $arProperties)
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
                $value = $this->applyProperty($value, $arProperty, $arItem);
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

    protected function applyXmlSections()
    {
        $arTree = [
            'ID' => 0,
            'CHILDREN' => &$this->arProductSections,
        ];
        $IBLOCK_ID = static::PRODUCT_IBLOCK_ID;
        foreach ($this->arProducts as &$arProduct) {
            $arProduct['IBLOCK_SECTION'] = [];
            $arProduct['IBLOCK_SECTION_ID'] = '';
            foreach (array_keys(static::ROOT_SECTIONS) as $sect) {
                foreach ($arProduct['EXTRA'][$sect] as $arSection) {
                    $arSections = [];
                    $arSections[] = static::ROOT_SECTIONS[$sect];
                    $arSections[] = $arSection;
                    if ($ID = $this->applySections($arTree, $arSections, $IBLOCK_ID)) {
                        $arProduct['IBLOCK_SECTION'][] = $ID;
                        if (empty($arProduct['IBLOCK_SECTION_ID'])) {
                            $arProduct['IBLOCK_SECTION_ID'] = $ID;
                        }
                    }
                }
            }
        }
    }

    protected function applySections(&$arTree, $arSections, $IBLOCK_ID)
    {
        foreach ($arSections as $arSection) {
            $name = static::clearEnumValue($arSection['NAME']);
            foreach ($arTree['CHILDREN'] as &$arNode) {
                $nodeName = static::clearEnumValue($arNode['NAME']);
                if($nodeName == $name) {
                    $arTree = &$arNode;
                    continue 2;
                }
            }

            if($this->obSection === null) {
                $this->obSection = new \CIBlockSection();
            }

            $arSection['IBLOCK_SECTION_ID'] = $arTree['ID'];
            $arSection['IBLOCK_ID'] = $IBLOCK_ID;//static::PRODUCT_IBLOCK_ID;//TODO: Make a parameter
            $arSection['CODE'] = static::translit($arSection['NAME']);
            $arSection['ID'] = $this->obSection->Add($arSection);
            if(empty($arSection['ID'])) {
                $this->log("Error: %s during processing %s", [$this->obSection->LAST_ERROR, $arSection,]);
            }
            $arSection['CHILDREN'] = [];
            $arTree['CHILDREN'][$arSection['ID']] = $arSection;
            $arTree = &$arTree['CHILDREN'][$arSection['ID']];
        }

        return $arTree['ID'];
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

    protected static function getSectionTree($IBLOCK_ID, $tree = true)
    {
        $arSections = [];

        $dbSections = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
            ],
            false,
            static::SECTION_FIELDS,
        );

        while($arSection = $dbSections->Fetch()) {
            if($tree) {
                $arSection['CHILDREN'] = [];
            }
            $arSections[$arSection['ID']] = $arSection;
            if($tree && $arSection['IBLOCK_SECTION_ID']) {
                $arSections[$arSection['IBLOCK_SECTION_ID']]['CHILDREN'][$arSection['ID']] = &$arSections[$arSection['ID']];
            }
        }

        if($tree) {
            foreach ($arSections as $id => $arSection) {
                if($arSection['IBLOCK_SECTION_ID']) {
                    unset($arSections[$id]);
                }
            }
        }

        return $arSections;
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

    protected function findEnumProperty($value, $arProperty, $arItem)
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

            $this->adjustEnumPropertyFields($arFields, $value, $arProperty, $arItem);

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

    protected function adjustEnumPropertyFields(&$arFields, $value, $arProperty, &$arItem)
    {
        if(isset($this->arEnumXmlId[$arProperty['ID']][$value])) {
            $arFields['XML_ID'] = $this->arEnumXmlId[$arProperty['ID']][$value];
        }
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

    protected function findIblockProperty($value, $arProperty, &$arItem)
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

            $this->adjustIblockPropertyFields($arFields, $value, $arProperty, $arItem);

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
        return true;
    }

    protected function adjustIblockPropertyFields(&$arFields, $value, $arProperty, &$arItem)
    {
        if($arProperty['CODE'] == 'COLOR' && $arItem['EXTRA']['COLOR_CODE']) {
            $arFields['PROPERTY_VALUES']['COLOR_CODE'] = $arItem['EXTRA']['COLOR_CODE'];
        }
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

        $this->arProductSections = static::getSectionTree(static::PRODUCT_IBLOCK_ID, true);

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
            return str_replace('ё', 'е', preg_replace('/\s+/', ' ', mb_strtolower(trim($value))));
        } else {
            return $value;
        }
    }

    protected function readXml($short = false)
    {
        $FILE = $_SERVER['DOCUMENT_ROOT'] . '/' . static::FILE_NAME;

        $arResult = [];

        $csvFile = new \CCSVData('R', false);
        $csvFile->LoadFile($FILE);
        $csvFile->SetDelimiter(',');
        $arHeader = $csvFile->Fetch();
        //$arHeader = array_flip($arHeader);
        while ($arRes = $csvFile->Fetch()) {
            $arLine = [];
            foreach ($arRes as $k => $v) {
                $arLine[$arHeader[$k]] = $v;
            }
            $arResult[] = $arLine;
        }

        return $arResult;
    }

    protected function readXmlCatalog()
    {
        $this->arProducts = [];
        $this->arOffers = [];

        $arProducts = [];

        foreach ($this->arData as $arItem) {

            $arProduct = $this->map($this->arProductTemplate, $arItem, $this->arProductProps, static::PRODUCT_MAPPING);
            $arProduct = $this->adjustProduct($arProduct);

            foreach ($arProduct['OFFERS'] as $arOffer) {
                $this->arOffers[$arOffer['XML_ID']] = $arOffer;
            }
            unset($arProduct['OFFERS']);

            $arProducts[$arProduct['XML_ID']] = $arProduct;
        }

        $this->arProducts = $arProducts;

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

            } else if($k == 'OFFERS') {

            } else if($k == 'PRODUCT') {
                $arResult['PRODUCT'] = $v;
            } else if($k == 'STORES') {

            //} else if($k == 'QUANTITY') {

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
//            } else if($k == 'QUANTITY') {
//                if($isOffer) {
//                    $arResult['QUANTITY'] = $v;
//                }
            } else {
                $arResult["OFFER_$k"] = $v;
            }
        }
        return $arResult;
    }

    /*protected function initXmlCatalog()
    {
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

        if(isset($MAPPING[$srcCode])) {
            $arMappings = $MAPPING[$srcCode];
        } else {
            return true;
        }

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

        return true;
    }*/

    protected function getIblock($IBLOCK_ID)
    {
        if(!isset($this->arIblockCache[$IBLOCK_ID])) {
            $dbIblock = \CIBlock::GetByID($IBLOCK_ID);
            $this->arIblockCache[$IBLOCK_ID] = $dbIblock->Fetch();
        }
        return $this->arIblockCache[$IBLOCK_ID];
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
        $needsAllSections = in_array('IBLOCK_SECTION', $arSelect);

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
            if($needsAllSections) {
                $arItem['IBLOCK_SECTION'] = [];
            }
            $arItems[$arItem['ID']] = $arItem;
        }

        if($arItems) {

            if($needsAllSections) {
                $dbSections = \CIBlockElement::GetElementGroups(
                    array_keys($arItems),
                    true,
                    ['ID', 'IBLOCK_ELEMENT_ID']
                );
                while($arSection = $dbSections->Fetch()) {
                    $arItems[$arSection['IBLOCK_ELEMENT_ID']]['IBLOCK_SECTION'][] = $arSection['ID'];
                }
            }

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

    protected function applyProperty($value, $arProperty, &$arItem) {

        $value = $this->unifyProp($value, $arProperty);

        foreach ($value as $i => &$val) {
            if($arProperty['PROPERTY_TYPE'] == 'L') {
                $val = $this->findEnumProperty($val, $arProperty, $arItem);
            } else if($arProperty['PROPERTY_TYPE'] == 'E') {
                $val = $this->findIblockProperty($val, $arProperty, $arItem);
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

    protected function applyItems($IBLOCK_ID, $arItems, $arExistingItems, $arProps)
    {
        $arResult = [];
        /*if($IBLOCK_ID == static::PRODUCT_IBLOCK_ID) {
            $arProps = $this->arProductProps;
        } else if($IBLOCK_ID == static::OFFER_IBLOCK_ID) {
            $arProps = $this->arOfferProps;
        } else {
            $arProps = [];
        }*/

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
                unset($arElement['EXTRA']);

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
                unset($arElement['EXTRA']);

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
                    /*if(!is_array($originalCopy)) {
                        $originalCopy = [
                            'TYPE' => 'HTML',
                            'TEXT' => $originalCopy,
                        ];
                    }
                    if(!is_array($newCopy)) {
                        $newCopy = [
                            'TYPE' => 'HTML',
                            'TEXT' => $newCopy,
                        ];
                    }*/
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
        $this->arData = $this->readXml();
        $this->readXmlCatalog();

        $this->readCatalog();

        $this->applyXmlSections();
        $this->applyXmlProperties();

        $arIds = $this->applyItems(static::PRODUCT_IBLOCK_ID, $this->arProducts, $this->arExistingProducts, $this->arProductProps);

        $this->convertCml2Link($arIds);

        $this->applyItems(static::OFFER_IBLOCK_ID, $this->arOffers, $this->arExistingOffers, $this->arOfferProps);

        $postProcessing = !!$params[0];
        $forceYaImg = !!$params[1];
        if($postProcessing) {
            static::registerYaImg([0, $forceYaImg, 5], 'N', 1);
            AdjustCsvColorAgent::register([0, false, 15], 'N', 1);
            return false;
        }

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
            $this->lastId = intval($params[0]);
        } else {
            $this->lastId = 0;
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
                '>ID' => $this->lastId,
            ],
            false,
            ['nTopCount' => $limit],
            ['ID', 'IBLOCK_ID']
        );
        $arElements = [];
        $this->lastId = null;
        while($arElement = $dbElements->Fetch()) {
            $this->lastId = intval($arElement['ID']);
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

        if($this->lastId === null) {
            return false;
        } else {
            return [$this->lastId, $this->bFileForceReload, $limit];
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

    protected function listYaDisk($path)
    {
        $params = [
            'public_key' => static::YA_DISK_PUBLIC_KEY,
            'path' => $path,
            'limit' => 50,
            'offset' => 0,
        ];
        $arResult = [];
        while(true) {
            $data = $this->getYaDisk('', $params);
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

    protected function getHttp()
    {
        if($this->obHttp === null) {
            $this->obHttp = new \Gsv\Util\Http\HttpClient(true, true);
        }
        return $this->obHttp;
    }
}