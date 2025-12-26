<?php

namespace Gsv\Bitrix\ImportTraits;

trait CommonTrait
{
    /*
    public const TRANSLIT_LANG = 'ru';
    public const TRANSLIT_PARAMS = array(
        "max_len" => 100,
        "change_case" => 'L', // 'L' - toLower, 'U' - toUpper, false - do not change
        "replace_space" => '-',
        "replace_other" => '-',
        "delete_repeat_replace" => true,
        "safe_chars" => '',
    );
     */

    /** @var \CTextParser $obTextParser */
    protected $obTextParser = null;

    protected static function translit($text)
    {
        return \CUtil::translit($text, static::TRANSLIT_LANG, static::TRANSLIT_PARAMS);
    }

    protected static function clearEnumValue($value)
    {
        if(!empty($value)) {
            $value = mb_strtolower(trim($value));
            $value = preg_replace('/\s+/', ' ', $value);
            $value = str_replace('ё', 'е', $value);
        }
        return $value;
    }

    protected function getTextParser()
    {
        if($this->obTextParser === null)
            $this->obTextParser = new \CTextParser();
        return $this->obTextParser;
    }

    protected function unifyProp($value, $arProperty)
    {
        if($arProperty['MULTIPLE'] != 'Y') {
            if($value) {
                $value = [$value];
            } else {
                $value = [];
            }
        } else if(empty($value)) {
            $value = [];
        }
        return $value;
    }

    protected function deunifyProp($value, $arProperty)
    {
        if($arProperty['MULTIPLE'] != 'Y') {
            if($value) {
                $value = $value[0];
            } else {
                $value = false;
            }
        }
        return $value;
    }
}