<?
namespace Gsv\Iblock\PropertyBuildList;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;
use Gsv\IBlock\PropertyBuildList;

/**
 * Создает свойство iblock.
 * Новое свойство позволяется указывать привязку к маркетинговому правилу без купонов
 */
class SaleCouponlessDiscount extends PropertyBuildList
{
    public static function OnIBlockPropertyBuildListHandler()
    {
        $arResult = parent::OnIBlockPropertyBuildListHandler();
        return array(
            'PROPERTY_TYPE' => 'N',
            'USER_TYPE' => 'SGsvListCouponlessDiscounts',
            //'USER_TYPE_ID' => 'SGsvListCouponlessDiscounts',
            'DESCRIPTION' => 'Gsv: Список скидок без купонов',
        ) + $arResult;
    }

    protected static function getDiscountList()
    {
        return \Bitrix\Sale\Internals\DiscountTable::getList([
            'order' => array("NAME" => "ASC"),
            'filter' => [
                'USE_COUPONS' => false,
            ]
        ]);
    }
}
