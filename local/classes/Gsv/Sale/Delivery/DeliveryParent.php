<?
namespace Gsv\Sale\Delivery;

use Bitrix\Main\Config\Option;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;

//TODO: It is RAW!!
class DeliveryParent extends DeliveryBase
{
    public static function getClassTitle()
    {
        return 'Новая служба доставки';
    }

    public static function getClassDescription()
    {
        return 'Новая служба доставки';
    }

    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
    {
        throw new \Bitrix\Main\SystemException('Only profiles can calculate concrete');
    }

    protected function getConfigStructure()
    {
        return array(
            "MAIN" => array(
                "TITLE" => 'Настройка родительского обработчика',
                "DESCRIPTION" => 'Настройка родительского обработчика',
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
            \Gsv\Sale\Delivery\Delivery::class,
        ];
    }

    public function getProfilesList()
    {
        return array("Новый профиль");
    }
}
?>