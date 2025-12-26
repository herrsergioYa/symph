<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Catalog;
use Bitrix\Highloadblock as HL;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Basket;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\PriceMaths;

class CStdAjaxComponent extends CBitrixComponent
{
    /**
     * @var array $arOriginalParams;
     */
    public $arOriginalParams;

    /**
     * @var string $action;
     */
    public $action = '';


	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public function onPrepareComponentParams($params)
	{
		$this->arOriginalParams = $params;

		return $params;
	}

	public static function sendJsonAnswer($result)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		header('Content-Type: application/json');

		echo \Bitrix\Main\Web\Json::encode($result);

		\CMain::FinalActions();
		die();
	}

	// making correct names for actions (camel case without '_')
	protected function getCorrectActionName($action)
	{
		$action = str_replace('_', ' ', trim((string)$action));

		return str_replace(' ', '', lcfirst(ucwords($action)));
	}

	protected function prepareAction()
	{
		$action = (string)$this->request->get($this->arParams['ACTION_VARIABLE']);

		$action = $this->getCorrectActionName($action);
		if (empty($action))
		{
			$action = 'initialLoad';
		}

		return $action;
	}

	protected function doAction($action)
	{
		$funcName = $action.'Action';

		if (is_callable([$this, $funcName]))
		{
			$this->{$funcName}();
		}
	}

	protected function initialLoadAction()
	{
        $this->applyTemplateMutator($this->arResult);
		$this->IncludeComponentTemplate();
	}

    protected function refreshAction()
    {
        $this->applyTemplateMutator($this->arResult);
        static::sendJsonAnswer($this->arResult['JS_DATA']);
    }

	protected function applyTemplateMutator(&$result)
	{
		if ($this->initComponentTemplate())
		{
			$template = $this->getTemplate();
			$templateFolder = $template->GetFolder();

            $action = $this->action;
            $params = $this->arParams;

			if (!empty($templateFolder))
			{
				$file = new Main\IO\File(Main\Application::getDocumentRoot().$templateFolder.'/mutator.php');

				if ($file->isExists())
				{
					include($file->getPath());
				}
			}
		}
	}

	public function executeComponent()
	{
		if ($this->includeModules())
		{
            $this->arResult['JS_DATA'] = [];

			$this->action = $this->prepareAction();
			$this->doAction($this->action);

            parent::executeComponent();
		}
	}

    public function includeModules()
    {
        return true;
    }

}
