<?php

namespace Sprint\Migration;


use CIBlock;

class VersionFixApiCodesOfBanners20250507124637 extends Version
{
    protected $author = "sgaevoy";

    protected $description = "Инфоблокам 74 и 75 необходимо проставить API_CODE.";

    protected $moduleVersion = "5.0.0";

    protected $arApiCodes = [
        74 => 'asproliteadvtbigkz',
        75 => 'asprolitebannerskz',
    ];

    public function up()
    {
        $helper = $this->getHelperManager();
        //your code ...
        \CModule::IncludeModule("iblock");
        $dbIblocks = \CIBlock::GetList(
            [],
            [
                'ID' => array_keys($this->arApiCodes),
                'API_CODE' => false,
            ],
        );
        /** @var \CIBlock $obIblock */
        $obIblock = null;
        while($arBlock = $dbIblocks->Fetch()) {
            if(empty($arBlock['API_CODE'])) { //Isn't checked by the filter!
                if($obIblock === null) {
                    $obIblock = new \CIBlock();
                }
                $obIblock->Update($arBlock['ID'], [
                    'API_CODE' => $this->arApiCodes[$arBlock['ID']],
                ]);
            }
        }
    }

    public function down()
    {
        //your code ...
    }
}
