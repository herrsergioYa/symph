<?
namespace Gsv\Sale\Delivery;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;

//TODO: It is RAW!!
class Delivery extends DeliveryChild
{
    public function __construct(array $initParams)
    {
        parent::__construct($initParams);

    }

    public static function getClassTitle()
    {
        return parent::getClassTitle() ?: 'Новая служба доставки';
    }

    public static function getClassDescription()
    {
        return parent::getClassDescription() ?: 'Новая служба доставки';
    }

    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
    {
        $result = new CalculationResult();
        //$result->addError(new \Bitrix\Main\Error('Доставка не возможна'));

        $location = $this->getLocationCode($shipment);

        if(empty($location))
        {
            return parent::calculateConcrete($shipment);
        }

        //$hlblock = $this->config['MAIN']["HLBLOCK"];
        //$hlblock = '\\' . $hlblock . 'Table';

        $arTariff = $this->findLocation($location, $this->config);

        if(empty($arTariff))
        {
            return parent::calculateConcrete($shipment);
        }

        $weight = $shipment->getWeight();
        $price = static::findShipmentPrice($weight, $arTariff);

//        $arTarget = \Bitrix\Sale\Location\LocationTable::getByCode($location)->fetch();
//        $arCenter = \Bitrix\Sale\Location\LocationTable::getByCode($this->config['MAIN']["CENTER_LOCATION"])->fetch();
//
//        if(empty($arTarget) || empty($arCenter))
//        {
//            return parent::calculateConcrete($shipment);
//        }
//
//        $distance = static::distanceFromGps($arCenter, $arTarget);
//
//        if($distance > $this->config['MAIN']["MAX_DISTANCE"])
//        {
//            return parent::calculateConcrete($shipment);
//        }
//
//        $price = $this->getOrderPrice($shipment);
//
//        if($price < $this->config['MAIN']["MIN_PRICE"])
//        {
//            $bFree = false;
//        }
//        elseif (in_array($location, $arFreeLocations))
//        {
//            $bFree = true;
//        }
//        elseif (in_array($location, $arPaidLocations))
//        {
//            $bFree = false;
//        }
//        else
//        {
//            $bFree = false;
//        }
//
//        if($bFree)
//        {
//            $result->setDeliveryPrice(0);
//        }
//        else
        {
          //  $weight = $shipment->getWeight() / 1000.0;
        //    $price = $weight * $this->config['MAIN']["PRICE_FOR_WEIGHT"];
            $result->setDeliveryPrice($price);
        }

        return $result;
    }

    protected function getConfigStructure()
    {
        $currency = $this->currency;

        if(Loader::includeModule('currency'))
        {
            $currencyList = CurrencyManager::getCurrencyList();
            if (isset($currencyList[$this->currency]))
                $currency = $currencyList[$this->currency];
            unset($currencyList);
        }

        $return = array(
            "MAIN" => array(
                "TITLE" => 'Настройка обработчика доставки',
                "DESCRIPTION" => 'Настройка обработчика доставки',
                "ITEMS" => array(
//                    'HLBLOCK' => [
//                        'TYPE' => 'STRING',
//                        'NAME' => 'Highloadblock',
//                        'READONLY' => false,
//                        'DEFAULT' => 'DeliveryZone',
//                    ],

                    "CURRENCY" => array(
                        "TYPE" => "DELIVERY_READ_ONLY",
                        "NAME" => 'Валюта',
                        "VALUE" => $this->currency,
                        "VALUE_VIEW" => $currency
                    ),


                )
            )
        );
        foreach ($this->getAllLocationGroups() as $arGroup)
        {
            $key = $arGroup['ID'];
            $name = $arGroup['NAME'] ?: $arGroup['CODE'];
            $this->fillConfig($return, $key, $name);
        }
        $this->fillConfig($return, 0, "'По умолчанию'");
        return $return;
    }

    public static function getWeights($arTariff)
    {
        $arWeights = [];
        foreach ($arTariff as $key => $value)
        {
            $matches = [];
            if($key != 'UF_WEIGHT_STEP' && preg_match('/^UF_WEIGHT($|_.*$)/', $key, $matches))
            {
                $type = $matches[1];
                if(isset($arTariff['UF_PRICE' . $type]))
                {
                    $value = intval($value);
                    $arWeights[$value] = floatval($arTariff['UF_PRICE' . $type]);
                }
            }
        }
        ksort($arWeights);
        return $arWeights;
    }

    public static function getShipmentPrice($weight, $arWeights)
    {
        $weights = array_keys($arWeights);
        $index = static::lower_bound($weight, $weights);
        if(array_key_exists($index, $weights))
        {
            return $arWeights[$weights[$index]];
        }
        else
        {
           return false;
        }
    }

    public static function findShipmentPrice($weight, $arTariff)
    {
        $arWeights = static::getWeights($arTariff);
        $price = static::getShipmentPrice($weight, $arWeights);
        if($price === false)
        {
            $delta = $weight - \array_key_last($arWeights);
            $price = $delta / $arTariff['UF_WEIGHT_STEP'];
            if ($arTariff['UF_ROUND'])
                $price = ceil($price);
            $price *= $arTariff['UF_PRICE_STEP'];
            $price += \array_last($arWeights);
        }
        return $price;
    }

    public static function lower_bound($weight, $weights)
    {
        list($a, $b) = [0, count($weights)];
        while($a < $b)
        {
            $c = intdiv($b + $a, 2);
            if(!isset($weights[$c]))
            {
                return false;
            }
            if($weights[$c] < $weight)
            {
                $a = $c + 1;
            }
            else
            {
                $b = $c;
            }
        }
        return $a;
    }

    public static function getAllLocations($location)
    {
        $arLocations = [];

        $dbLocations = \Bitrix\Sale\Location\LocationTable::getList([
            'filter' => [
                '=CHILD.CODE' => $location,
            ],
            'order' => ['LEFT_MARGIN' => 'DESC'],
            'select' => [
                'CODE', 'ID'
            ],
            'runtime' => [
                'CHILD' => array(
                    'data_type' => 'Bitrix\Sale\Location\Location',
                    'reference' => array(
                        '>=ref.LEFT_MARGIN' => 'this.LEFT_MARGIN',
                        '<=ref.RIGHT_MARGIN' => 'this.RIGHT_MARGIN'
                    )
                ),
            ]
        ]);

        while($arLocation = $dbLocations->fetch())
        {
            $arLocations[] = $arLocation['CODE'];
        }
        return $arLocations;

//        $arLocation = \Bitrix\Sale\Location\LocationTable::getRow([
//            'filter' => [
//                '=CODE' => $location
//            ],
//            'select' => [
//                'LEFT_MARGIN', 'RIGHT_MARGIN'
//            ]
//        ]);
//        if($arLocation)
//        {
//            $dbLocations = \Bitrix\Sale\Location\LocationTable::getList([
//                'filter' => [
//                    '<=LEFT_MARGIN' => $arLocation['LEFT_MARGIN'],
//                    '>=RIGHT_MARGIN' => $arLocation['RIGHT_MARGIN'],
//                ],
//                'order' => ['LEFT_MARGIN' => 'DESC'],
//                'select' => [
//                    'CODE', 'ID'
//                ]
//            ]);
//            while($arLocation = $dbLocations->fetch())
//            {
//                $arLocations[] = $arLocation['CODE'];
//            }
//        }
//        return $arLocations;
    }

    protected function getAllLocationGroups()
    {
        $arGroups = [];
        $dbLoc = \Bitrix\Sale\Location\GroupLocationTable::getList([
            'filter' => ['GROUP.NAME.LANGUAGE_ID' => LANGUAGE_ID],
            'select' => ['LOCATION_ID', 'LOCATION_GROUP_ID', 'GROUP.*','GROUP.NAME.*', 'NAME' => 'GROUP.NAME.NAME']
        ]);
        while($obLoc = $dbLoc->fetchObject())
        {
            $arGroups[$obLoc->getLocationGroupId()] = $obLoc->getGroup()->collectValues();
            $arGroups[$obLoc->getLocationGroupId()]['NAME'] = $obLoc->getGroup()->getName()->getName();
        }
        return $arGroups;
    }

    protected function findLocation(string $location, array $config)
    {
        $locations = static::getAllLocations($location);

        $arLocations = [];

        $dbLoc = \Bitrix\Sale\Location\GroupLocationTable::getList([
            'filter' => [
                '=LOCATION.CODE' => $locations,
            ],
            'order' => [
                'GROUP.SORT' => 'ASC',
            ],
            'select' => [
             //   'GROUP',
                'CODE' => 'LOCATION.CODE',
                'ID' => 'GROUP.ID',
            ]
        ]);
        while($arLoc = $dbLoc->fetch())
        {
            $arLocations[$arLoc['CODE']][] = $arLoc['ID'];
        }
        uksort($arLocations, function ($a, $b) use($locations) {
            return array_search($a, $locations) <=> array_search($b, $locations);
        });
        $index = 0;
        while(is_array($arLoc = array_shift($arLocations)))
        {
            while($arLoc)
            {
                $index = intval(array_shift($arLoc));
                if($index > 0)
                {
                    break 2;
                }
            }
        }
        if($index <= 0)
        {
            $index = 0;
        }
        return [
            'UF_WEIGHT' => $config['MAIN'][$index . '_W'],
            'UF_PRICE' => $config['MAIN'][$index . '_P'],
            'UF_WEIGHT_2' => $config['MAIN'][$index . '_W2'],
            'UF_PRICE_2' => $config['MAIN'][$index . '_P2'],
            'UF_WEIGHT_STEP' => $config['MAIN'][$index . '_WS'],
            'UF_PRICE_STEP' => $config['MAIN'][$index . '_PS'],
            'UF_ROUND' => $config['MAIN'][$index . '_R'] == 'Y',
        ];
    }

    protected function fillConfig(array &$return, $key, $name)
    {
        $ret = &$return['MAIN']['ITEMS'];
        $ret[$key . '_W'] = [
            "TYPE" => "NUMBER",
            "MIN" => 0,
            "NAME" => 'Первая граница веса для ' . $name . ', г',
            'READONLY' => false,
            'DEFAULT' => '500',
        ];
        $ret[$key . '_P'] = [
            "TYPE" => "NUMBER",
            "MIN" => 0,
            "NAME" => 'Цена доставки для первой границы веса ' . $name . ', г',
            'READONLY' => false,
            'DEFAULT' => '420',
        ];
        $ret[$key . '_W2'] = [
            "TYPE" => "NUMBER",
            "MIN" => 0,
            "NAME" => 'Вторая граница веса для ' . $name . ', г',
            'READONLY' => false,
            'DEFAULT' => '1000',
        ];
        $ret[$key . '_P2'] = [
            "TYPE" => "NUMBER",
            "MIN" => 0,
            "NAME" => 'Цена доставки для второй границы веса ' . $name,
            'READONLY' => false,
            'DEFAULT' => '540',
        ];
        $ret[$key . '_WS'] = [
            "TYPE" => "NUMBER",
            "MIN" => 0,
            "NAME" => 'Каждый последующий шаг веса для ' . $name,
            'READONLY' => false,
            'DEFAULT' => '1000',
        ];
        $ret[$key . '_PS'] = [
            "TYPE" => "NUMBER",
            "MIN" => 0,
            "NAME" => 'Цена за каждый последующий шаг веса для ' . $name,
            'READONLY' => false,
            'DEFAULT' => '174',
        ];
        $ret[$key . '_R'] = [
            "TYPE" => "Y/N",
            "NAME" => 'Округлять вес вверх для для ' . $name,
            'READONLY' => false,
            'DEFAULT' => 'Y',
        ];
        return;
    }
}
?>