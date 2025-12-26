<?php

namespace Gsv\Bitrix;

use \Bitrix\Main\EventManager;

/**
 * Базовый класс для создания методв RestAPI
 * В правилах трансляции адресов надо бы написать
 * #^/rest/# /bitrix/services/rest/index.php
 * На многих инсталяциях этого почему-то нет... :-(
 * Поэтому есть обход путь в /local/ajax/restApi.php
 */
class RestApi
{
    //There probably will be only one...
    //BUT we can create som instances manually...
    protected static $arSingletons = [];

    protected $scope, $class;

    /**
     * Получение экземпляра-синглтона для конкретного класса
     * Вызываем через static
     * @return mixed
     */
    public static function getInstance()
    {
        if(empty(self::$arSingletons[static::class]))
        {
            self::$arSingletons[static::class] = new static;
        }
        return self::$arSingletons[static::class];
    }


    /**
     * Генератор списка методов для события onRestServiceBuildDescription
     * Всякий метод, заканчивающийся на Action, становится методом API, но уже без Action
     * Префикс генерируется на основании имени класса с пространством имени
     * Все генерируется в змеином стиле
     * @return array Список доступных Bitrix методов
     */
    public function onRestServiceBuildDescription()
    {
        $ret = [];
        $prefix = static::toSnakeCase(static::getClass($this->class)) . '.';
        foreach(static::getClassActions($this->class) as $method)
        {
            $matches = [];
            if(preg_match('/^(.+)Action$/', $method, $matches))
            {
                $ret[$prefix . static::toSnakeCase($matches[1])] = [
                    'callback' => [$this->class, $method],
                    'options' => [],
                ];
            }
        }
        if($ret)
        {
            $ret = [$this->scope => $ret];
        }
        return $ret;
    }

    /**
     * Авторизация методов класса для события onRestCheckAuth
     * Вызывает canИмяМетод(), если таковой имеется. Если нет, то все разрешено по умолчанию.
     * @param mixed $query Параметры запроса (Почему НЕТ имени!?)
     * @param string $scope
     * @param mixed $res Ошибки
     * @return bool|null Можно вызывать?
     */
    public function onRestCheckAuth($query, $scope, &$res)
    {
        if($scope == $this->scope)
        {
            $method = 'can' . static::toCamelCase($this->scope, true);
            if(in_array($method, static::getClassCans($this->class)))
            {
                return $this->class->$method($query, $res);
            }
            return true;
        }
        return null;
    }

    /**
     * Обертка для привязки метода синглтона (без постфикса Handler)
     * Действует аналогично
     * @return array
     */
    public static function onRestServiceBuildDescriptionHandler() : array
    {
        return static::getInstance()->onRestServiceBuildDescription();
    }

    /**
     * Обертка для привязки метода синглтона (без постфикса Handler)
     * Действует аналогично
     * @param $query
     * @param $scope
     * @param $res
     * @return array
     */
    public static function onRestCheckAuthHandler($query, $scope, &$res)
    {
        return static::getInstance()->onRestCheckAuth($query, $scope, $res);
    }


    /**
     * RestApi constructor.
     * @param mixed $class Публикуем класс/объект. По умолчанию публикуется текущий объект с учетом наследования
     * @param mixed $scope Пространство RestAPI. По умолчанию генерируется из полного названия класса в змеином стиле
     */
    public function __construct($class = false, $scope = false)
    {
        if(empty($class))
        {
            $class = $this;
        }
        if(empty($scope))
        {
            $scope = static::toSnakeCase(static::getClass($class));
        }
        $this->class = $class;
        $this->scope = $scope;
    }

    /**
     * Переводит наименование из верблюжьего стиля в змеиный
     * @param string $name Наименования
     * @param bool $bx ИСпользоват заглавные буквы? (стиль константы)
     * @return string Резльтат
     */
    public static function toSnakeCase(string $name, $bx = false) : string
    {
        if(strpos($name, '\\') !== false)
        {
            $name = trim($name, '\\');
            $name = explode('\\', $name);
            foreach ($name as &$n)
            {
                $n = static::toSnakeCase($n);
            }
            $name = implode('.', $name);
        }
        else
        {
            $ret = '';
            $last = '';
            $name = str_split($name);
            foreach ($name as $i => $n)
            {
                if(ctype_upper($n))
                {
                    if(ctype_lower($last) )
                    {
                        $ret .= '_' . strtolower($n);
                    }
                    else
                    {
                        $ret .= strtolower($n);
                    }
                }
                else
                {
                    $ret .= $n;
                }
                $last = $n;
            }
            $name = $ret;
        }

        if($bx)
        {
            $name = strtoupper($name);
        }

        return $name;
    }

    /**
     * Переводит наименование из змеиного стиля в верблюжий
     * @param string $name Наименование
     * @param bool $upperFirst Заглавна ли первая буква?
     * @return string Результат
     */
    public static function toCamelCase(string $name, $upperFirst = true) : string
    {
        if(strpos($name, '.') !== false)
        {
            $name = trim($name, '.');
            $name = explode('.', $name);
            foreach ($name as &$n)
            {
                $n = static::toCamelCase($n);
            }
            $name = implode('\\', $name);
        }
        else
        {
            $ret = '';
            $last = '';
            $name = str_split($name);
            foreach ($name as $i => $n)
            {
                if($n == '_')
                {

                }
                elseif($last == '')
                {
                    $ret .= $upperFirst ? strtoupper($n) : strtolower($n);
                }
                elseif($last == '_')
                {
                    $ret .= strtoupper($n);
                }
                else
                {
                    $ret .= strtolower($n);
                }
                $last = $n;
            }
            $name = $ret;
        }

        return $name;
    }

    /**
     * Генерация текста JS-метода для вызова методов RestAPI
     * @param string $name Иия JS-метода (может быть анонимным!)
     * @param string|bool $prefix Префикс, используемый при вызове (может быть пустым)
     * @param string $endpoint Явный endpoint, если отличается от стандартного
     * если указано false, то используется полное имя класса в змеином стиле)
     * @return false|string Текст JS-метода
     */
    public static function getJsMethod($name = '', $prefix = '', $endpoint = '/local/restApi.php/')
    {
        if($prefix === false)
        {
            $prefix = static::toSnakeCase(static::class);
        }
        if(strlen($prefix))
        {
            $prefix .= '.';
        }

        ob_start();

        ?>

        //<script type="application/javascript">

        function <?= $name ?>(method, params, callback, async = true) {

            var xhr = function () {
                if (!!window.XDomainRequest)
                    return new XDomainRequest();
                else
                    return new XMLHttpRequest();
            };

            var xhr = xhr(),
                url = '/<?= trim($endpoint, '/') ?>/<?= $prefix ?>' + method + '.json';

            xhr.open('POST', url, async);

            xhr.setRequestHeader("Content-type", "application/json");

            var bRequestCompleted = false;

            var isSuccess = function (xhr) {
                return typeof xhr.status == 'undefined' || (xhr.status >= 200 && xhr.status < 300) || xhr.status === 304 || xhr.status >= 400 && xhr.status < 500 || xhr.status === 1223 || xhr.status === 0;
            };

            // IE fix
            xhr.onprogress = function () {
            };
            if (async) {
                xhr.ontimeout = function () {
                };
                xhr.timeout = 0;
            }

            xhr.onload = function (event) {
                if (bRequestCompleted)
                    return;

                bRequestCompleted = true;

                xhr.onload = function () {
                };

                var bSuccess = isSuccess(xhr);
                var data;

                if (bSuccess) {
                    data = xhr.responseText;

                    if (data.length > 0) {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {
                            bSuccess = false;
                            data = {
                                error: e.message,
                                data: data
                            };
                        }
                    } else {
                        data = {result: {}};
                    }
                } else {
                    data = {
                        error: xhr.statusText,
                        data: xhr.responseText
                    };
                }

                var s = xhr.status;

                xhr = null;

                if (bSuccess) {
                    if (callback) {
                        callback.apply(window, [data]);
                    }
                } else { // Should I change anything?
                    if (callback) {
                        callback.apply(window, [data]);
                    }
                }
            };

            xhr.onerror = function (event) {
                if (callback) {
                    callback.apply(window, [{
                        error: xhr.statusText,
                        data: xhr.responseText
                    }]);
                }
            };

            xhr.send(JSON.stringify(params));

            return xhr;
        }
        //</script>
        <?

        return ob_get_clean();
    }

    /**
     * Генерация текста JS-метода для вызова методов RestAPI
     * @param string $name Иия JS-метода (может быть анонимным!)
     * @param string $endpoint Явный endpoint, если отличается от стандартного
     * если указано false, то используется полное имя класса в змеином стиле)
     * @return false|string Текст JS-метода
     */
    public function getJsMethodSlim($name = '', $endpoint = '/local/restApi.php/')
    {;
        return static::getJsMethod($name, $this->scope, $endpoint);
    }

    /**
     * Генерация текста JS-объекта для вызова методов RestAPI
     * @param mixed $class Параметр $class из __construct
     * @param mixed $scope Параметр $scope из __construct
     * @param string $endpoint Явный endpoint, если отличается от стандартного
     * если указано false, то используется полное имя класса в змеином стиле)
     * @return false|string Текст JS-объекта
     */
    public static function getJsObject($class = false, $scope = false, $endpoint = '/local/restApi.php/')
    {
        if(empty($class))
        {
            $class = static::class;
        }
        if(empty($scope))
        {
            $scope = static::toSnakeCase(static::getClass($class));
        }

        ob_start();

        ?>
        //<script type="application/javascript">
        ((function () {
            <?= static::getJsMethod('ret', $scope, $endpoint) ?>
            <?foreach (array_keys(static::getClassActions($class)) as $method):?>
                ret.<?= $method ?> = function(params, callback, async = true) {
                    return ret('<?= $method ?>', params, callback, async);
                };
            <?endforeach;?>
            return ret;
        })())
        //</script>
        <?

        return ob_get_clean();
    }

    /**
     * Генерация текста JS-объекта для вызова методов RestAPI
     * @param string $endpoint Явный endpoint, если отличается от стандартного
     * если указано false, то используется полное имя класса в змеином стиле)
     * @return false|string Текст JS-объекта
     */
    public function getJsObjectSlim($endpoint = '/local/restApi.php/')
    {
        return static::getJsObject($this->class, $this->scope, $endpoint);
    }

    /**
     * Получает имя класса.
     * Если передана строка, то она и считается именем класса
     * Если передан объект, то возвращается его класс
     * Иначе false
     * @param $class
     * @return bool|string
     */
    public static function getClass($class)
    {
        if(is_object($class))
            return get_class($class);
        elseif(is_string($class))
            return $class;
        else
            return false;
    }

    /**
     * Получает спиcок методов, заканчивающихся на Action
     * @param mixed $class Указанный класс
     * @return array|bool
     */
    public static function getClassActions($class)
    {
        if(is_object($class) || is_string($class))
        {
            $ret = [];
            foreach (get_class_methods($class) as $method)
            {
                $matches = [];
                if(preg_match('/^(.+)Action$/', $method, $matches))
                {
                    $ret[$matches[1]] = $method;
                }
            }
            return $ret;
        }

        return false;
    }

    /**
     * Получает спиcок методов, начинающихся с can
     * @param mixed $class Указанный класс
     * @return array|bool
     */
    public static function getClassCans($class)
    {
        if(is_object($class) || is_string($class))
        {
            $ret = [];
            foreach (get_class_methods($class) as $method)
            {
                $matches = [];
                if(preg_match('/^can(.+)$/', $method, $matches))
                {
                    $ret[lcfirst($matches[1])] = $method;
                }
            }
            return $ret;
        }

        return false;
    }

    /**
     * Восстанавливает имя модуля по имена класса/обьекта по соглашению D7
     * @param bool $class Класс или объект
     * @return array|bool|string Имя модуля или false, если восстановление не удалось
     */
    public static function getModule($class = false)
    {
        if(empty($class))
        {
            $class = static::class;
        }

        $name = static::toSnakeCase(static::getClass($class));
        $name = explode('.', $name);
        if(count($name) < 3)
        {
            return false;
        }
        if($name[0] == 'bitrix')
        {
            $name = $name[1];
        }
        else
        {
            $name = $name[0] . '.' . $name[1];
        }
        return $name;
    }

    /**
     * Регистрирует текущий класс на обработчики через registerEventHandler()
     * @param EventManager|null $eventManager
     * @param string|null $moduleId Имя модуля, от лица которого проиходит регистрация (по умолчанию восстанавливается из имени класса)
     */
    public static function registerEventHandler(EventManager $eventManager = null, string $moduleId = null)
    {
        if(empty($moduleId))
        {
            $moduleId = static::getModule();
        }

        if(empty($eventManager))
        {
            $eventManager = EventManager::getInstance();
        }

        $eventManager->registerEventHandler(
            "rest", "OnRestServiceBuildDescription",
            $moduleId,
            static::class,
            "OnRestServiceBuildDescriptionHandler"
        );

        $eventManager->registerEventHandler(
            "rest", "OnRestCheckAuth",
            $moduleId,
            static::class,
            "OnRestCheckAuthHandler"
        );
    }

    /**
     * Отменяется вышеуказанную регистрацию через unRegisterEventHandler()
     * @param EventManager|null $eventManager
     * @param string|null $moduleId Имя модуля, от лица которого проиходит регистрация (по умолчанию восстанавливается из имени класса)
     */
    public static function unRegisterEventHandler(EventManager $eventManager = null, string $moduleId = null)
    {
        if(empty($moduleId))
        {
            $moduleId = static::getModule();
        }

        if(empty($eventManager))
        {
            $eventManager = EventManager::getInstance();
        }

        $eventManager->unRegisterEventHandler(
            "rest", "OnRestServiceBuildDescription",
            $moduleId,
            static::class,
            "OnRestServiceBuildDescriptionHandler"
        );

        $eventManager->unRegisterEventHandler(
            "rest", "OnRestCheckAuth",
            $moduleId,
            static::class,
            "OnRestCheckAuthHandler"
        );
    }

    /**
     * Регистрирует текущий класс на обработчики через addEventHandler()
     * @param EventManager|null $eventManager
     * @return array
     */
    public static function addEventHandler(EventManager $eventManager = null)
    {
        if(empty($eventManager))
        {
            $eventManager = EventManager::getInstance();
        }

        $ret = [];

        $ret[] = $eventManager->addEventHandler(
            "rest", "OnRestServiceBuildDescription",
            [static::class, "OnRestServiceBuildDescriptionHandler"]
        );

        $ret[] = $eventManager->addEventHandler(
            "rest", "OnRestCheckAuth",
            [static::class, "OnRestCheckAuthHandler"]
        );

        return $ret;
    }

    /**
     * Регистрирует текущий объект на обработчики через addEventHandler()
     * @param EventManager|null $eventManager
     * @return array
     */
    public function addEventHandlerEx(EventManager $eventManager = null)
    {
        if(empty($eventManager))
        {
            $eventManager = EventManager::getInstance();
        }

        $ret = [];

        $ret[] = $eventManager->addEventHandler(
            "rest", "OnRestServiceBuildDescription",
            [$this, "OnRestServiceBuildDescription"]
        );

        $ret[] = $eventManager->addEventHandler(
            "rest", "OnRestCheckAuth",
            [$this, "OnRestCheckAuth"]
        );

        return $ret;
    }
}