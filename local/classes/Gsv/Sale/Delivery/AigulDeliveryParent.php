<?
namespace Gsv\Sale\Delivery;

use Bitrix\Main\Config\Option;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;

//TODO: It is RAW!!
class AigulDeliveryParent extends AigulDeliveryBase
{
    public static function getClassTitle()
    {
        return 'Доставка "Айгуль"';
    }

    public static function getClassDescription()
    {
        return 'Доставка "Айгуль"';
    }

    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
    {
        throw new \Bitrix\Main\SystemException('Only profiles can calculate concrete');
    }

    protected function getConfigStructure()
    {
        return array(
            "MAIN" => array(
                "TITLE" => 'Настройка родительского обработчика Aigul',
                "DESCRIPTION" => 'Настройка родительского обработчика Aigul',
                "ITEMS" => array(
                )
            )
        );
    }

    public function isCalculatePriceImmediately()
    {
        return true;
    }

    public static function whetherAdminExtraServicesShow()
    {
        return true;
    }

    public static function canHasProfiles()
    {
        return true;
    }

    public static function getChildrenClassNames()
    {
        return [
            \Gsv\Sale\Delivery\AigulDelivery::class,
        ];
    }

    public function getProfilesList()
    {
        return array("Новый профиль Aigul");
    }
}
?>