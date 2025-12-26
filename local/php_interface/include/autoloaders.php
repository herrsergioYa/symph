<?
    if(!defined('CAN_REGISTER_BX_PSR4_NAMESPACE')) {
        define('CAN_REGISTER_BX_PSR4_NAMESPACE', defined('SM_VERSION') && explode('.', SM_VERSION)[0] > 20);
    }
	
    /** @var \Composer\Autoload\ClassLoader[] $composerClassLoader */
    global $composerClassLoaders;

    if(defined('USE_PSR04_AUTOLOADER')
        && (USE_PSR04_AUTOLOADER == 'Y' || USE_PSR04_AUTOLOADER == 'A' && empty($composerClassLoaders))) {
        if (file_exists(__DIR__ . '/psr04.php')) {
            require_once __DIR__ . '/psr04.php';
        }
    }

	/** @var ?\Psr4AutoloaderClass $psr4ClassLoader */
    global $psr4ClassLoader;
	
    if(defined("LOCAL_CLASSES_DIRECTORIES") && LOCAL_CLASSES_DIRECTORIES)
    {
        if(!empty($composerClassLoaders))
        {
            if (!in_array('local_classes_autoload', spl_autoload_functions()))
            {
                $composerClassLoaders[0]->addPsr4("", LOCAL_CLASSES_DIRECTORIES);
                $composerClassLoaders[0]->setClassMapAuthoritative(false);
            }
        }
        else if($psr4ClassLoader && $psr4ClassLoader instanceof \Psr4AutoloaderClass)
        {
            //If it is already here...
            foreach ((array)LOCAL_CLASSES_DIRECTORIES as $dir)
            {
                $psr4ClassLoader->addNamespace('', $dir);
            }
        }
        else if(function_exists('local_classes_autoload'))
        {
            if (!in_array('local_classes_autoload', spl_autoload_functions()))
            {
                spl_autoload_register('local_classes_autoload');
            }
        }
        else if(class_exists(\Psr4AutoloaderClass::class, false)
            || file_exists(__DIR__ . '/classes/Psr4AutoloaderClass.php'))
        {
            (function(&$psr4ClassLoader){
                if(!class_exists(\Psr4AutoloaderClass::class, false)) {
                    require_once __DIR__ . '/classes/Psr4AutoloaderClass.php';
                }
                $psr4ClassLoader = new \Psr4AutoloaderClass();
                $psr4ClassLoader->register();
            })($psr4ClassLoader);
            foreach ((array)LOCAL_CLASSES_DIRECTORIES as $dir)
            {
                $psr4ClassLoader->addNamespace('', $dir);
            }
        }
		/*else if($psr4ClassLoader && $psr4ClassLoader instanceof \Psr4AutoloaderClass
		    || class_exists(\Psr4AutoloaderClass::class, false)
		    || file_exists(__DIR__ . '/classes/Psr4AutoloaderClass.php'))
		{
			if(!($psr4ClassLoader && $psr4ClassLoader instanceof \Psr4AutoloaderClass))
			{
                if(!class_exists(\Psr4AutoloaderClass::class, false))
                {
                    require_once __DIR__ . '/classes/Psr4AutoloaderClass.php';
                }
				$psr4ClassLoader = new \Psr4AutoloaderClass();
				$psr4ClassLoader->register();
			}
			foreach ((array)LOCAL_CLASSES_DIRECTORIES as $dir)
			{
				$psr4ClassLoader->addNamespace('', $dir);
			}
		}*/
		else if(defined('CAN_REGISTER_BX_PSR4_NAMESPACE') && CAN_REGISTER_BX_PSR4_NAMESPACE === true)
		{
			//Avoid this branch at any costs!!
			$arAutoLoadClasses = [];
			foreach (LOCAL_CLASSES_DIRECTORIES as $dir)
			{
				$dir = rtrim($dir, '/\\');
				foreach (scandir($dir) as $subdir)
				{
					if($subdir == '.' || $subdir == '..')
					{
						continue;
					}

					$fulldir = $dir . '/' . $subdir;

					if(is_dir($fulldir))
					{
						\Bitrix\Main\Loader::registerNamespace('\\' . $subdir, $fulldir);
					}
					else if(strlen($subdir) > 4 && substr($subdir, -4) == '.php')
					{
						if(strpos($fulldir, $_SERVER['DOCUMENT_ROOT']) === 0)
						{
							$arAutoLoadClasses[substr($subdir, 0, -4)] = substr($fulldir, strlen($_SERVER['DOCUMENT_ROOT']) + 1);
						}
					}
				}
			}
			if($arAutoLoadClasses)
			{
				\Bitrix\Main\Loader::registerAutoLoadClasses(null, $arAutoLoadClasses);
			}
			unset($arAutoLoadClasses);
		}
    }

    if (function_exists('highload_block_autoload'))
    {
        if (!in_array('highload_block_autoload', spl_autoload_functions()))
        {
            spl_autoload_register('highload_block_autoload');
        }
    }

    if (function_exists('iblock_orm_autoload'))
    {
        if (!in_array('iblock_orm_autoload', spl_autoload_functions()))
        {
            spl_autoload_register('iblock_orm_autoload');
        }
    }

    if(function_exists('db_micro_orm_autoload'))
    {
        if(defined('REDBEANPHP_LIB_FILE') || defined('MEDOO_LIB_FILE'))
        {
            if (!in_array('db_micro_orm_autoload', spl_autoload_functions()))
            {
                spl_autoload_register('db_micro_orm_autoload');
            }
        }
    }
	
    if(defined("LOCAL_LIB_DIRECTORIES") && LOCAL_LIB_DIRECTORIES)
    {
        if(defined('CAN_REGISTER_BX_PSR4_NAMESPACE') && CAN_REGISTER_BX_PSR4_NAMESPACE === true)
		{
			foreach (LOCAL_LIB_DIRECTORIES as $pseudoModule => $dirs)
            {
                $namespace = '\\' . str_replace('.', '\\', ucwords($pseudoModule, '.'));
                foreach ((array)$dirs as $dir)
                {
                    \Bitrix\Main\Loader::registerNamespace($namespace, $dir);
                }
            }
		}
		else if(function_exists('local_lib_autoload'))
		{
			if (!in_array('local_lib_autoload', spl_autoload_functions()))
			{
				spl_autoload_register('local_lib_autoload');
			}
		}
		//Not truly appropriate substitutions! But better than nothing...
		else if(!empty($composerClassLoader))
        {
            foreach (LOCAL_LIB_DIRECTORIES as $pseudoModule => $dirs)
			{
				$namespace = '\\' . str_replace('.', '\\', ucwords($pseudoModule, '.'));
				foreach ((array)$dirs as $dir)
				{
					$composerClassLoader->addPsr4($namespace, (array)$dir);
				}
			}
        }
        else if($psr4ClassLoader && $psr4ClassLoader instanceof \Psr4AutoloaderClass
            || class_exists(\Psr4AutoloaderClass::class, false)
            || file_exists(__DIR__ . '/classes/Psr4AutoloaderClass.php'))
		{
			if(!($psr4ClassLoader && $psr4ClassLoader instanceof \Psr4AutoloaderClass))
			{
                if(!class_exists(\Psr4AutoloaderClass::class, false))
                {
                    require_once __DIR__ . '/classes/Psr4AutoloaderClass.php';
                }
				$psr4ClassLoader = new \Psr4AutoloaderClass();
				$psr4ClassLoader->register();
			}
			foreach (LOCAL_LIB_DIRECTORIES as $pseudoModule => $dirs)
			{
				$namespace = '\\' . str_replace('.', '\\', ucwords($pseudoModule, '.'));
				foreach ((array)$dirs as $dir)
				{
					$psr4ClassLoader->addNamespace($namespace, $dir);
				}
			}
        }
    }

    if (function_exists('gsv_component_loader'))
    {
        if (!in_array('gsv_component_loader', spl_autoload_functions()))
        {
            spl_autoload_register('gsv_component_loader');
        }
    }

    if (function_exists('gsv_module_loader'))
    {
        if (!in_array('gsv_module_loader', spl_autoload_functions()))
        {
            spl_autoload_register('gsv_module_loader');
        }
    }