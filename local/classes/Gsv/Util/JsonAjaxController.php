<?php


namespace Gsv\Util;

/**
 * Класс ajax-контроллера для PHP
 */
class JsonAjaxController
{
    /**
     * Имя переменной для передачи имени вызываемого метода
     */
    const ACTION_NAME = 'action';


    /**
     * Следует ли инстанцировать этот класс при использовании в режиме обработчика
     */
    const INSTANTIATED = true;

    /**
     * JsonAjaxController constructor.
     */
    public function __construct()
    {

    }

    /**
     * Метод, который обрабатывает ajax-запрос.
     * Может быть вызван вручную, если даже запроса не было.
     * Отправляет ответ в формате JSON.
     * @param string|object|null $clazz Класс или объект, которые обрабатывает запросы
     * (По умолчанию - текущий класс или создаваемый экземпляр контроллера)
     * @param mixed $ACTION_NAME Имя переменной, в котрой передается имя метода
     * @return bool Был ли найден метод для вызова?
     */
    public static function execute($clazz = null, $ACTION_NAME = false)
    {
        $ACTION_NAME = $ACTION_NAME ?: static::ACTION_NAME;
        $arParams = static::getAllParams();
        if($action = $arParams[$ACTION_NAME])
        {
            $method =  $action . 'Action';
            if(in_array($method, get_class_methods(static::getClassName($clazz))))
            {
                $callable = false;
                if(is_string($clazz))
                {
                    if(class_exists($clazz, true))
                    {
                        $callable = [$clazz, $method];
                    }
                }
                elseif(is_object($clazz))
                {
                    $callable = [$clazz, $method];
                }
                else
                {
                    if(static::INSTANTIATED)
                    {
                        $ob = new static();
                        $callable = [$ob, $method];
                    }
                    else
                    {
                        $callable = [static::class, $method];
                    }
                }
                if($callable && is_callable($callable))
                {
                    $response = static::exec($callable, $arParams);
                    static::render($response);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Метод, который обрабатывает ajax-запрос.
     * Может быть вызван вручную, если даже запроса не было.
     * Отправляет ответ в формате JSON. Если метод не был найден, возвращает JS.
     * @param mixed $ajax_url Адрес, по которому вызывается ajax (по умолчанию - URI текущего зарпоса)
     * @param mixed $clazz Класс или объект, которые обрабатывает запросы
     * (По умолчанию - текущий класс или объект контроллера)
     * @param mixed $ACTION_NAME Имя переменной, в котрой передается имя метода
     * @return bool Был ли найден метод для вызова?
     */
    public static function executeOrSendJs($clazz = null, $ajax_url = false, $ACTION_NAME = false)
    {
        if(static::execute($clazz, $ACTION_NAME))
        {
            return true;
        }
        static::renderJs($ajax_url, $clazz, $ACTION_NAME);
        return false;
    }

    /**
     * Отрисовка JS
     * @param mixed $ajax_url Адрес, по которому вызывается ajax (по умолчанию - URI текущего зарпоса)
     * @param mixed $clazz Класс или объект, которые обрабатывает запросы
     * (По умолчанию - текущий класс или объект контроллера)
     * @param mixed $ACTION_NAME Имя переменной, в котрой передается имя метода
     */
    protected static function renderJs($ajax_url = false, $clazz = null, $ACTION_NAME = false)
    {
        header('Content-Type: application/javascript');
        echo 'window.' . static::getDefaultJsName($clazz) . '=';
        echo static::writeJs($ajax_url, $clazz, $ACTION_NAME);
    }

    /**
     * Получает список методов для обработки ajax.
     * Метод, заканичвающийся на Action, считается обработчиком
     * @param mixed $clazz Класс, методы которого необходимо опубликовать (По умолчанию - текущий класс контроллера)
     * @param bool $encode Превратить список в json-строку
     * @return array|false|string Список методов, возможно, json-сериализованный
     */
    public static function getActions($clazz = null, $encode = false)
    {
        $clazz = static::getClassName($clazz);

        $actions = [];
        foreach (get_class_methods($clazz) as $method)
        {
            if(strlen($method) > 6 && substr($method, -6) == 'Action')
            {
                $actions[] = substr($method, 0, -6);
            }
        }
        if($encode)
        {
            $actions = json_encode($actions);
        }
        return $actions;
    }


    /**
     * Генератор кода метода JS для удаленного вызова методов
     * @param mixed $ajax_url Адрес, по которому вызывается ajax (по умолчанию - URI текущего зарпоса)
     * @param mixed $ajax_class Класс, методы которого необходимо опубликовать (По умолчанию - текущий класс контроллера)
     * @param bool $ACTION_NAME Имя переменной, в котрой передается имя метода
     * @return false|string Текст метода
     */
    public static function writeJs($ajax_url = false, $ajax_class = false, $ACTION_NAME = false)
    {
        $ACTION_NAME = $ACTION_NAME ?: static::ACTION_NAME;

        if(empty($ajax_url))
        {
            $ajax_url = $_SERVER['REQUEST_URI'];
        }

        ob_start();
        ?>
        //<script type="application/javascript">
        ((function (url, actions) {

            function callAjax(url, method, params, callback) {

                if (typeof method !== 'undefined' && method) {
                    if (url.includes('?')) {
                        url += '&<?= $ACTION_NAME ?>=' + method;
                    } else {
                        url += '?<?= $ACTION_NAME ?>=' + method;
                    }
                }

                if('fetch' in window) {

                    var req = {
                        method: 'POST',
                        body: params,
                    };


                    if (!(params instanceof FormData)) {
                        req.headers = {"Content-type": "application/json"};
                        req.body = JSON.stringify(params);
                    }

                    return fetch(url, req)
                        .then(response => response.json()/*, error => undefined*/)
                        .then(response => {
                            callback.apply(window, [response]);
                            return response;
                        }, error => {
                            callback.apply(window, [undefined]);
                            return undefined;
                        });

                } else {

                    var async = true;

                    var xhr = function () {
                        if (!!window.XDomainRequest)
                            return new XDomainRequest();
                        else
                            return new XMLHttpRequest();
                    };

                    var xhr = xhr();

                    xhr.open('POST', url, async);

                    if (!(params instanceof FormData)) {
                        xhr.setRequestHeader("Content-type", "application/json");
                        params = JSON.stringify(params);
                    }

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
                                callback.apply(window, [undefined]);
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

                    xhr.send(params);

                    return xhr;
                }
            }

            var ret = function (action, data, callback) {
                if('Promise' in window) {
                    return new Promise(function(resolve, reject) {
                        var wrapper = function (response) {
                            if(callback) {
                                callback.apply(window, [response]);
                            }
                            if(typeof response !== "undefined") {
                                resolve(response);
                            } else {
                                reject({});
                            }
                        }
                        callAjax(url, action, data, wrapper);
                    });
                } else {
                    return callAjax(url, action, data, callback);
                }
            };

            for (let i in actions) {
                let action = actions[i];
                ret[action] = function (data, callback) {
                    return ret(action, data, callback);
                }
            }

            ret.$ = function (data, callback) {
                return ret(undefined, data, callback);
            }

            return ret;
        })('<?= $ajax_url ?>', <?= static::getActions($ajax_class, true) ?>))
        //</script>
        <?
        return ob_get_clean();
    }

    /**
     * Имя объекта JS по умолчанию
     * @param mixed $clazz Имя публикуемого класса
     * @return mixed|string
     */
    public static function getDefaultJsName($clazz)
    {
        $clazz = static::getClassName($clazz);
        $clazz = trim($clazz, '\\');
        $clazz = str_replace('\\', '_', $clazz);
        return $clazz;
    }

    public static function getClassName($clazz)
    {
        if(is_object($clazz))
        {
            $className = get_class($clazz);
        }
        else if(is_string($clazz))
        {
            $className = $clazz;
        }
        else
        {
            $className = static::class;
        }
        return $className;
    }

    protected static function exec($callable, $arParams)
    {
        return $callable($arParams);
    }

    public static function getAllParams()
    {
        return array_merge($_GET, $_REQUEST, static::getPostParams());
    }

    public static function getPostParams()
    {
        if(static::isJsonRequest())
        {
            return json_decode(trim(file_get_contents('php://input')), true);
        }
        else
        {
            //Apply $_FILES?
            return (array)$_POST;
        }
    }

    public static function getJsonParams($sentinel = null)
    {
        if(static::isJsonRequest())
        {
            return json_decode(trim(file_get_contents('php://input')), true);
        }
        else
        {
            return $sentinel;
        }
    }

    public static function isJsonRequest()
    {
        return isset( $_SERVER['CONTENT_TYPE'] ) &&
            strpos( $_SERVER['CONTENT_TYPE'], "application/json" ) !== false;
    }

    public static function isPostRequest()
    {
        return $_SERVER["REQUEST_METHOD"] == "POST";
    }

    public static function isGetRequest()
    {
        return $_SERVER["REQUEST_METHOD"] == "GET";
    }

    /**
     * Переписать параметры запроса по данным, пришедшим из JSON POST, если таковой есть
     * @param bool $bForce Отдавать приоритет JSON в $_REQUEST
     * (В POST приоритет всегда у JSON)
     */
    public static function applyJsonParams($bForce = false)
    {
        if($json = static::getJsonParams())
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

    /**
     * Отрисовка ответа на ajax-запрос
     * @param mixed $response Ответ на запрос
     */
    protected static function render($response)
    {
        $charset = ini_get('default_charset') ?: 'utf-8';
        header('Content-Type: application/json; charset=' . $charset);
        echo json_encode($response);
    }

    public static function buildQuery($useSessId = 'Y', $ACTION_NAME = false, $SESSID_NAME = false)
    {
        $ACTION_NAME = $ACTION_NAME ?: static::ACTION_NAME;
        $SESSID_NAME = $SESSID_NAME ?: 'sessid';

        $query = urlencode($ACTION_NAME) . '=#action#';

        if($useSessId === 'Y')
            $query .= '&'. urlencode($SESSID_NAME) . '=#sessid#';
        elseif($useSessId === 'A')
            $query .= '&'. urlencode($SESSID_NAME) . '=' . urlencode(static::getSessId());
        elseif($useSessId !== 'N')
            $query .= '&'. urlencode($SESSID_NAME) . '=';

        return $query;
    }

    public static function getSessId()
    {
        if(empty($_SESSION['GSV_SESS_ID']))
        {
            if(class_exists(Security::class))
            {
                $_SESSION['GSV_SESS_ID'] = Security::base64_urlencode(Security::getRandomBytes(48));
            }
            else
            {
                $_SESSION['GSV_SESS_ID'] = hash('sha512', uniqid(rand(), true), false);
            }
        }

        return $_SESSION['GSV_SESS_ID'];
    }
}