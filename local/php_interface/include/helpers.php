<?php

function normalizeIp($ip)
{
    $matches = [];
    if(preg_match('/\s*(\d{1,3})[.](\d{1,3})[.](\d{1,3})[.](\d{1,3})\s*/', $ip, $matches)) {
        array_shift($matches);
        foreach ($matches as &$match) {
            $match = ltrim($match, '0');
            if(strlen($match) == 0) {
                $match = '0';
            }
        }
        unset($match);
        return implode('.', $matches);
    } else {
        return false;
    }
}
function normalizeRuPhone($phone, $allowNoCode = false, $allow8 = false, $preserveCode = true)
{
    $phone = preg_replace('/\D+/', '', $phone);

    $matches = [];
    if(empty($phone)) {
        return false;
    } else if(preg_match('/^7(\d{10})$/', $phone, $matches)) {

    } else if($allow8 && preg_match('/^8(\d{10})$/', $phone, $matches)) {
        $phone = '7' . $matches[1];
    } else if($allowNoCode && preg_match('/^(\d{10})$/', $phone, $matches)) {
        $phone = '7' . $phone;
    } else {
        return false;
    }

    if(!$preserveCode) {
        return $matches[1];
    }

    return $phone;
}

if(!function_exists('localizeIblockElement')) {
    function localizeIblockElement(&$arElement, $lang = null)
    {
        if($lang === null) {
            $lang = SITE_ID;
        }

        $lang = strtoupper($lang);

        foreach ($arElement as $field => &$value) {
            if ($field == 'PROPERTIES') {
                continue;
            }

            $fieldLang = $field . '_' . $lang;

            foreach ($arElement as $fld => &$val) {
                if ($fld == 'PROPERTIES') {
                    continue;
                }
                if(empty($val)) {
                    continue;
                }
                if($fld == $fieldLang || strpos($field,'UF_') !== 0 && 'UF_' . $fieldLang == $fld) {
                    $value = $val;
                }
            }
            unset($val);
        }
        unset($value);

        if(empty($arElement['PROPERTIES'])) {
            return;
        }

        foreach ($arElement as $field => &$value) {
            if($field == 'PROPERTIES') {
                continue;
            }

            if($field[0] == '~') {
                $tilda = '~';
                $fieldLang = substr($field, 1) . '_' . $lang;
            } else {
                $tilda = '';
                $fieldLang = $field . '_' . $lang;
            }

            foreach ($arElement['PROPERTIES'] as $code => &$arProperty) {
                if(empty($arProperty['VALUE'])) {
                    continue;
                }

                if($code == $fieldLang) {
                    $value = $arProperty[$tilda . 'VALUE'];
                    $isTextField = substr($field, -5) == '_TEXT' && is_array($value);
                    $isFileField = substr($field, -8) == '_PICTURE' && is_numeric($value);
                    if($isFileField && !$tilda) {
                        $value = \CFile::GetFileArray($value);
                    }
                    if($isTextField && $value['TYPE'] == 'HTML' && $arProperty['~VALUE']) {
                        $value = $arProperty['~VALUE'];
                    }
                    if(isset($arElement['FIELDS'][$field])) {
                        if($isTextField) {
                            $arElement['FIELDS']["${field}_TYPE"] = strtolower($value['TYPE']);
                            $arElement['FIELDS'][$field] = $value['TEXT'];
                        } else {
                            $arElement['FIELDS'][$field] = $value;
                        }
                    }
                    if($isTextField) {
                        $arElement["${field}_TYPE"] = strtolower($value['TYPE']);
                        $value = $value['TEXT'];
                    }
                }
            }
            unset($arProperty);
        }
        unset($value);


        foreach ($arElement['PROPERTIES'] as $dst => &$arPropertyDst) {
            $code = $dst . '_' . $lang;

            foreach ($arElement['PROPERTIES'] as $src => &$arPropertySrc) {
                if(empty($arPropertySrc['VALUE'])) {
                    continue;
                }

                if($code == $src) {
                    $arPropertyDst = $arPropertySrc;
                    if(isset($arElement['DISPLAY_PROPERTIES'][$dst]) && isset($arElement['DISPLAY_PROPERTIES'][$src])) {
                        $arElement['DISPLAY_PROPERTIES'][$dst] = $arElement['DISPLAY_PROPERTIES'][$src];
                    }
                }
            }
            unset($arPropertySrc);
        }
        unset($arPropertyDst);
    }
}

if(!function_exists('resultCacheTime')) {
    function resultCacheTime($arParams)
    {
        if(is_numeric($arParams))
        {
            return intval($arParams);
        }

        if(!is_array($arParams) || !isset($arParams['CACHE_TYPE']) || !isset($arParams['CACHE_TIME']))
        {
            return 0;
        }

        if($arParams['CACHE_TYPE'] == 'N')
        {
            return 0;
        }
        elseif($arParams['CACHE_TYPE'] == 'Y' || $arParams['CACHE_TYPE'] == 'A' && \Bitrix\Main\Config\Option::get("main", "component_cache_on", "Y") == 'Y')
        {
            return intval($arParams['CACHE_TIME']);
        }
        else
        {
            return 0;
        }
    }
}

if(!function_exists('resultCache')) {
    function resultCache($ttl, $callback, $params = [])
    {
        if($callback instanceof \Closure) {
            $r = new \ReflectionFunction($callback);
            $cacheId = $r->getFileName() . ':' . $r->getStartLine() . '-' . $r->getEndLine();
            //TODO: Добавить захваченные параметры.
        } else {
            $cacheId = serialize($callback);
        }

        $cacheId .= '_' . serialize($params);
        $cacheId = hash('sha512', $cacheId);

        $cache = \Bitrix\Main\Data\Cache::createInstance();

        if($cache->initCache($ttl, $cacheId, 'resultCache')) {
            $cache->output();
            $result = $cache->getVars();
            $result = $result['result'];
        } else if($cache->startDataCache()) {
            $result = $callback(...$params);
            $cache->endDataCache(['result' => $result]);
        } else {
            $result = $callback(...$params);
        }

        return $result;
    }
}

function escapeCsv($value, $force = false) {
    $value .= "";
    if($force || strpos('"', $value) !== false
        || strpos(";", $value != false)
        || strpos("\n", $value != false)) {
        return '"' . str_replace('"', '""', $value) . '"';
    } else {
        return $value;
    }
}

function query_timestamp($mark = '?')
{
    $result = '';
    if(gsv_is_debug()) {
        if($mark) {
            $result .= $mark;
        }
        $result .= 't=';
        $result .= time();
    }
    return $result;
}

function query_mtime($file, $mark = '?')
{
    $result = '';
    if(gsv_is_debug()) {
        if($mark) {
            $result .= $mark;
        }
        $result .= 'm=';
        $FILE = $_SERVER["DOCUMENT_ROOT"].$file;
        if(file_exists($FILE)) {
            $result .= filemtime($FILE);
        }
    }
    return $result;
}

function filename_mtime($file)
{
    return $file . query_mtime($file, '?');
}