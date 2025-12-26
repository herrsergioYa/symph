<?php

namespace Sprint\Migration;

use \Gsv\Bitrix\SprintMigration\SaleOrderPropsTrait;

class VersionAddedOrderProp20241228111620 extends Version
{
    use SaleOrderPropsTrait;

    const ORDER_PROP_CODES = ['ZIP'];

    protected $description = "";

    protected $moduleVersion = "4.1.1";

    public function up()
    {
        //your code ...
        $this->readProps(__FILE__);
        //$this->ensureFields(__FILE__);
    }

    public function down()
    {
        //your code ...
    }
}
