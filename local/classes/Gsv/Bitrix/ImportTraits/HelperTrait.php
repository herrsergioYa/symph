<?php

namespace Gsv\Bitrix\ImportTraits;

trait HelperTrait
{
    use ProductTrait;
    use PriceTrait;
    use StoreTrait;
    use IblockTrait;
    use SectionTrait;
    use PropertyTrait;
    use MiscTrait;

    /*

    //We DO initialize them at runtime!
    public const PRODUCT_IBLOCK_ID = CATALOG_IBLOCK_ID;
    public const OFFER_IBLOCK_ID = CATALOG_OFFERS_IBLOCK_ID;*.

     protected const PRODUCT_MAPPING = [
        'Артикул' => [
            'XML_ID',
            'PROPERTY_ARTICLE',
            'CODE',
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
            [
                'NAME' => 'Каталог',
                'UF_NAME_EN' => 'Catalog',
            ],
        ],
        'CATALOG' => [],
    ];

    public const FILE_HASH_COMPARE = false;

    const MAX_PREVIEW_TEXT_LEN = 50;

    public const TRANSLIT_LANG = 'ru';
    public const TRANSLIT_PARAMS = array(
        "max_len" => 100,
        "change_case" => 'L', // 'L' - toLower, 'U' - toUpper, false - do not change
        "replace_space" => '-',
        "replace_other" => '-',
        "delete_repeat_replace" => true,
        "safe_chars" => '',
    );

    */


    /**
     * @var array $arData - Raw data
     */
    protected $arData;

    /**
     * @var array $arProducts - Products extracted from the raw data
     */
    protected $arProducts;
    /**
     * @var array $arOffers - Offers extracted from the raw data
     */
    protected $arOffers;

    /**
     * @var array $arProductProps - Properties of a product
     */
    protected $arProductProps;
    /**
     * @var array $arOfferProps - Properties of an offer
     */
    protected $arOfferProps;

    /**
     * @var array $arExistingProducts - Products already being in the DB
     */
    protected $arExistingProducts;
    /**
     * @var array $arExistingOffers - Offers already being in the DB
     */
    protected $arExistingOffers;

    /**
     * @var ?array $arProductTemplate - Product template
     */
    protected $arProductTemplate = null;
    /**
     * @var ?array $arOfferTemplate - Offer template
     */
    protected $arOfferTemplate = null;

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
                    if(is_string($val)) {
                        $val = trim($val);
                    }
                }
                unset($val);
            } else if(is_string($value)) {
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

    /*protected static function merge($arProduct, $arOffer, $isOffer)
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
    }*/

    protected function initAll()
    {
        $this->arProductProps = $this->initProperties(static::PRODUCT_IBLOCK_ID,self::PRODUCT_MAPPING);
        $this->arOfferProps = $this->initProperties(static::OFFER_IBLOCK_ID,self::OFFER_MAPPING);

        $this->arProductSections = static::getSectionTree(static::PRODUCT_IBLOCK_ID, true);

        $this->initPrices(self::PRODUCT_MAPPING);
        $this->initPrices(self::OFFER_MAPPING);

        $this->initStores(self::PRODUCT_MAPPING);
        $this->initStores(self::OFFER_MAPPING);
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
                $arOffer = $this->adjustOffer($arOffer);
                $this->arOffers[$arOffer['XML_ID']] = $arOffer;
            }
            unset($arProduct['OFFERS']);

            $arProducts[$arProduct['XML_ID']] = $arProduct;
        }

        $this->arProducts = $arProducts;

        $this->adjustCatalog();
    }

    protected function readCatalog()
    {
        $this->arExistingProducts = $this->readIblockCatalog(
            static::PRODUCT_IBLOCK_ID,
            static::getFieldCodes(static::PRODUCT_MAPPING),
            static::getPropertyCodes(static::PRODUCT_MAPPING),
            array_keys($this->arProducts)
        );
        $this->arExistingOffers = $this->readIblockCatalog(
            static::OFFER_IBLOCK_ID,
            static::getFieldCodes(static::OFFER_MAPPING),
            static::getPropertyCodes(static::OFFER_MAPPING),
            array_keys($this->arOffers)
        );

        $this->readCatalogCatalog(
            $this->arExistingProducts,
            static::getProductCodes(static::PRODUCT_MAPPING),
            $this->arExistingOffers,
            static::getProductCodes(static::OFFER_MAPPING)
        );

        $this->readCatalogPrices($this->arExistingProducts);
        $this->readCatalogPrices($this->arExistingOffers);

        $this->readCatalogStoreAmount($this->arExistingProducts);
        $this->readCatalogStoreAmount($this->arExistingOffers);
    }

    protected function applyItems($IBLOCK_ID, $arItems, $arExistingItems, $arProps)
    {
        $arResult = [];

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
                $arElement = $this->fixProperties($arElement, false);
                if($arElement) {
                    $result = $this->getIblockElement()->Update($ID, $arElement);
                }

                $arProperties = $this->filterChangedProperties($arExistingProperties, $arProperties, $arProps);
                $arProperties = $this->fixProperties($arProperties, $arProps);
                if($arProperties) {
                    \CIBlockElement::SetPropertyValuesEx($ID, $IBLOCK_ID, $arProperties);
                }

            } else {

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

                $arElement = $this->fixProperties($arElement, false);
                $arProperties = $this->fixProperties($arProperties, $arProps);

                if($arProperties) {
                    //Only this time!!
                    $arElement['PROPERTY_VALUES'] = $arProperties;
                }

                $ID = $this->getIblockElement()->Add($arElement);

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

                $arExistingProduct = $arProduct;
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
            if($arExistingProduct['TYPE']
                && $arExistingProduct['TYPE'] != \Bitrix\Catalog\ProductTable::TYPE_PRODUCT
                && $arExistingProduct['TYPE'] != \Bitrix\Catalog\ProductTable::TYPE_OFFER
            ) {
                continue;
            }

            $this->applyPrices($ID, $arExistingPrices, $arPrices);
            $this->applyAmounts($ID, $arExistingAmounts, $arAmounts);
        }

        return $arResult;
    }

    protected function applyAll()
    {
        $this->arData = $this->readXml();
        $this->readXmlCatalog();

        $this->readCatalog();
return;
        $this->applySections($this->arProducts);

        $this->applyProperties($this->arProducts, $this->arProductProps);
        $this->applyProperties($this->arOffers, $this->arOfferProps);

        $arIds = $this->applyItems(static::PRODUCT_IBLOCK_ID, $this->arProducts, $this->arExistingProducts, $this->arProductProps);

        $this->convertCml2Link($arIds);

        $this->applyItems(static::OFFER_IBLOCK_ID, $this->arOffers, $this->arExistingOffers, $this->arOfferProps);

        return $arIds;
    }
    public static function asField($target)
    {
        if(static::asProp($target) || static::asPrice($target) || static::asProd($target) || static::asStoreAmount($target) || static::asExtra($target)) {
            return false;
        } else {
            return $target;
        }
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
}