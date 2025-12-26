<?php

namespace Sprint\Migration;

use \Bitrix\Main\Config\Option;

class VersionAddedGifteryOptions20241114071053 extends Version
{
    protected $author = "admin";

    protected $description = "";

    protected $moduleVersion = "4.15.1";

    public function up()
    {
        $helper = $this->getHelperManager();
        //your code ...
        foreach(['id', 'secret'] as $name) {
            if ('' == Option::get('ush.giftery', $name, '')) {
                Option::set('ush.giftery', $name, '****');
            }
        }
    }

    public function down()
    {
        //your code ...
    }
}
