<?php

namespace Gsv\Bitrix\ImportTraits;

trait SectionTrait
{
    use CommonTrait;
    //use ProductTrait;

    /*

     const SECTION_FIELDS = ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'UF_NAME_EN', 'UF_NAME_RU',];

     const ROOT_SECTIONS = [
        'SECTION' => [
            [
                'NAME' => 'Каталог',
                'UF_NAME_EN' => 'Catalog',
            ],
        ],
        'CATALOG' => [],
    ];
     */

    /**
     * @var array $arProductSections - Sections being in the Iblock
     */
    protected $arProductSections = [];

    /**
     * @var int|string $SECTION_IBLOCK_ID
     */
    protected $SECTION_IBLOCK_ID;

    /** @var \CIBlockSection $obElement */
    protected $obSection = null;

    protected function initSectionTree($IBLOCK_ID)
    {
        $this->SECTION_IBLOCK_ID = (int)$IBLOCK_ID;
        $this->arProductSections = static::getSectionTree($this->SECTION_IBLOCK_ID, true);
    }

    protected static function getSectionTree($IBLOCK_ID, $tree = true)
    {
        $arSections = [];

        $SECTION_FIELDS = static::SECTION_FIELDS;
        $SECTION_FIELDS[] = 'ID';
        $SECTION_FIELDS[] = 'IBLOCK_SECTION_ID';
        $SECTION_FIELDS[] = 'NAME';
        //$SECTION_FIELDS[] = 'CODE';
        $SECTION_FIELDS = array_values(array_unique($SECTION_FIELDS));

        $dbSections = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
            ],
            false,
            $SECTION_FIELDS,
        );

        while($arSection = $dbSections->Fetch()) {
            if($tree) {
                $arSection['CHILDREN'] = [];
            }
            $arSections[$arSection['ID']] = $arSection;
            if($tree && $arSection['IBLOCK_SECTION_ID']) {
                $arSections[$arSection['IBLOCK_SECTION_ID']]['CHILDREN'][$arSection['ID']]
                    = &$arSections[$arSection['ID']];
            }
        }

        if($tree) {
            foreach ($arSections as $id => $arSection) {
                if($arSection['IBLOCK_SECTION_ID']) {
                    unset($arSections[$id]);
                }
            }
        }

        return $arSections;
    }

    protected function applySections(&$arProducts)
    {
        $arTree = [
            'ID' => 0,
            'CHILDREN' => &$this->arProductSections,
        ];
        $IBLOCK_ID = $this->SECTION_IBLOCK_ID;//Should I take the $IBLOCK_ID from $arProduct and that's all?
        foreach ($arProducts as &$arProduct) {
            if(!empty($arProduct['IBLOCK_ID']) && $arProduct['IBLOCK_ID'] != $IBLOCK_ID) {
                throw new \Exception("IBLOCK mismatches!");
            }
            $arProduct['IBLOCK_SECTION'] = [];
            $arProduct['IBLOCK_SECTION_ID'] = '';
            foreach (array_keys(static::ROOT_SECTIONS) as $sect) {
                if(isset($arProduct['EXTRA']['SECTION_' . $sect])) {
                    $arSections = [];
                    foreach(static::ROOT_SECTIONS[$sect] as $arSection) {
                        $arSections[] = $arSection;
                    }
                    foreach ($arProduct['EXTRA'][$sect] as $arSection) {
                        $arSections[] = $arSection;
                    }
                    if ($ID = $this->applySection($arTree, $arSections, $IBLOCK_ID)) {
                        $arProduct['IBLOCK_SECTION'][] = $ID;
                        //The order DOES matter
                        if (empty($arProduct['IBLOCK_SECTION_ID'])) {
                            $arProduct['IBLOCK_SECTION_ID'] = $ID;
                        }
                    }
                }
            }
        }
    }

    protected function applySection(&$arTree, $arSections, $IBLOCK_ID)
    {
        foreach ($arSections as $arSection) {
            $name = static::clearEnumValue($arSection['NAME']);
            foreach ($arTree['CHILDREN'] as &$arNode) {
                $nodeName = static::clearEnumValue($arNode['NAME']);
                if($nodeName == $name) {
                    $arTree = &$arNode;
                    continue 2;
                }
            }

            $arSection['IBLOCK_SECTION_ID'] = $arTree['ID'];
            $arSection['IBLOCK_ID'] = $IBLOCK_ID;
            $arSection['CODE'] = static::translit($arSection['NAME']);
            $arSection['ID'] = $this->getIblockSection()->Add($arSection);
            if(empty($arSection['ID'])) {
                $this->log("Error: %s during processing %s", [$this->obSection->LAST_ERROR, $arSection,]);
            }
            $arSection['CHILDREN'] = [];
            $arTree['CHILDREN'][$arSection['ID']] = $arSection;
            $arTree = &$arTree['CHILDREN'][$arSection['ID']];
        }

        return $arTree['ID'];
    }

    protected function adjustSections(&$arProduct)
    {

    }

    protected function getIblockSection()
    {
        if($this->obSection === null) {
            $this->obSection = new \CIBlockSection();
        }
        return $this->obSection;
    }
}