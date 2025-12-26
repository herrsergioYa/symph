<?php

namespace Sprint\Migration;


class VersionAddedSaleProfileLink20240822075658 extends Version
{
    protected $description = "";

    protected $moduleVersion = "4.1.1";

    const ORDER_PROP_CODES = [
        'PROFILE_LINK',
    ];

    public function up()
    {
        //your code ...
        $this->ensureFields();
    }

    public function down()
    {
        //your code ...
    }


}
