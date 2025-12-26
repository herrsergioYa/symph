<?php
/**
 * Created by PhpStorm.
 * User: Gaevoy Sergey
 * Date: 23.02.2020
 * Time: 23:08
 * gaevoy@2amedia.ru
 * https://bitbucket.org/gaevoy_sergey/usefull-goodies/src/master/
 */

namespace Gsv\Bitrix;

/**
 * Базовый класс для агентов
 */
class Agent
{
    public function __construct()
    {
        $arModules = [];
        $this->getModulesToLoad($arModules);
        foreach ($arModules as $sModule)
        {
            if (!\Bitrix\Main\Loader::requireModule($sModule))
            {
                throw new \Exception('No module ' . $sModule . ' found!');
            }
        }
        $this->initAgent();
    }

    /**
     * Функция стандартного (безымянного) агента, которую необходимо переопределить.
     * @param mixed ...$params Параметры запуска агента
     * @return mixed Нечто страшное
     * Если возвращается строка, то это новая команда агента
     * Если возвращает null (или ничего не возвращается) или что-то приводимое к true,
     * то агент будет вызван с теми же параметрами
     * Во всех остальных случаях агент повторно не вызывается
     */
    public function execute(...$params)
    {
        return false;
    }

    /**
     * Функция вызова стандартного (безымянного) агента ядром
     * @param mixed ...$params  Параметры вызова
     * @return string Новая команда агента
     */
    public static function run(...$params)
    {
        return static::_run('', $params);
    }

    /**
     * Синтезирует вызов стандартного (безымянного) агента для ядра с указанными параметрами
     * @param mixed ...$params Параметры
     * @return string
     */
    public static function getAgentCommand(...$params): string
    {
        return static::_getAgentCommand('', $params);
    }

    /**
     * Метод регистрации стандартного (безымянного) агента
     * @param array $params Параметры
     * @param string $period check for agent execution count in period of time (Y/N)
     * @param int $interval time interval between execution (sec)
     * @param string $next_exec first execution time
     * @param string $active is the agent active or not (Y/N)
     * @param int $user_id user (ID or false)
     * @param boolean $updateIfExists Перенастроить агента, если уже задан
     * @param int $sort order
     * @param string $module agent's module
     * @return bool|int Положительное число - ID созданного агента, отрицательное число - ID перенастроенного агента с противоположным знаком, false - ошибка созданяи агента
     */
    public static function register(
        $params = [],

        $period = "N",
        $interval = 86400,
        $next_exec = "",
        $active = "Y",
        $user_id = 1,

        $updateIfExists = true,

        // $datecheck = "", // first check for execution time
        $sort = 100,
        $module = ''
    )
    {
        return static::_register('', $params, $period, $interval, $next_exec, $active, $user_id, $updateIfExists, $sort, $module);
    }

    /**
     * Синтетика функций вызова нестандартного (именованного) агента ядром
     * @param $method string Имя агента
     * @param $arguments array Аргументы
     * @return string Результат выполнения для ядра
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments)
    {
        //Методы class::runИмяАгента(параметры)
        if(stripos($method, 'run') === 0)
        {
            $method = substr($method, 3);
            return static::_run($method, $arguments);
        }
        //Методы class::getAgentCommandForИмяАгента(параметры)
        elseif(stripos($method, 'getAgentCommandFor') === 0)
        {
            $method = substr($method, strlen('getAgentCommandFor'));
            return static::_getAgentCommand($method, $arguments);
        }
        //Методы class::registerИмяАгента(параметры)
        elseif(stripos($method, 'register') === 0)
        {
            $method = substr($method, strlen('register'));
            //$method = lcfirst($method);
            return static::_register($method, ...$arguments);
        }
        else
        {
            throw new \Exception("No method $method found!");
        }
    }

    /**
     * Вспомогательная функция вызова агента на уже существующем экземпляре
     * @param $method string Имя агента
     * @param $arguments array Аргументы
     * @return string Результат выполнения для ядра
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        //Методы $this->runИмяАгента(параметры)
        if(stripos($method, 'run') === 0)
        {
            $method = substr($method, 3);
            $ret = $this->_run_($method, $arguments);
            return static::_getReturnCommandFor($method, $arguments, $ret);
        }
        else
        {
            return static::__callStatic($method, $arguments);
        }
    }

    /**
     * Инициализация агента
     * В текущей реализации просто загружает требуемые модули
     * @throws \Bitrix\Main\LoaderException
     */
    protected function initAgent()
    {

    }

    /**
     * Задает список модулей, необходимых агенту для работы
     * @param $arModules array Список модулей
     */
    public function getModulesToLoad(&$arModules)
    {

    }

    /**
     * Вызов стандартных и нестандартных агентов на классе
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param $params array Параметры
     * @return string Результат выполнения для ядра
     * @throws \Exception
     */
    protected static function _run($method, $params)
    {
        $ob = new static();
        $ret = $ob->_run_($method, $params);
        return static::_getReturnCommandFor($method, $params, $ret);
    }

    /**
     * Вызов стандартных и нестандартных агентов на экземпляре
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param $params array Параметры
     * @return string Результат
     */
    protected function _run_($method, $params)
    {
        //Если мы не под CLI, но метод этого треюует, то откладываем исполнение агента с теми же параметрами
        if (!static::isCli() && in_array(strtolower($method), array_map('strtolower', $this->arRunOnlyWhenCli)))
            return true;
        $methodExec = 'execute' . ucfirst($method);
        $ret = $this->$methodExec(...$params);
        return $ret;
    }

    /**
     * Создание команды вызова стандартных и нестандартных агентов
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param $params array Параметры
     * @return string Команда для вызова ядром
     */
    protected static function _getAgentCommand($method, $params): string
    {
        $method = ucfirst($method);
        if($params)
        {
            $params = array_map(function($param) {
                return var_export($param, true);
            }, $params);
            $params = implode(', ', $params);
            return '\\' . static::class . "::run" . $method . "(" . $params . ');';
        }
        else
        {
            return '\\' . static::class . "::run" . $method . "();";
        }
    }

    /**
     * Метод регистрации агента
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param array $params Параметры
     * @param string $period check for agent execution count in period of time (Y/N)
     * @param int $interval time interval between execution (sec)
     * @param string $next_exec first execution time
     * @param string $active is the agent active or not (Y/N)
     * @param int $user_id user (ID or false)
     * @param boolean $updateIfExists Перенастроить агента, если уже задан
     * @param int $sort order
     * @param string $module agent's module
     * @return bool|int Положительное число - ID созданного агента, отрицательное число - ID перенастроенного агента с противоположным знаком, false - ошибка созданяи агента
     */
    protected static function _register(
        $method = '',
        $params = [],

        $period = "N",
        $interval = 86400,
        $next_exec = "",
        $active = "Y",
        $user_id = 1,

        $updateIfExists = true,

        // $datecheck = "", // first check for execution time
        $sort = 100,
        $module = ''
    )
    {
        $name = static::_getAgentCommand($method, $params);
        $z = \CAgent::GetList([], [
            'NAME' => $name,
            'USER_ID' => $user_id ?: false,
        ]);
        if (!($agent = $z->Fetch()))
        {
            $arFields = array(
                "MODULE_ID" => $module,
                "SORT" => $sort,
                "NAME" => $name,
                "ACTIVE" => $active,
                "AGENT_INTERVAL" => $interval,
                "IS_PERIOD" => $period,
                "USER_ID" => $user_id ?: false,
            );

            $next_exec = static::ensureNextExec($interval, $next_exec);
            if ($next_exec != '')
                $arFields["NEXT_EXEC"] = $next_exec;

            $ID = \CAgent::Add($arFields);
            return $ID;
        }
        else if($updateIfExists)
        {
            $arFields = array(
                "MODULE_ID" => $module,
                "SORT" => $sort,
                "NAME" => $name,
                "ACTIVE" => $active,
                "AGENT_INTERVAL" => $interval,
                "IS_PERIOD" => $period,
                "USER_ID" => $user_id ?: false,
            );

            $next_exec = static::ensureNextExec($interval, $next_exec);
            if ($next_exec != '')
                $arFields["NEXT_EXEC"] = $next_exec;

            if(\CAgent::Update($agent['ID'], $arFields))
                return -$agent['ID'];
            else
                return false;
        }
        else
        {
            return -$agent['ID'];
        }
    }

    /**
     * Создание команды для ядра
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param $arguments
     * @param $ret mixed Параметры
     * @return string Команда для вызова ядром
     */
    protected static function _getReturnCommandFor($method, $arguments, $ret): string
    {
        if (is_string($ret))
        {
            return $ret;
        }
        elseif (is_array($ret))
        {
            return static::_getAgentCommand($method, $ret);
        }
        elseif ($ret || $ret === null) // For compatibility ... :-(
        {
            return static::_getAgentCommand($method, $arguments);
        }
        else
        {
            return '';
        }
    }

    /**
     * Определяет, запущен ли текущий скрипт из-под консоли.
     * Выполнение из-под консоли может быть принуждено определением
     * define('CLI', true);
     * @return bool
     */
    public static function isCli()
    {
        if(defined('CLI'))
        {
            if(CLI === true || CLI === 'Y')
                return true;
            elseif(CLI === false || CLI === 'N')
                return false;
        }
        return php_sapi_name () == 'cli';
    }

    /**
     * Массив имен методов, которые можно запускать только под CLI.
     * Рекомендуется устанавливать при инициализации агента.
     */
    protected $arRunOnlyWhenCli = [];

    /**
     * Ограничено ли выполнение агента только CLI
     * @param string $method Имя агента или (для безымянного) пустая строка
     * @return bool Результат
     */
    protected function isRunAllowedOnlyOnCli($method = ''): bool
    {
        return array_search($method, $this->arRunOnlyWhenCli) !== false;
    }

    /**
     * Разрешить выполнять агента только на CLI
     * @param string|array $method Имя или имена агентов или (для безымянного) пустая строка
     */
    protected function allowRunOnlyOnCli($method = '')
    {
        if(is_array($method))
        {
            foreach ($method as $m)
            {
                self::allowRunOnlyOnCli($m);
            }
            return;
        }
        if(!$this->isRunAllowedOnlyOnCli($method))
        {
            $this->arRunOnlyWhenCli[] = strtolower($method);
        }
    }

    /**
     * Разрешить выполнять агента везде
     * @param string|array $method Имя или имена агентов или (для безымянного) пустая строка
     */
    protected function allowRunEverywhere($method = '')
    {
        if(is_array($method))
        {
            foreach ($method as $m)
            {
                self::allowRunEverywhere($m);
            }
            return;
        }
        $method = strtolower($method);
        if(($pos = array_search($method, $this->arRunOnlyWhenCli)) !== false)
        {
            unset($this->arRunOnlyWhenCli[$pos]);
        }
    }

    /**
     * Метод, что формирует части для разделения работы
     * Работает и со старым GetList(), и с D7 getList()
     * @param mixed $filter Текущий фильтр
     * @param int $nChunkSize Размер порции
     * @return array Параметры выборки
     * @throws \Exception Если передан некорректный фильтр
     */
    protected static function formChunk($filter, $nChunkSize = 100) : array
    {
        if(is_numeric($filter))
        {
            return [
                'sort' => [
                    'ID' => 'ASC',
                ],
                'filter' => [
                    '>ID' => $filter,
                ],
                'navStartParams' => [
                    'nTopCount' => $nChunkSize,
                ],
                'getList' => [
                    'filter' => [
                        '>ID' => $filter,
                    ],
                    'order' => [
                        'ID' => 'ASC',
                    ],
                    'limit' => $nChunkSize,
                ],
                'lastID' => $filter,
            ];
        }
        elseif(is_array($filter))
        {
            return [
                'sort' => [
                ],
                'filter' => $filter,
                'navStartParams' => false,
                'getList' => [
                    'filter' => $filter,
                    'order' => [
                    ],
                ],
                'lastID' => 0,
            ];
        }
        else
        {
            throw new \Exception('Incorrect filter: ' . var_export($filter, true));
        }
    }

    /**
     * Формирует ответ для следующего вызова агента
     * @param array|int $filter Используемый фильтр
     * @param int $lastID Последний обработанный ID или 0 для старта
     * @param bool|null $bMustRepeat Надо повторить заход? (По умолчанию повторяем, если is_numeric($filter))
     * @param array $additionalParams Дополнительные аргументы для вызова агента
     * @return array|bool Значение, которое должен ыернуть агент
     * @throws \Exception Если передан некорректный фильтр
     */
    protected static function formNextChunk($filter, $lastID, $bMustRepeat = null, ...$additionalParams)
    {
        if(is_null($bMustRepeat))
        {
            $bMustRepeat = is_numeric($filter);
        }
        if(is_numeric($filter))
        {
            $filter = intval($filter);
            $lastID = intval($lastID);
            if($filter < $lastID)
            {
                return array_merge([$lastID], array_values($additionalParams));
            }
            elseif($bMustRepeat)
            {
                return array_merge([0], array_values($additionalParams));
            }
            else
            {
                return false;
            }
        }
        elseif(is_array($filter))
        {
            if($bMustRepeat)
            {
                return array_merge([$filter], array_values($additionalParams));
            }
            else
            {
                return false;
            }
        }
        else
        {
            throw new \Exception('Incorrect filter: ' . var_export($filter, true));
        }
    }

    /**
     * Установщие времени первого исполнения
     * Обходит ограничения Bitrix
     * @param int $interval Интервал времени между исполнениями
     * @param mixed $next_exec Текущее значение следующего времни исполнения
     * @return string Время первого исполнения после обработки
     */
    protected static function ensureNextExec($interval, $next_exec)
    {
        // $num = 0;
//        while(!\CTimeZone::Enabled())
//        {
//            \CTimeZone::Enable();
//            $num++;
//        }

        if (is_numeric($next_exec))
            $next_exec = \ConvertTimeStamp(time() + intval($next_exec), 'FULL');
        if ($next_exec === true)
            $next_exec = \ConvertTimeStamp(time() + $interval, 'FULL');
        $next_exec = (string)$next_exec;

//        while ($num > 0)
//        {
//            \CTimeZone::Disable();
//            $num--;
//        }

        return $next_exec;
    }

    /**
     * Проверяет, является ли представленный фильтр фильтром последовательного перебора
     * @param string|int|array $filter Фильтр
     * @return bool Является ?
     */
    public static function isSequential($filter)
    {
        return is_numeric($filter);
    }

    /**
     * Произошел ли перезапуск последовательности?
     * @param string|int|array $filter Фильтр
     * @param array|bool $return Новые аргументы вызова
     * @return bool Произошел ли?
     */
    protected static function wasLastIterationInSequence($filter, $return)
    {
        return static::isSequential($filter) && (is_array($return) && is_numeric($return[0]) && $return[0] == 0 || $return === false);
    }
}