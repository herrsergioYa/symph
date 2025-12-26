<?
namespace Gsv\Iblock\PropertyBuildList;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;
use Gsv\IBlock\PropertyBuildList;

/**
 * Создает свойство iblock.
 * Новое свойство позволяется указывать привязку к маркетинговому правилу каталога
 */
class SaleCatalogDiscount extends SaleCouponlessDiscount
{
    public static function OnIBlockPropertyBuildListHandler()
    {
        $arResult = parent::OnIBlockPropertyBuildListHandler();
        return array(
            'PROPERTY_TYPE' => 'N',
            'USER_TYPE' => 'SGsvListCatalogDiscounts',
            //'USER_TYPE_ID' => 'SGsvListCatalogDiscounts',
            'DESCRIPTION' => 'Gsv: Список скидок каталога',
        ) + $arResult;
    }

    protected static function getDiscountList()
    {
        return \Bitrix\Sale\Internals\DiscountTable::getList([
            'order' => array("NAME" => "ASC"),
            'filter' => [
                //'USE_COUPONS' => false,//Deemed by default
                'EXECUTE_MODULE' => array('all', 'catalog'),
            ]
        ]);
    }
}
