<?php


namespace Gsv\Bitrix;



class CronAgent extends Agent
{
    const MAX_COUNT = 3;

    const SKIP_MISSED_EXECUTIONS = false;

    /**
     * Функция вызова стандартного (безымянного) агента ядром по cron
     * @param string $cronSchedule Расписание
     * @param $lastTimestamp mixed Время последнего исполнения
     * @param mixed ...$params Параметры вызова
     * @return string Новая команда агента
     * @throws \Exception
     */
    public static function cron($cronSchedule, $lastTimestamp, ...$params)
    {
        return static::_cron('', $cronSchedule, $lastTimestamp, $params);
    }

    /**
     * Вызов стандартных и нестандартных агентов по расписанию cron
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param string $cronSchedule Расписание
     * @param $lastTimestamp mixed Время последнего исполнения
     * @param $params array Параметры
     * @return string Результат выполнения для ядра
     * @throws \Exception
     */
    protected static function _cron($method, $cronSchedule, $lastTimestamp, $params)
    {
        $ob = new static();
        $return = $ob->_cron_($method, $cronSchedule, $lastTimestamp, $params);
        return static::_getReturnScheduleFor($method, $cronSchedule, $lastTimestamp, $params, $return);
    }

    /**
     * Вызов стандартных и нестандартных агентов по расписанию cron
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param string $cronSchedule Расписание
     * @param $lastTimestamp mixed Время последнего исполнения
     * @param $params array Параметры
     * @return string Результат выполнения
     * @throws \Exception
     */
    protected function _cron_($method, $cronSchedule, &$lastTimestamp, $params)
    {
        $params = array_values($params);
        $cron = \Cron\CronExpression::factory($cronSchedule);

        $lastTime = new \DateTime();
        $now = $lastTime->getTimestamp();
        $lastTime->setTimestamp($lastTimestamp);
        $nextTime = $cron->getNextRunDate($lastTime);

        $return = true;
        $maxCount = static::MAX_COUNT;

        while($nextTime->getTimestamp() <= $now && $maxCount-- > 0)
        {
            $ob = isset($this) ? $this : new static();

            $lastTimestamp = $nextTime->getTimestamp();

            //Если мы не под CLI, но метод этого треюует, то откладываем исполнение агента с теми же параметрами
            if (static::isCli() || !in_array(strtolower($method), array_map('strtolower', $ob->arRunOnlyWhenCli)))
            {
                $methodExec = 'execute' . ucfirst($method);
                $return = $ob->$methodExec(...$params);
            }
            else
            {
                return true;
            }

            if(is_array($return))
            {
                $params = $return;
            }
            elseif (is_string($return))
            {
                return $return;
            }
            elseif ($return || $return === null)
            {
                $return = $params;
            }
            else
            {
                return false;
            }

            if($this->shouldSkipMissedExecutions())
            {
                $currentDate = new \DateTime();
                //$nextTime = $cron->getNextRunDate($currentDate);
                $lastTimestamp = $currentDate->getTimestamp();
                break;
            }
            $nextTime = $cron->getNextRunDate($nextTime);
            $now = (new \DateTime())->getTimestamp();
        }

        return $return;
    }

    /**
     * Метод, определющий надо пропускать пропущенные исполнения
     * @return bool По сути: да - непериодический агент, нет - периодический
     */
    protected function shouldSkipMissedExecutions()
    {
        return static::SKIP_MISSED_EXECUTIONS;
    }

    /**
     * Метод регистрации стандартного (безымянного) агента  на cron
     * @param string $schedule Schedule
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
     * @throws \Exception
     */
    public static function schedule(
        $schedule = '0 * * * *',
        $params = [],

        $period = "N",
        $interval = 30,
        $next_exec = "",
        $active = "Y",
        $user_id = 1,

        $updateIfExists = true,

        // $datecheck = "", // first check for execution time
        $sort = 100,
        $module = ''
    )
    {
        return static::_schedule('', $schedule, $params, $period, $interval, $next_exec, $active, $user_id, $updateIfExists, $sort, $module);
    }

    /**
     * Метод регистрации агента на cron
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param string $schedule Schedule
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
     * @throws \Exception
     */
    protected static function _schedule(
        $method = '',
        $schedule = '0 * * * *',
        $params = [],

        $period = "N",
        $interval = 30,
        $next_exec = "",
        $active = "Y",
        $user_id = 1,

        $updateIfExists = true,

        // $datecheck = "", // first check for execution time
        $sort = 100,
        $module = ''
    )
    {
        $lastTimestamp = (new \DateTime())->getTimestamp();
        $name = static::_getCronCommand($method, $schedule, $lastTimestamp, $params);
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
     * Синтетика функций вызова нестандартного (именованного) агента ядром
     * @param $method string Имя агента
     * @param $arguments array Аргументы
     * @return string Результат выполнения для ядра
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments)
    {
        //Методы class::cronИмяАгента(расписание, параметры)
        if(stripos($method, 'cron') === 0)
        {
            if(count($arguments) < 2)
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            $method = substr($method, 4);
            $schedule = array_shift($arguments);
            if(!is_string($schedule))
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            $lastTime = array_shift($arguments);
            if(!is_numeric($lastTime))
            {
                throw new \Exception("Cron $method without lastTime used!");
            }
            return static::_cron($method, $schedule, $lastTime, $arguments);
        }
        //Методы class::getCronCommandForИмяАгента(расписание, параметры)
        elseif(stripos($method, 'getCronCommandFor') === 0)
        {
            if(count($arguments) < 2)
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            $method = substr($method, strlen('getCronCommandFor'));
            $schedule = array_shift($arguments);
            if(!is_string($schedule))
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            $lastTime = array_shift($arguments);
            if(!is_numeric($lastTime))
            {
                throw new \Exception("Cron $method without lastTime used!");
            }
            return static::_getCronCommand($method, $schedule, $lastTime, $arguments);
        }
        //Методы class::scheduleИмяАгента(параметры)
        elseif(stripos($method, 'schedule') === 0)
        {
            if(count($arguments) < 1)
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            $method = substr($method, strlen('schedule'));
            $schedule = array_shift($arguments);
            if(!is_string($schedule))
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            return static::_schedule($method, $schedule, ...$arguments);
        }
        else
        {
            return parent::__callStatic($method, $arguments);
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
        //Методы $this->cronИмяАгента(расписание, параметры)
        if(stripos($method, 'cron') === 0)
        {
            if(count($arguments) < 2)
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            $method = substr($method, 4);
            $schedule = array_shift($arguments);
            if(!is_string($schedule))
            {
                throw new \Exception("Cron $method without schedule used!");
            }
            $lastTime = array_shift($arguments);
            if(!is_numeric($lastTime))
            {
                throw new \Exception("Cron $method without lastTime used!");
            }
            $ret = $this->_cron_($method, $schedule, $lastTime, $arguments);
            return static::_getReturnScheduleFor($method, $schedule, $lastTime, $arguments, $ret);
        }
        else
        {
            return parent::__call($method, $arguments);
        }
    }

    /**
     * Создание cron-команды для ядра
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param $cronSchedule string Расписание
     * @param $lastTimestamp mixed Время последнего исполнения
     * @param $arguments array
     * @param $ret mixed Параметры
     * @return string Команда для вызова ядром
     * @throws \Exception
     */
    protected static function _getReturnScheduleFor($method, $cronSchedule, $lastTimestamp, $arguments, $ret): string
    {
        if (is_string($ret))
        {
            return $ret;
        }
        elseif (is_array($ret))
        {
            return static::_getCronCommand($method, $cronSchedule, $lastTimestamp, $ret);
        }
        elseif ($ret || $ret === null) // For compatibility ... :-(
        {
            return static::_getCronCommand($method, $cronSchedule, $lastTimestamp, $arguments);
        }
        else
        {
            return '';
        }
    }

    /**
     * Синтезирует вызов стандартного (безымянного) агента для ядра с указанными параметрами
     * @param $schedule string Расписание
     * @param $lastTimestamp mixed Время последнего исполнения
     * @param mixed ...$params Параметры
     * @return string
     * @throws \Exception
     */
    public static function getCronCommand($schedule, $lastTimestamp, ...$params): string
    {
        return static::_getCronCommand('', $schedule, $lastTimestamp, $params);
    }

    /**
     * Создание команды вызова стандартных и нестандартных агентов
     * @param $method string Имя агента или (для безымянного) пустая строка
     * @param $schedule string Расписание
     * @param $lastTimestamp mixed Время последнего исполнения
     * @param $args array Параметры
     * @return string Команда для вызова ядром
     * @throws \Exception
     */
    protected static function _getCronCommand($method, $schedule, $lastTimestamp, $args): string
    {
        $method = ucfirst($method);
        $lastTimestamp = intval($lastTimestamp);
        //if(empty($lastTimestamp))
        //    $lastTimestamp = (new \DateTime())->getTimestamp();
        $params = array_merge([$schedule, intval($lastTimestamp)], array_values($args));
        if($params)
        {
            $params = array_map(function($param) {
                return var_export($param, true);
            }, $params);
            $params = implode(', ', $params);
            return '\\' . static::class . "::cron" . $method . "(" . $params . ');';
        }
        else
        {
            throw new \Exception('Impossible condition!');
            //return '\\' . static::class . "::cron" . $method . "();";
        }
    }
}