<?php

namespace Gsv\Bitrix;

use \Bitrix\Main\Config\Option;

/**
 * Класс для реализации Ajax по ключам
 */
class ComponentByAjax
{
    /**
     *
     */
    const COMPONENT_AJAX_OPT = 'gsv.component_ajax';
    /**
     *
     */
    const COMPONENT_AJAX_SALT = "gsv-component-ajax";

    /**
     * Метод, который получает ключ для вызова компонента Ajax.
     * @param \CBitrixComponent $component Компонент
     * @param string|null $templateName Шаблон для генерации вида
     * @param array|null $arParams Параметры компонента
     * @return array Полученный массив
     */
    public static function fromComponent(\CBitrixComponent $component, string $templateName = null, array $arParams = null)
    {
        $name = $component->__name;
        $params = $arParams ?? $component->arParams;
        $template = $templateName ?? $component->GetTemplateName();

        return static::fill($name, $template, $params);
    }

    /**
     * Метод, который получает ключ для вызова компонента Ajax.
     * @param \CBitrixComponentTemplate $template Шаблон компонента
     * @param string|null $templateName Шаблон для генерации вида
     * @param array|null $arParams Параметры компонента
     * @return array Полученный массив
     */
    public static function fromTemplate(\CBitrixComponent $template, string $templateName = null, array $arParams = null)
    {
        return static::fromComponent($template->__component, $templateName, $arParams);
    }

    /**
     * @param string $componentName
     * @param string $templateName
     * @param array $arParams
     * @return array
     */
    public static function fill(string $componentName, string $templateName, array $arParams): array
    {
        foreach ($arParams as $key => $value)
        {
            if (strpos($key, '~') === 0)
            {
                $arParams[substr($key, 1)] = $value;
                unset($arParams[$key]);
            }
        }

        ksort($arParams);

        $arData = array(
            'COMPONENT_NAME' => $componentName,
            'COMPONENT_TEMPLATE' => $templateName,
            'PARAMS' => $arParams,
        );

        return $arData;
    }


    /**
     * @param \CBitrixComponent|\CBitrixComponentTemplate|string|array $component
     * @param string|null $templateName
     * @param array|null $arParams
     * @return array
     * @throws \Exception
     */
    public static function from($component, string $templateName = null, array $arParams = null): array
    {
        if($component instanceof \CBitrixComponent) {
            return static::fromComponent($component, $templateName, $arParams);
        } else if($component instanceof \CBitrixComponentTemplate) {
            return static::fromTemplate($component, $templateName, $arParams);
        } else if(is_string($component)) {
            return static::fill($component, $templateName ?? '.default', $arParams ?? []);
        } else if(is_array($component)) {
            if($templateName !== null) {
                $component['COMPONENT_TEMPLATE'] = $templateName;
            }
            if($arParams !== null) {
                $component['PARAMS'] = $arParams;
            }
            return $component;
        } else {
            throw new \Exception('Wrong argument: ' . $component);
        }
    }

    /**
     * Метод, который получает ключ на низком уровне. Можно сформировать любой запрос.
     * @param array $arData Массив для получения ключа
     * @return string|bool Ключ
     */
    public static function setSessData(array $arData)
    {
        $uniqueKey = serialize($arData);
        $uniqueKey = hash('sha512', $uniqueKey);
        $_SESSION[__CLASS__][$uniqueKey] = $arData;

        //возвращаем ключ
        return $uniqueKey;
    }

    /**
     * Возвращает данные по указазнному ключу
     * @param string $uniqueKey Ключ
     * @return array|false Данные
     */
    public static function getSessData(string $uniqueKey)
    {
        return $_SESSION[__CLASS__][$uniqueKey] ?: false;
    }

    public static function setSessDataFrom($component, string $templateName = null, array $arParams = null)
    {
        return static::setSessData(static::from($component, $templateName, $arParams));
    }

    /**
     * Метод, который получает шифровку на низком уровне. Можно сформировать любой запрос.
     * @param array $arData Массив для получения ключа
     * @return bool|string Шифровка
     */
    public static function protect(array $arData)
    {
        $ret = \Gsv\Bitrix\Security::protectArray($arData, static::COMPONENT_AJAX_SALT);
        if($ret)
        {
            $ret = static::urlencode64($ret);
        }
        return $ret;
    }

    /**
     * Возвращает данные по указазнной шифровке
     * @param string $uniqueKey Шифровка
     * @return array|false Данные
     */
    public static function unprotect(string $data)
    {
        try
        {
            $data = static::urldecode64($data);
            if(!is_string($data))
                return false;
            $return = \Gsv\Bitrix\Security::unprotectArray($data, static::COMPONENT_AJAX_SALT);
            if(!is_array($return))
                $return = false;
            return $return;
        }
        catch (\Throwable $exception)
        {
            return false;
        }
    }

    public static function protectFrom($component, string $templateName = null, array $arParams = null)
    {
        return static::protect(static::from($component, $templateName, $arParams));
    }

    public static function sign(array $arData)
    {
        $ret = \Gsv\Bitrix\Security::signArray($arData, static::COMPONENT_AJAX_SALT, '', true);
        if($ret)
        {
            $ret = static::urlencode64($ret);
        }
        return $ret;
    }

    public static function unsign(string $data)
    {
        try
        {
            $data = static::urldecode64($data);
            if(!is_string($data))
                return false;
            $return = \Gsv\Bitrix\Security::unsignArray($data, static::COMPONENT_AJAX_SALT, '', true);
            if(!is_array($return))
                $return = false;
            return $return;
        }
        catch (\Throwable $exception)
        {
            return false;
        }
    }

    public static function signFrom($component, string $templateName = null, array $arParams = null)
    {
        return static::sign(static::from($component, $templateName, $arParams));
    }

    public static function encrypt(array $arData)
    {
        $ret = \Gsv\Bitrix\Security::encryptArray($arData);
        if($ret)
        {
            $ret = static::urlencode64($ret);
        }
        return $ret;
    }

    public static function decrypt(string $data)
    {
        try
        {
            $data = static::urldecode64($data);
            if(!is_string($data))
                return false;
            $return = \Gsv\Bitrix\Security::decryptArray($data);
            if(!is_array($return))
                $return = false;
            return $return;
        }
        catch (\Throwable $exception)
        {
            return false;
        }
    }

    public static function encryptFrom($component, string $templateName = null, array $arParams = null)
    {
        return static::encrypt(static::from($component, $templateName, $arParams));
    }

    public static function urlencode64(string $val)
    {
        if(!preg_match('#^([A-Za-z0-9+/]+={0,2})([.]([A-Za-z0-9+/]+={0,2}))*$#', $val))
        {
            return false;
        }

        $ret = [];
        foreach (explode('.', $val) as $v)
        {
            if((strlen($v) & 3) && substr($v, -1) == '=')
            {
                return false;
            }

            $v = rtrim($v, '=');
            $v = str_replace(['+', '/'], ['-', '_'], $v);

            $ret[] = $v;
        }
        return implode('.', $ret);
    }

    public static function urldecode64(string $val)
    {
        if(!preg_match('#^([A-Za-z0-9_-]+)([.]([A-Za-z0-9_-]+))*$#', $val))
        {
            return false;
        }

        $ret = [];
        foreach (explode('.', $val) as $v)
        {
            $v = str_replace(['-', '_'], ['+', '/'], $v);

            $rem = strlen($v) & 3;

            if($rem == 2)
            {
                $v .= '==';
            }
            else if($rem == 3)
            {
                $v .= '=';
            }
            else if($rem != 0)
            {
                return false;
            }

            $ret[] = $v;
        }
        return implode('.', $ret);
    }

    /**
     * Метод, который получает перманентный токен на низком уровне. Можно сформировать любой запрос.
     * @param array $arData Массив для получения ключа
     * @return bool|string Шифровка
     */
    public static function store(array $arData)
    {
        $data = serialize($arData);
        $uniqueKey = hash('sha512', $data);
        Option::set(static::COMPONENT_AJAX_OPT, $uniqueKey, $data);
        return $uniqueKey;
    }

    /**
     * Возвращает данные по указазнному перманентному токену
     * @param string $uniqueKey Шифровка
     * @return array|false Данные
     */
    public static function restore(string $uniqueKey)
    {
        try
        {
            $return = Option::get(static::COMPONENT_AJAX_OPT, $uniqueKey, '');
            $return = unserialize($return);
            if(!is_array($return))
                $return = false;
            return $return;
        }
        catch (\Throwable $exception)
        {
            return false;
        }
    }

    public static function storeFrom($component, string $templateName = null, array $arParams = null)
    {
        return static::store(static::from($component, $templateName, $arParams));
    }

    /**
     * Парсит запрос к серверу
     * @return mixed Распарсенный запрос
     */
    public static function parseRequest()
    {
        if( isset( $_SERVER['CONTENT_TYPE'] ) && strpos( $_SERVER['CONTENT_TYPE'], "application/json" ) !== false)
        {
            $str = file_get_contents('php://input');
            return json_decode($str, true);
        }
        else
        {
            return $_POST;
        }
    }

    /**
     * Парсит JSON-запрос к серверу
     * @return mixed Распарсенный запрос или null, если JSON-запрос отсутствует или его не удалось распарсить
     */
    public static function parseJsonRequest()
    {
        if( isset( $_SERVER['CONTENT_TYPE'] ) && strpos( $_SERVER['CONTENT_TYPE'], "application/json" ) !== false)
        {
            $str = file_get_contents('php://input');
            return json_decode($str, true);
        }
        else
        {
            return null;
        }
    }

    /**
     * Переписать параметры запроса по данным, пришедшим из JSON POST, если таковой есть
     * @param bool $bForce Отдавать приоритет JSON в $_REQUEST
     * (В POST приоритет всегда у JSON)
     */
    public static function applyJsonRequest($bForce = false)
    {
        if($json = static::parseJsonRequest())
        {
            if($bForce)
            {
                $_REQUEST = array_merge($_REQUEST, $json);
            }
            else
            {
                $_REQUEST += $json;
            }
            $_POST = array_merge($_POST, $json);
        }
    }
}