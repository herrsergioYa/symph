<?php

    //use \Bitrix\Main\ModuleManager;
    use \Bitrix\Main\Localization\Loc;
    use \Bitrix\Main\Config\Option;
    //use \Bitrix\Main\EventManager;
    use \Bitrix\Main\Loader;

    use \local_lib;

    $module_id = local_lib::MODULE_STATIC_ID;

    Loader::includeModule($module_id);

    Loc::getMessage($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
    Loc::getMessage(__FILE__);


    function displayProperty($arOption, $module_id)
    {
        $val = Option::get($module_id, $arOption[0], $arOption[3]);
        $type = $arOption[2];

        ?>
        <tr>
            <td valign="top" width="50%"><?
                if ($type[0] == "checkbox")
                    echo "<label for=\"" . htmlspecialcharsbx($arOption[0]) . "\">" . $arOption[1] . "</label>";
                else
                    echo $arOption[1];
                ?>:
            </td>
            <td valign="top" width="50%"><?
                if ($type[0] == "checkbox"):
                    ?><input type="checkbox" name="<?
                echo htmlspecialcharsbx($arOption[0]) ?>" id="<?
                echo htmlspecialcharsbx($arOption[0]) ?>" value="Y"<?
                    if ($val == "Y") echo " checked"; ?> /><?
                elseif ($type[0] == "text"):
                    ?><input type="text" size="<?
                echo $type[1] ?>" maxlength="255" value="<?
                echo htmlspecialcharsbx($val) ?>" name="<?
                echo htmlspecialcharsbx($arOption[0]) ?>" /><?
                elseif ($type[0] == "textarea"):
                    ?><textarea rows="<?
                echo $type[1] ?>" cols="<?
                echo $type[2] ?>" name="<?
                echo htmlspecialcharsbx($arOption[0]) ?>"><?
                    echo htmlspecialcharsbx($val) ?></textarea><?
                elseif ($type[0] == "select"):
                    ?><select name="<?
                echo htmlspecialcharsbx($arOption[0]) ?>" value="<?= htmlspecialcharsbx($val)?>">
                    <?foreach ($type[1] as $k => $v):?>
                        <option value="<?= htmlspecialcharsbx($k) ?>"<?= $k == $val ? ' selected' : ''?>><?= htmlspecialcharsbx($v) ?></option>
                    <?endforeach;?>
                </select><?
                endif;
                ?></td>
        </tr>
        <?
    }

    function displayInfo($name, $data, $raw = false)
    {
        ?>
        <tr>
            <td valign="top" width="50%"><?
                echo htmlspecialcharsbx($name);
                ?>:
            </td>
            <td valign="top" width="50%"><?
                echo $raw ? $data : htmlspecialcharsbx($data);
                ?></td>
        </tr>
        <?
    }

    function displayProperties($arAllOptions, $module_id)
    {
        foreach ($arAllOptions as $arOption):

            displayProperty($arOption, $module_id);

        endforeach;
    }

    function displayButton($text, $cmd)
    {
        ?>
            <button class="adm-btn my-cmd-btn" data-cmd="<?= $cmd ?>"><?= htmlspecialcharsbx($text) ?></button>
        <?
    }

    function displayButtons($list)
    {
        ?>
        <tr>
            <td colspan="2" class="adm-workarea" style="text-align: center; vertical-align: middle">
            <?
                foreach ($list as $item)
                {
                    displayButton($item['text'], $item['cmd']);
                }
            ?>
            </td>
        </tr>
        <?
    }

    $arIblocks = [];
    if(Loader::includeModule('iblock')) {
        $dbIblocks = \CIBlock::GetList();
        while($arIblock = $dbIblocks->Fetch()) {
            $arIblocks[$arIblock['ID']] = '[' . $arIblock['ID'] . '] ' . $arIblock['NAME'];
        }
    }

    $rights = $APPLICATION->GetGroupRight($module_id);
    if ($rights>="R") :

        $aTabs = array();

        $aTabs[] = array("DIV" => "edit1", "TAB" => Loc::getMessage('OPT_LOCAL_LIB_TAB'), "ICON" => "seo_settings", "TITLE" => Loc::getMessage('OPT_LOCAL_LIB_TAB'));
        $aTabs[] = array("DIV" => "edit_access", "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"), "ICON" => "seo_settings", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS"));

        require_once __DIR__ . '/default_option.php';

        $arOptions = Array(

            "edit1" => Array(
                "url" => Array("url", Loc::getMessage('OPT_LOCAL_LIB_URL'), array("text"), $local_lib_default_option["url"]),
                "http_auth" => Array("http_auth", Loc::getMessage('OPT_LOCAL_LIB_HTTP_AUTH'), array("text"), $local_lib_default_option["http_auth"]),
                "product_iblock_id" => Array("product_iblock_id", Loc::getMessage('OPT_LOCAL_LIB_PRODUCT_IBLOCK_ID'), array("select", $arIblocks), $local_lib_default_option["product_iblock_id"]),
                "offers_iblock_id" => Array("offers_iblock_id", Loc::getMessage('OPT_LOCAL_LIB_OFFERS_IBLOCK_ID'), array("select", $arIblocks), $local_lib_default_option["offers_iblock_id"]),
                "brand_iblock_id" => Array("brand_iblock_id", Loc::getMessage('OPT_LOCAL_LIB_BRAND_IBLOCK_ID'), array("select", $arIblocks), $local_lib_default_option["brand_iblock_id"]),
            ),

        );

        $tabControl = new CAdminTabControl("tabControl", $aTabs);


        if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
        {
            if (strlen($RestoreDefaults) > 0)
            {
                Option::delete($module_id);

                $z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
                while($zr = $z->Fetch())
                    $APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
            }
            else
            {
                foreach ($arOptions as $arAllOptions)
                {
                    foreach ($arAllOptions as $arOption)
                    {
                        $name = $arOption[0];
                        $val = $_POST[$name];
                        if ($arOption[2][0] == "checkbox" && $val != "Y")
                            $val = "N";

                        Option::set($module_id, $name, $val);
                    }
                }
            }
        }

        $tabControl->Begin();
        ?>
        <form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>" name="seo_settings">
        <?=bitrix_sessid_post();?>
        <?

        $tabControl->BeginNextTab();

        displayProperties($arOptions["edit1"], $module_id);

//        $list[] = array(
//            'text' => Loc::getMessage(""),
//            'cmd' => 'update'
//        );
//
//        if($list)
//        {
//            displayButtons($list);
//        }

        $tabControl->BeginNextTab();

        //group_rights2 work some strange
        //require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");

        $tabControl->Buttons();?>
        <script language="JavaScript">
        function confirmRestoreDefaults()
        {
            return confirm('<?echo AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>');
        }
        </script>
        <input type="submit" name="Update" value="<?echo Loc::getMessage("MAIN_SAVE")?>" class="adm-btn-save">
        <input type="hidden" name="Update" value="Y">
        <input type="submit" name="RestoreDefaults" OnClick="return confirmRestoreDefaults();" value="<?echo Loc::getMessage("MAIN_RESET")?>">
        <?$tabControl->End();?>
        </form>

    <?endif;?>
