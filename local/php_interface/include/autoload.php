<?
	if(!function_exists('local_classes_autoload'))
    {
        /**
         * Автозагрузчик классов из папки /local/classes/
         * Классы должны хранится в папке по PSR-4
         * @param string $className Имя класса
         * @return bool Успешность загрузки
         */
        function local_classes_autoload($className)
        {
            if (class_exists($className, false))
            {
                return true;
            }

            $trimmedClassName = trim($className, '\\');

            foreach (LOCAL_CLASSES_DIRECTORIES as $dir)
            {
                //$dir = $_SERVER['DOCUMENT_ROOT'] . $dir;
                $file = $dir
                    . str_replace('\\', '/', $trimmedClassName)
                    . '.php';
                if (file_exists($file))
                {
                    (function ($file)
                    {
                        require_once $file;
                    })($file);
                    if (class_exists($className, false))
                    {
                        return true;
                    }
                }
            }

            return false;
        }
    }

	if(!function_exists('local_lib_autoload'))
	{
		/**
		 * Автозагрузчик классов из папки /local/lib/
		 * Классы должны хранится в папке по спецификации модулей словно они в LOCAL_LIB_MODULES
		 * @param string $className Имя класса
		 * @return bool Успешность загрузки
		 */
		function local_lib_autoload($className)
		{
			if (class_exists($className, false))
			{
				return true;
			}

			$trimmedClassName = trim($className, '\\');
			$trimmedClassName = strtolower($trimmedClassName);
			$arParts = explode('\\', $trimmedClassName);

			if (count($arParts) < 3)
			{
				return false;
			}

			$module = array_shift($arParts);
			$module .= '.';
			$module .= array_shift($arParts);

			if (!isset(LOCAL_LIB_DIRECTORIES[$module]))
			{
				return false;
			}

			foreach ((array)(LOCAL_LIB_DIRECTORIES[$module]) as $dir)
			{
				$file = $dir
					. implode('/', $arParts)
					. '.php';
				if (file_exists($file))
				{
					(function ($file)
					{
						require_once $file;
					})($file);
					if (class_exists($className, false))
					{
						return true;
					}
				}
			}

			return false;
		}
	}

    //Bitrix specific loaders follow...
    //TODO: Should I stop here if no Bitrix?

    if(!function_exists('highload_block_autoload'))
    {
        /**
         * Загружает ORM-обертку HighloadBlock'а при первом упоминании в коде.
         * Убирает необходимость вручную синтезировать сущность.
         * @param string $className Имя класса HighloadBlock'а по соглашению Bitrix.
         * @return bool Успешность загрузки
         */
        function highload_block_autoload($className)
        {
            if(!is_bitrix_included())
            {
                return false;
            }

            if (class_exists($className, false))
            {
                return true;
            }

            $className = trim(trim($className), '\\');

            if (strpos($className, '\\'))
            {
                return false;
            }

            if (strlen($className) > 5 && substr($className, -5) === 'Table')
            {
                if (\Bitrix\Main\Loader::includeModule('highloadblock'))
                {
                    $hlblName = substr($className, 0, -5);
                    /*$hlbl = \Bitrix\Highloadblock\HighloadBlockTable::getRow([
                        'filter' => [
                            'NAME' => $hlblName,
                        ],
                        'select' => [
                            'ID'
                        ],
                    ]);*/
                    if ($hlblName)//$hlbl)
                    {
                        $hlblClass = getHlblDataClass($hlblName);//$hlbl['ID']);
                        if('\\' . $className == $hlblClass)
                        {
                            return true;
                        }
                        else
                        {
                            //Should never happen!?
                            class_alias($hlblClass, '\\' . $className);
                            return true;
                        }
                    }
                }
            }

            return false;
        }
    }

    if(!function_exists('getHlblDataClass'))
    {
        /**
         * Загрузка имени класса HL-блока по ID
         * @param int|string|array $hlblID - HL-блок
         * @param bool $bUseCache Использовать кеш при первом и последующих обращениях
         * @return string|false - имя класса
         */
        function getHlblDataClass($hlblID, $bUseCache = true)
        {
            if(!is_bitrix_included())
            {
                return false;
            }

            static $arCache = [];

            if(\Bitrix\Main\Loader::includeModule('highloadblock'))
            {
                if(!$bUseCache)
                {
                    if ($entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblID))
                    {
                        $entity_data_class = $entity->getDataClass();

                        return $entity_data_class;
                    }
                }

                if ($hlblID && is_numeric($hlblID) && intval($hlblID) > 0)
                {
                    $hlblID = intval($hlblID);

                    if (isset($arCache[$hlblID]))
                    {
                        return $arCache[$hlblID];
                    }

                    if ($hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlblID)->fetch())
                    {
                        if ($entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock))
                        {
                            $entity_data_class = $entity->getDataClass();
                            $arCache[$hlblock['NAME']] = $arCache[$hlblock['ID']] = $entity_data_class;
                            return $entity_data_class;
                        }
                    }

                    $arCache[$hlblID] = false;
                }
                else if ($hlblID && is_string($hlblID) && strlen($hlblID) > 0)
                {
                    if (isset($arCache[$hlblID]))
                    {
                        return $arCache[$hlblID];
                    }

                    $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getRow([
                        'filter' => [
                            'NAME' => $hlblID,
                        ],
                        'select' => [
                            '*',
                        ],
                    ]);
                    if ($hlblock)
                    {
                        if ($entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock))
                        {
                            $entity_data_class = $entity->getDataClass();

                            //There may be some lower/uppercase tricks in $hlblID so ...
                            $arCache[$hlblID] = $arCache[$hlblock['NAME']] =
                                $arCache[$hlblock['ID']] = $entity_data_class;

                            return $entity_data_class;
                        }
                    }

                    $arCache[$hlblID] = false;
                }
                else if ($hlblID && is_array($hlblID))
                {
                    if (!empty($hlblID['ID']) && isset($arCache[$hlblID['ID']]))
                    {
                        if(!empty($hlblID['NAME']) && !isset($arCache[$hlblID['NAME']]))
                        {
                            return $arCache[$hlblID['NAME']] = $arCache[$hlblID['ID']];
                        }
                        return $arCache[$hlblID['ID']];
                    }
                    else if(!empty($hlblID['NAME']) && isset($arCache[$hlblID['NAME']]))
                    {
                        return $arCache[$hlblID['NAME']];
                    }

                    if (!empty($hlblID['ID']) && !empty($hlblID['NAME']) && !empty($hlblID['TABLE_NAME']))
                    {
                        if ($entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblID))
                        {
                            $entity_data_class = $entity->getDataClass();
                            //We don't NAME from the DB so we can do only that...
                            $arCache[$hlblID['NAME']] = $arCache[$hlblID['ID']] = $entity_data_class;
                            return $entity_data_class;
                        }
                    }
                    else if (!empty($hlblID['ID']) && is_numeric($hlblID['ID']) && intval($hlblID['ID']) > 0)
                    {
                        $entity_data_class = getHlblDataClass($hlblID['ID']);

                        //Everything is cached by the previous call

                        return $entity_data_class;
                    }
                    else if (!empty($hlblID['NAME']) && is_string($hlblID['NAME']) && strlen($hlblID['NAME']) > 0)
                    {
                        $entity_data_class = getHlblDataClass($hlblID['NAME']);

                        //Naught to cache!

                        return $entity_data_class;
                    }
                }
            }

            return false;
        }
    }

    if(!function_exists('iblock_orm_autoload'))
    {
        /**
         * Загружает ORM-обертку IBlock'а при первом упоминании в коде.
         * Убирает необходимость вручную синтезировать сущность.
         * @param string $className Имя класса IBlock'а по принципу \IblockElement<API_CODE>Table.
         * @return bool Успешность загрузки
         */
        function iblock_orm_autoload($className)
        {
            if(!is_bitrix_included())
            {
                return false;
            }

            if (class_exists($className, false))
            {
                return true;
            }

            $className = trim(trim($className), '\\');

            if (strpos($className, '\\'))
            {
                return false;
            }

            $classNameLower = strtolower($className);
            //$iBlock = false;
            //$apiCode = false;

            if (strlen($className) > 18 && substr($classNameLower, -12) === 'elementtable' && substr($classNameLower, 0, 6) == 'iblock')
            {
                if (\Bitrix\Main\Loader::includeModule('iblock'))
                {
                    $apiCode = substr($className, 6, -12);
                    if ($apiCode)
                    {
                        $iBlock = getIblockByCode(lcfirst($apiCode));
                        if($iBlock === null)
                        {
                            $iBlock = getIblockByCode($apiCode);
                        }

                        if($iBlock === null)
                        {
                            return false;
                        }

                        $iBlockId = $iBlock->getId();
                        $iBlockCode = $iBlock->getCode() ?: '';
                        $iBlockApiCode = $iBlock->getApiCode() ?: '';
                        $entity_data_class = $iBlock->getEntityDataClass();

                        if(!class_exists($entity_data_class, true))
                        {
                            return false;
                        }

                        $php = <<<PHP
                        
                        class $className extends $entity_data_class 
                        {
                            /*public*/ const IBLOCK_ID = $iBlockId;
                            /*public*/ const IBLOCK_CODE = '$iBlockCode';
                            /*public*/ const IBLOCK_API_CODE = "$iBlockApiCode";
                        }
PHP;

                        eval($php);

                        //return class_exists($className, false);
                    }
                }
            }
            else if (strlen($className) > 18 && substr($classNameLower, -12) === 'sectiontable' && substr($classNameLower, 0, 6) == 'iblock')
            {
                if (\Bitrix\Main\Loader::includeModule('iblock'))
                {
                    $apiCode = substr($className, 6, -12);
                    if ($apiCode)
                    {
                        $iBlock = getIblockByCode(lcfirst($apiCode));
                        if($iBlock === null)
                        {
                            $iBlock = getIblockByCode($apiCode);
                        }

                        if($iBlock === null)
                        {
                            return false;
                        }

                        $iBlockId = $iBlock->getId();
                        $iBlockCode = $iBlock->getCode() ?: '';
                        $iBlockApiCode = $iBlock->getApiCode() ?: '';
                        $entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($iBlockId);

                        if(!class_exists($entity, true))
                        {
                            return false;
                        }

                        $php = <<<PHP
                        
                        class $className extends $entity
                        {
                            /*public*/ const IBLOCK_ID = $iBlockId;
                            /*public*/ const IBLOCK_CODE = '$iBlockCode';
                            /*public*/ const IBLOCK_API_CODE = "$iBlockApiCode";
                        }
PHP;

                        eval($php);

                        //return class_exists($className, false);
                    }
                }
            }
            else if (strlen($className) > 10 && substr($classNameLower, -4) === 'desc' && substr($classNameLower, 0, 6) == 'iblock')
            {
                if (\Bitrix\Main\Loader::includeModule('iblock'))
                {
                    $apiCode = substr($className, 6, -4);
                    if ($apiCode)
                    {
                        $iBlock = getIblockByCode(lcfirst($apiCode));
                        if ($iBlock === null)
                        {
                            $iBlock = getIblockByCode($apiCode);
                        }

                        if ($iBlock === null)
                        {
                            return false;
                        }

                        $iBlockId = $iBlock->getId();
                        $iBlockCode = $iBlock->getCode() ?: '';
                        $iBlockApiCode = $iBlock->getApiCode() ?: '';

                        $php = <<<PHP
                        
                        class $className
                        {
                            /*public*/ const IBLOCK_ID = $iBlockId;
                            /*public*/ const IBLOCK_CODE = '$iBlockCode';
                            /*public*/ const IBLOCK_API_CODE = "$iBlockApiCode";
                        }
PHP;

                        eval($php);

                        //return class_exists($className, false);
                    }
                }
            }
            else
            {
                return false;
            }


            /*if($iBlock) {
                if(!class_exists("Iblock" . $iBlock->getApiCode() . 'Desc', false)) {
                    //TODO::Create it!
                }
                //There could be a fallback so...
                if(class_exists("Iblock" . $iBlock->getApiCode() . 'Desc', false)
                    && $apiCode && !class_exists("Iblock" . $apiCode . 'Desc', false)) {
                    class_alias("Iblock" . $iBlock->getApiCode() . 'Desc', "Iblock" . $apiCode . 'Desc', false);
                }
            }*/

            return class_exists($className, false);
        }
    }

    if(!function_exists('getIblockByCode'))
    {
        /**
         * Загрузка имени класса инфоблока по ID или API_CODE
         * @param int|string $code - идентификатор или код
         * @param bool $bUseCache Использовать кеш при первом и последующих обращениях
         * @return ?\Bitrix\Iblock\Iblock - Объект Iblock'a
         */
        function getIblockByCode($code, $bUseCache = true)
        {
            if(!is_bitrix_included())
            {
                return null;
            }

            static $arCache = [];

            if(\Bitrix\Main\Loader::includeModule('iblock'))
            {
                if ($code && is_numeric($code))
                {
                    $iBlockId = intval($code);

                    if($iBlockId <= 0)
                    {
                        return null;
                    }

                    if ($bUseCache && array_key_exists($iBlockId, $arCache))
                    {
                        return $arCache[$iBlockId];
                    }

                    $iBlock = \Bitrix\Iblock\Iblock::getByPrimary($iBlockId);

                    if(empty($iBlock))
                    {
                        $iBlock = null;
                    }

                    if ($bUseCache)
                    {
                        $arCache[$iBlockId] = $iBlock;
                        if($iBlock && ($apiCode = $iBlock->getApiCode()))
                        {
                            //We canNOT cache CODE 'cause it can be the same as another Iblock's API_CODE.
                            $arCache[$apiCode] = $iBlock;
                        }
                    }

                    return $iBlock;
                }
                else if ($code && is_string($code))
                {
                    if(strlen($code) <= 0)
                    {
                        return null;
                    }

                    if($bUseCache && array_key_exists($code, $arCache))
                    {
                        return $arCache[$code];
                    }

                    $iBlock = \Bitrix\Iblock\IblockTable::getList([
                        'filter' => ['=API_CODE' => $code],
                        'select' => ['ID'],
                        'limit' => 1,
                    ])->fetchObject();

                    if(empty($iBlock))
                    {
                        //Fallback
                        $iBlock = \Bitrix\Iblock\IblockTable::getList([
                            'filter' => ['=CODE' => $code],
                            'select' => ['ID'],
                            'limit' => 1,
                        ])->fetchObject();
                    }

                    if(empty($iBlock))
                    {
                        $iBlock = null;
                    }

                    if($bUseCache)
                    {
                        if($iBlock && ($iBlockId = $iBlock->getId()))
                        {
                            $arCache[$iBlockId] = $iBlock;
                        }
                        if($iBlock && ($apiCode = $iBlock->getApiCode()))
                        {
                            //$apiCode can (and will!) differ from $code if we did use the fallback.
                            $arCache[$apiCode] = $iBlock;
                        }
                        $arCache[$code] = $iBlock;
                    }

                    return $iBlock;
                }
                else
                {

                }
            }

            return null;
        }
    }


    if(!function_exists('db_micro_orm_autoload'))
    {
        function db_micro_orm_autoload($className)
        {
            static $hasRedBeanPhp = null;
            static $hasMedoo = null;

            if (class_exists($className, false))
            {
                return true;
            }

            $trimmedOnlyClassName = trim($className, '\\');
            $trimmedClassName = strtolower($trimmedOnlyClassName);

            $baseMedoo = false;

            if($trimmedClassName == 'r' || strpos($trimmedClassName, 'redbean_') === 0
                || strpos($trimmedClassName, 'redbeanphp\\') === 0)
            {
                if($hasRedBeanPhp === null)
                {
                    $hasRedBeanPhp = defined('REDBEANPHP_LIB_FILE') && is_string(REDBEANPHP_LIB_FILE)
                        && file_exists(REDBEANPHP_LIB_FILE);
                }

                if($hasRedBeanPhp)
                {
                    (function() {
                        require_once REDBEANPHP_LIB_FILE;
                    })();

					//composer's stubb
                    if(!class_exists(\RedBeanPHP\R::class, false)
                        && class_exists(\RedBeanPHP\Facade::class, false))
                    {
                        class_alias(\RedBeanPHP\Facade::class, \RedBeanPHP\R::class);
                    }

					//rb.php's stubbs
                    if(!class_exists(\R::class, false)
                        && class_exists(\RedBeanPHP\Facade::class, false))
                    {
                        class_alias(\RedBeanPHP\Facade::class, \R::class);
                    }

                    if(!class_exists(\RedBean_SimpleModel::class, false)
                        && class_exists(\RedBeanPHP\SimpleModel::class, false))
                    {
                        class_alias(\RedBeanPHP\SimpleModel::class, \RedBean_SimpleModel::class);
                    }

                    return class_exists($className, false);
                }
            }
            else if($trimmedClassName == 'medoo' || strpos($trimmedClassName, 'medoo\\') === 0)
            {
                if($hasMedoo === null)
                {
                    $hasMedoo = defined('MEDOO_LIB_FILE') && is_string(MEDOO_LIB_FILE)
                        && file_exists(MEDOO_LIB_FILE);
                }

                if($hasMedoo)
                {
                    (function() {
                       require_once MEDOO_LIB_FILE;
                    })();

                    //My stubb
                    if(!class_exists(\Medoo::class, false)
                        && class_exists(\Medoo\Medoo::class, false))
                    {
                        class_alias(\Medoo\Medoo::class, \Medoo::class);
                    }

                    return class_exists($className, false);
                }
            }
            else if(strpos($trimmedClassName, 'gsv\\bitrix\\db\\medoo_') === 0)
            {
                $baseMedoo = 'Gsv\\Bitrix\\Db\\Medoo';
            }
            else if(strpos($trimmedClassName, 'gsv\\util\\db\\medoo_') === 0)
            {
                $baseMedoo = 'Gsv\\Util\\Db\\Medoo';
            }

            if($baseMedoo && class_exists("\\${baseMedoo}", true))
            {
                $conn = substr($trimmedOnlyClassName, strlen($baseMedoo) + 1);

                if($conn == '' || strpos($conn, '\\') !== false)
                {
                    return false;
                }

                $namespace = substr($baseMedoo, 0, -6);

                $newClass =
<<<PHP
                namespace $namespace;

                class Medoo_$conn extends Medoo
                {
                    public function __construct()
                    {
                        parent::__construct("$conn");
                    }
                }
PHP;
                eval($newClass);

                return class_exists($className, false);
            }

            return false;
        }
    }

    if(!function_exists('gsv_component_loader'))
    {
        /**
         *
         * @param $className
         * @return boolean
         */
        function gsv_component_loader($className)
        {
            if (class_exists($className, false))
            {
                return true;
            }
            if(!class_exists(\CBitrixComponent::class))
            {
                return false;
            }

            static $namespaces = null;

            if($namespaces === null) {
                $namespaces = [];
                foreach(GSV_COMPONENTS_NAMESPACE as $k => $v) {
                    $k = trim($k, '\\') . '\\';
                    $k = mb_strtolower($k);
                    $namespaces[$k] = $v;
                }

                uksort($namespaces, function($a, $b) {
                   return strlen($b) <=> strlen($a);
                });
            }

            $trimmedClassName = trim($className, '\\');
            $trimmedClassNameLC = mb_strtolower($className);
            foreach ($namespaces as $namespace => $file) {
                if(strpos($trimmedClassNameLC, $namespace) === 0) {
                    $class = substr($trimmedClassName, strlen($namespace));
                    $template = false;//Isn't supported by now.
                    if(strpos($class, '\\') !== false) {
                        $parts = explode('\\', $class);
                        if(count($parts) == 2) {
                            //TODO: Parse the template!
                        }
                        continue;
                    }
                    if($file === true && $template !== false) {
                        //There is no class of a template!
                        continue;
                    }
                    if($file === false) {
                        return false;
                    }
                    $componentName = preg_replace('/([A-Z])/', '.$1', $class);
                    $componentName = ltrim($componentName, '.');
                    $componentName = mb_strtolower($componentName);
                    $componentName = "gsv:$componentName";

                    $class = \CBitrixComponent::includeComponentClass($componentName);

                    if($class === '' || $template !== false) {
                        $class = \CBitrixComponent::class;
                    }

                    //FIXME: It is always TRUE 'cause we cannot simply check if the component exists w/o creating it :'-(
                    //TODO: Should we disable the aliasing!?
                    if($class) {
                        if($file === true) {
//                            if($template !== false) {
//                                //There is no class of a template!
//                                continue;
//                            }
                            if(!class_exists($className)) {
                                class_alias($class, $className);
                            }
                            return true;
//                        } else if($file === false) {
//                            return false;
                        } else {
                            /** @var \CBitrixComponent $c */
                            $c = new $class();
                            if($template !== false) {
                                $c->initComponent($componentName, $template);
                                if($c->initComponentTemplate()) {
                                    $file = $c->getTemplate()->GetFolder() . '/' . $file;
                                } else {
                                    continue;
                                }
                            } else {
                                $c->initComponent($componentName);
                                $file = $c->getPath() . '/' . $file;
                            }
                            (function($file) {
                                require_once $_SERVER['DOCUMENT_ROOT'] . $file;
                            })($file);
                            if(class_exists($className, false)) {
                                return true;
                            }
                        }
                    }
                }
            }

            return !!class_exists($className, false);
        }
    }


    if(!function_exists('gsv_module_loader'))
    {
        /**
         *
         * @param $className
         * @return boolean
         */
        function gsv_module_loader($className)
        {
            if (class_exists($className, false))
            {
                return true;
            }
            if(!class_exists(\Bitrix\Main\ModuleManager::class)
                || !class_exists(\Bitrix\Main\Loader::class))
            {
                return false;
            }

            static $bLoading = false;

            static $oldModules = [
                'iblock', 'catalog', 'sale', 'search', 'forum', 'blog',

                //Some specific but popular classes
                'subscription' => 'subscribe',
                'rubric' => 'subscribe',
                'webservice',
                'soap' => 'webservice',
            ];

            if($bLoading)
            {
                return false;
            }

            $bLoading = true;

            try
            {
                $trimmedClassName = trim($className, '\\');
                $arParts = explode('\\', strtolower($trimmedClassName));

                $modules = [];

                if (count($arParts) < 3)
                {
                    if (count($arParts) == 1 && $arParts[0][0] == 'c')
                    {
                        $oldClass = substr($arParts[0], 1);
                        foreach ($oldModules as $i => $prefix)
                        {
                            if (strpos($oldClass, $prefix) === 0)
                            {
                                $modules[] = is_numeric($i) ? $prefix : $i;
                            }
                        }
                    }
                }
                else
                {
                    $vendor = array_shift($arParts);
                    $module = array_shift($arParts);
                    if ($vendor == 'bitrix')
                    {
                        $modules[] = $module;
                        //There are some "bitrix.***" modules so no else follows
                    }
                    $modules[] = $vendor . '.' . $module;
                }

                foreach ($modules as $module)
                {
                    if (\Bitrix\Main\ModuleManager::isModuleInstalled($module))
                    {
                        if (\Bitrix\Main\Loader::includeModule($module))
                        {
                            \Bitrix\Main\Loader::autoload($className);
                            if (class_exists($className, false))
                            {
                                return true;
                            }
                            /*if (class_exists($className, true))
                            {
                                return true;
                            }*/
                        }
                    }
                }
            }
            finally
            {
                $bLoading = false;
            }

            return false;
        }
    }

    if (!function_exists('is_bitrix_included'))
    {
        function is_bitrix_included(): bool
        {
            return defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true;
        }
    }