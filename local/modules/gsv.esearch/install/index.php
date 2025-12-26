<?
    use \Bitrix\Main\ModuleManager;
    use \Bitrix\Main\Localization\Loc;
    use \Bitrix\Main\Config\Option;
    use \Bitrix\Main\EventManager;
    use \Bitrix\Main\Loader;

    Loc::loadMessages(__FILE__);

    class gsv_esearch extends \CModule
    {

        const MODULE_STATIC_ID = "gsv.esearch";
        const DATETIME_FORMAT = "Y-m-d H:i:s";

        function __construct()
        {
            $arModuleVersion = array();
            include(dirname(__FILE__)."/version.php");
            $this->MODULE_ID = self::MODULE_STATIC_ID;
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME = GetMessage("gsv_esearch_MODULE_NAME");
            $this->MODULE_DESCRIPTION = GetMessage("gsv_esearch_MODULE_DESC");

            $this->PARTNER_NAME = "SeoComplex";
            $this->PARTNER_URI = "";
        }

        /**
         * @return string
         */
        protected static function GetOptionPageAddress()
        {
            return "/bitrix/admin/settings.php?mid=".self::MODULE_STATIC_ID."&lang=".LANGUAGE_ID;
        }

        function DoInstall()
        {
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            self::InstallAgents();
            ModuleManager::registerModule($this->MODULE_ID);
            //return true;
        }

        function DoUninstall()
        {
            $this->UnInstallDB();
            $this->UnInstallEvents();
            $this->UnInstallFiles();
            self::UnInstallAgents();
            ModuleManager::unRegisterModule($this->MODULE_ID);
            //return true;
        }

        function InstallDB()
        {
            global $DB;


        }

        function UnInstallDB()
        {
            global $DB;

            Option::delete(self::MODULE_STATIC_ID);
            CAdminNotify::DeleteByModule(self::MODULE_STATIC_ID);
        }


        static function InstallAgents()
        {

        }


        static function UnInstallAgents()
        {
            \CAgent::RemoveModuleAgents(self::MODULE_STATIC_ID);
        }

        function InstallEvents()
        {
            $eventManager = EventManager::getInstance();
            return true;
        }

        function UnInstallEvents()
        {
            $eventManager = EventManager::getInstance();
            return true;
        }

        function InstallFiles()
        {
            return true;
        }

        function UnInstallFiles()
        {
            return true;
        }

        function GetModuleRightList()
        {
            $arr = array(
                "reference_id" => array("D","R","W"),
                "reference" => array(
                    "[D] ".Loc::getMessage("gsv_esearch_DENIED"),
                    "[R] ".Loc::getMessage("gsv_esearch_OPENED"),
                    "[W] ".Loc::getMessage("gsv_esearch_FULL"))
            );
            return $arr;
        }
    }