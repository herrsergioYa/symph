<?php

namespace Gsv\Sale\ActionCtrl;

//TODO: It is RAW!!
class GifteryCoupon extends \CSaleActionCtrlAction
{
    /**
     * @var \USH\Giftery\App $gifteryApp
     */
    protected static $gifteryApp;

    /**
     * @var array|null|false $forcedCert
     */
    protected static $forcedCert = null;


    /**
     * Получение имени класса
     * @return string
     */
    public static function GetClassName()
    {
        return static::class;
    }

    /**
     * Получение ID условия
     * @return array|string
     */
    public static function GetControlID()
    {
        return "GifteryCoupon";
    }

    /**
     * Добавление пункта в список условий с указанием отдельной группы
     * @param $arParams
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetControlShow($arParams)
    {
        $arControls = static::GetAtomsEx();
        $arResult = array(
            'controlgroup' => true,
            'group' =>  false,
            'label' => 'Кастомные правила',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'children' =>  [
                array(
                    'controlId' => static::GetControlID(),
                    'group' => false,
                    'label' => "Сертификат Giftery",
                    'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                    'control' => array(
                        "Сертификат Giftery",
                        $arControls["PT"]
                    )
                ),
            ]
        );

        return $arResult;
    }

    /**
     * Формирование данных для визуального представления условия
     * @param bool $strControlID
     * @param bool $boolEx
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = (true === $boolEx ? true : false);
        $priceList = [];
        /*if (\Bitrix\Main\Loader::includeModule('catalog')) {
            // Получение типов цен
            $arGroupPrice = \Bitrix\Catalog\GroupTable::getList([
                'select' => ['ID', 'NAME'],
                'cache' => [
                    'ttl' => 60,
                    'cache_joins' => true,
                ]
            ]);

            while ($el = $arGroupPrice->fetch()) {
                $priceList[$el['ID']] = $el['NAME'] . " [" . $el['ID'] . "]";
            }
        }*/
        $priceList[1] = 'Y';

        $arAtomList = [
            "PT" => [
                "JS" => [
                    "id" => "PT",
                    "name" => "extra",
                    "type" => "select",
                    "values" => $priceList,
                    "defaultText" => "...",
                    "defaultValue" => "",
                    "first_option" => "..."
                ],
                "ATOM" => [
                    "ID" => "PT",
                    "FIELD_TYPE" => "string",
                    "FIELD_LENGTH" => 255,
                    "MULTIPLE" => "N",
                    "VALIDATE" => "list"
                ]
            ],
        ];
        if (!$boolEx) {
            foreach ($arAtomList as &$arOneAtom)
            {
                $arOneAtom = $arOneAtom["JS"];
            }
            if (isset($arOneAtom))
            {
                unset($arOneAtom);
            }
        }

        return $arAtomList;
    }

    /**
     * Функция должна вернуть колбэк того, что должно быть выполнено при наступлении условий
     * @param $arOneCondition
     * @param $arParams
     * @param $arControl
     * @param bool $arSubs
     * @return string
     */
    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        return '\\' . static::class . '::applyProductDiscount($arOrder, "' . $arOneCondition["PT"] . '")';
    }

    /**
     * Логика кастомного условия
     * @param array &$arOrder
     * @param string|integer $dummy
     * @return void
     */
    public static function applyProductDiscount(&$arOrder, $dummy)//&$arOrder)//$row), $priceType)
    {
        $totalPaidByGiftery = 0;
        $orderPrice = static::GetOrderBasketTotal($arOrder);
        $inst = new static();
        $arCoupon = $inst->GetAppliedCouponWithBalance();
        if($arCoupon)
        {
            $discount = $orderPrice - 1;
            $balance = floatval($arCoupon['balance']);
            if($balance > 0 && $discount > 0)
            {
                if($discount > $balance)
                {
                    $discount = $balance;
                }
                $balance -= $discount;
                $totalPaidByGiftery += $discount;
                $discountP = $discount * 100.0 / $orderPrice;
                $arDiscount = array(
                    'VALUE' => -$discountP,
                    'UNIT' => 'P',
                    'LIMIT_VALUE' => 0,
                );
                \Bitrix\Sale\Discount\Actions::applyToBasket($arOrder, $arDiscount,"");
            }
            $deliveryPrice = $arOrder['PRICE_DELIVERY'];
            $discount = $deliveryPrice;
            if($balance > 0 && $discount > 0)
            {
                if($discount > $balance)
                {
                    $discount = $balance;
                }
                $balance -= $discount;
                $totalPaidByGiftery += $discount;
                $discountP = $discount * 100.0 / $deliveryPrice;
                $arDiscount = array(
                    'VALUE' => -$discountP,
                    'UNIT' => 'P',
                    'LIMIT_VALUE' => 0,
                );
                \Bitrix\Sale\Discount\Actions::applyToDelivery($arOrder, $arDiscount);
            }
        }
        $_SESSION['totalPaidByGiftery'] = $totalPaidByGiftery;
    }

    public static function GetOrderBasketTotal($order)
    {
        $orderPrice = 0;
        foreach ($order['BASKET_ITEMS'] as $basketItem)
        {
            $orderPrice += $basketItem['QUANTITY'] * $basketItem['PRICE'];
        }
        return $orderPrice;
    }

    public static function GetControlDescr()
    {
        $description = parent::GetControlDescr();
        return $description;
    }

    public function GetAppliedCouponWithBalance()
    {
        if(static::$forcedCert !== null) {
            return static::$forcedCert;
        }
        return static::GetGifteryApp()->getAppliedCert(true);
    }

    public static function GetGifteryApp()
    {
        if(static::$gifteryApp === null)
        {
            static::$gifteryApp = new \USH\Giftery\App();
        }
        return static::$gifteryApp;
    }

    public static function onSaleOrderSavedHandler(\Bitrix\Main\Event $event)
    {
        /** @var \Bitrix\Sale\Order $order */
        $order = $event->getParameter("ENTITY");
        $oldValues = $event->getParameter("VALUES");
        $isNew = $event->getParameter("IS_NEW");
        if ($isNew)
        {
            if(isset($_SESSION['totalPaidByGiftery']))
            {
                $totalPaidByGiftery = $_SESSION['totalPaidByGiftery'];
                static::GetGifteryApp()->redeemCert($totalPaidByGiftery);
                unset($_SESSION['totalPaidByGiftery']);
            }
            static::$forcedCert = \USH\Giftery\App::getAppliedCert_NoCheck();
            \USH\Giftery\App::removeCert();
        }
    }
}