<?php

namespace Gsv\Bitrix\ImportTraits;

trait MiscTrait
{
    use CommonTrait;

    /** @var bool $bFileForceReload - Always reload files */
    protected $bFileForceReload = false;

    /** @var \Gsv\Util\Http\HttpClient $obHttp */
    protected $obHttp = null;




    protected function filterChangedProperties($arOriginal, $arNew, $arProperties)
    {
        foreach ($arNew as $code => $arProp) {
            if(!array_key_exists($code, $arOriginal)) {
                continue;
            }
            if(array_key_exists($code, $arProperties)) {
                $arProperty = $arProperties[$code];
                if($arProperty['PROPERTY_TYPE'] == 'L') {
                    $arOrig = $arOriginal[$code]['VALUE_ENUM_ID'];
                } else {
                    $arOrig = $arOriginal[$code]['VALUE'];
                }
            } else {
                $arProperty = $code;
                $arOrig = $arOriginal[$code];
            }

            if(!$this->isChangedProperty($arOrig, $arProp, $arProperty)) {
                unset($arNew[$code]);
            }
        }
        return $arNew;
    }

    protected function isChangedProperty($arOriginal, $arNew, $arProperty = false)
    {
        if(empty($arProperty)) {
            $arProperty = [
                'MULTIPLE' => 'N',
                'PROPERTY_TYPE' => 'S',
            ];
        } else if(is_string($arProperty)) {
            if(substr($arProperty, -8) == '_PICTURE') {
                $arProperty = [
                    'MULTIPLE' => 'N',
                    'PROPERTY_TYPE' => 'F',
                ];
            } else {
                $arProperty = [
                    'MULTIPLE' => 'N',
                    'PROPERTY_TYPE' => 'S',
                ];
            }
        }

        $arOriginal = $this->unifyProp($arOriginal, $arProperty);
        $arNew = $this->unifyProp($arNew, $arProperty);

        if($arProperty['PROPERTY_TYPE'] == 'F') {

            if($this->bFileForceReload) {
                return true;
            }

            if(empty($arNew)) {
                return !empty($arOriginal);
            }

            if(count($arNew) != count($arOriginal)) {
                return true;
            }

            if(!static::FILE_HASH_COMPARE) {
                return false;
            }

            $arOriginalHashes = [];
            foreach ($arOriginal as $arFile) {
                $file = $_SERVER['DOCUMENT_ROOT'] . $arFile['SRC'];
                //static ::dump([$file, $arFile]);
                if(!file_exists($file)) {
                    //static ::dump('NOT exists!');
                    return true;
                }
                $hash = $arFile["FILE_SIZE"] . '-' . hash_file('sha512', $file);
                $arOriginalHashes [] = $hash;
            }

            $arNewHashes = [];
            foreach ($arNew as $arFile) {
                $file = $arFile["tmp_name"];
                if(!file_exists($file)) {
                    //Should it be so!?
                    return false;
                }
                $hash = $arFile["size"] . '-' . hash_file('sha512', $file);
                $arNewHashes [] = $hash;
            }

            return (array_diff($arOriginalHashes, $arNewHashes) || array_diff($arNewHashes, $arOriginalHashes));
        }

        if(count($arOriginal) != count($arNew)) {
            return true;
        }

        if($arProperty['PROPERTY_TYPE'] == 'S' && $arProperty['USER_TYPE'] == 'HTML') {
            $arOriginalCopy = $arOriginal;
            $arNewCopy = $arNew;
            foreach ($arOriginalCopy as $originalCopy) {
                foreach ($arNewCopy as $j => $newCopy) {
                    /*if(!is_array($originalCopy)) {
                        $originalCopy = [
                            'TYPE' => 'HTML',
                            'TEXT' => $originalCopy,
                        ];
                    }
                    if(!is_array($newCopy)) {
                        $newCopy = [
                            'TYPE' => 'HTML',
                            'TEXT' => $newCopy,
                        ];
                    }*/
                    if($originalCopy['TYPE'] == $newCopy['TYPE']
                        && $originalCopy['TEXT'] == $newCopy['TEXT']) {
                        unset($arNewCopy[$j]);
                        continue 2;
                    }
                }
                return true;
            }
        }

        if(empty(array_diff($arOriginal, $arNew)) && empty(array_diff($arNew, $arOriginal))) {
            return false;
        }

        return true;
    }

    public static function asExtra($target)
    {
        $pos = strpos($target, 'EXTRA_');
        if($pos === 0) {
            return substr($target, strlen('EXTRA_'));
        } else {
            return false;
        }
    }

    protected static function getExtraCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($field = static::asExtra($target)) {
                    $arResult[] = $field;
                }
            }
        }
        return array_unique($arResult);
    }

    protected function getHttp()
    {
        if($this->obHttp === null) {
            $this->obHttp = new \Gsv\Util\Http\HttpClient(true, true);
        }
        return $this->obHttp;
    }
}