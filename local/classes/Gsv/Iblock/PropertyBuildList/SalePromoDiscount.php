<?
namespace Gsv\Iblock\PropertyBuildList;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;
use Gsv\IBlock\PropertyBuildList;

/**
 * Создает свойство iblock.
 * Новое свойство позволяется указывать привязку к промо-акции
 */
class SalePromoDiscount extends SaleDiscount
{
    public static function OnIBlockPropertyBuildListHandler()
    {
        $arResult = parent::OnIBlockPropertyBuildListHandler();
        return array(
            'PROPERTY_TYPE' => 'N',
            'USER_TYPE' => 'SGsvListPromos',
            //'USER_TYPE_ID' => 'SGsvListDiscounts',
            'DESCRIPTION' => 'Gsv: Список промо-акций',
        ) + $arResult;
    }

    protected static function getDiscountList()
    {
        return \Bitrix\Sale\Internals\DiscountTable::getList([
            'order' => array("NAME" => "ASC"),
            'filter' => [
                'USE_COUPONS' => true,
            ]
        ]);
    }
}
