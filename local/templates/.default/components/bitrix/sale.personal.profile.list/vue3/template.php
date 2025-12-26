<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?

if($arResult["ERROR_MESSAGE"] <> '')
{
	ShowError($arResult["ERROR_MESSAGE"]);
}
if($arResult["NAV_STRING"] <> '')
{
	?>
	<p><?=$arResult["NAV_STRING"]?></p>
	<?
}?>
<div class="mx-auto mw-480 address-wrap" id="<?= $arResult['VUE']['ROOT_ID'] ?>" v-cloak>
    <div class="block-radio address-item" :class="{'selected': profile.d.IS_MAIN_ADDRESS == 'Y'/*profile.ID == data.profileId*/}"
         v-for="profile in data.PROFILES">
        <label class="d-flex justify-content-between form-check-label p-4 pt-3 ms-4">
            <div class="pa radio-group">
                <input class="form-check-input" type="radio" name="delivery-address"
                       :checked="data.profileId == profile.ID" :value="profile.ID"
                       @change="selectProfile($event.target.value)" checked>
                <span class="radio"></span>
            </div>
            <div class="d-flex flex-column gap-3">
                <div class="fs-14">{{profile.d.COUNTRY}}, {{profile.d.CITY}}, {{profile.d.ZIP}}</div>
                <div>{{profile.d.ADDRESS}}</div>
            </div>
            <div class="d-flex flex-column justify-content-between align-items-end">
                <button class="btn btn-delete" type="button" title="<?= \data::get('REMOVE_ADDRESS') ?>" @click="remove(profile)" ref="removeBtn" __data-fancybox data-src="#address-confirm-delete"></button>
                <div><button class="fs-10 ttu btn-link" @click="edit(profile)" data-modal="#address-edit-modal"><?= \data::get('CHANGE_ADDRESS') ?></button></div>
            </div>
        </label>
    </div>

    <div class="d-flex align-items-center justify-content-center h-100" v-if="data.PROFILES.length == 0">
        <div class="mx-auto mb-10 w-100 mw-480 tac">
            <?= \data::get('NO_ADDRESS') ?>
            <?/*div class="mt-5">
                <button class="btn btn-100 btn-primary" type="button" data-modal="#address-add-modal">Добавить адрес</button>
            </div*/?>
        </div>
    </div>

    <div class="mt-5">
        <button class="btn btn-100 btn-primary" type="button" @click="add()" data-modal="#address-add-modal"><?= \data::get('ADD_NEW_ADDRESS') ?></button>
    </div>

    <Teleport to="#address-add-modal">
        <div class="d-flex justify-content-between align-items-center px-4 top">
            <span class="ttu"><?= \data::get('NEW_ADDRESS') ?></span>
            <button class="btn btn-link btn-close" type="button" data-modal-close><?= \data::get('CLOSE_ADDRESS') ?></button>
        </div>
        <div class="d-flex flex-column oh middle">
            <div class="mb-6 px-4">
                <?= \data::get('ADD_ADDRESSES') ?>
            </div>
            <form class="px-4" method="post" action="" id="address-edit-form">
                <div class="pr mb-6">
                    <select class="form-select w-100 placeholder-static" v-model="data.v.COUNTRY_ID">
                        <option :value="country.COUNTRY_ID" v-for="country in data.COUNTRIES">{{country.COUNTRY}}</option>
                    </select>
                    <label class="placeholder"><?= \data::get('COUNTRY_ADDRESS') ?></label>
                </div>
                <div class="row gx-6 mb-6">
                    <div class="col">
                        <div class="pr">
                            <input id="city" class="form-control" type="text" v-model="data.v.CITY" required>
                            <label class="placeholder" for="city"><?= \data::get('CITY_ADDRESS') ?></label>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="pr">
                            <input id="postcode" class="form-control" type="text" v-model="data.v.ZIP">
                            <label class="placeholder" for="postcode"><?= \data::get('ZIP_ADDRESS') ?></label>
                        </div>
                    </div>
                </div>
                <div class="pr mb-5">
                    <input id="address" class="form-control" type="text" v-model="data.v.ADDRESS" required>
                    <label class="placeholder" for="address"><?= \data::get('ADDRESS_ADDRESS') ?></label>
                </div>
                <label class="pr form-check-label d-inline-flex">
                    <div>
                        <input class="form-check-input" type="checkbox" name=""
                               :checked="data.v.IS_MAIN_ADDRESS == 'Y'"
                               @change="data.v.IS_MAIN_ADDRESS = $event.target.value ? 'Y' : 'N'">
                        <span class="checkbox"></span>
                    </div>
                    <span><?= \data::get('MAIN_ADDRESS') ?></span>
                </label>
            </form>
        </div>
        <div class="mt-auto p-4 bottom">
            <button class="btn btn-100 btn-primary" @click.prevent="create" form="address-edit-form"><?= \data::get('ADD_ADDRESS') ?></button>
        </div>
    </Teleport>
    <Teleport to="#address-edit-modal">
        <div class="d-flex justify-content-between align-items-center px-4 top">
            <span class="ttu"><?= \data::get('CHANGE_THE_ADDRESS') ?></span>
            <button class="btn btn-link btn-close" type="button" data-modal-close><?= \data::get('CLOSE_ADDRESS') ?></button>
        </div>
        <div class="d-flex flex-column oh middle" v-if="data.profile.v">
            <div class="mb-6 px-4">
                <?= \data::get('ADD_ADDRESSES') ?>
            </div>
            <form class="px-4" method="post" action="" id="address-edit-form">
                <div class="pr mb-6">
                    <select class="form-select w-100 placeholder-static" v-model="data.profile.v.COUNTRY_ID">
                        <option :value="country.COUNTRY_ID" v-for="country in data.COUNTRIES">{{country.COUNTRY}}</option>
                    </select>
                    <label class="placeholder"><?= \data::get('COUNTRY_ADDRESS') ?></label>
                </div>
                <div class="row gx-6 mb-6">
                    <div class="col">
                        <div class="pr">
                            <input id="city" class="form-control input-filled" type="text"
                                   v-model="data.profile.v.CITY" required>
                            <label class="placeholder" for="city"><?= \data::get('CITY_ADDRESS') ?></label>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="pr">
                            <input id="postcode" class="form-control input-filled" type="text"
                                   v-model="data.profile.v.ZIP">
                            <label class="placeholder" for="postcode"><?= \data::get('ZIP_ADDRESS') ?></label>
                        </div>
                    </div>
                </div>
                <div class="pr mb-5">
                    <input id="address" class="form-control input-filled" type="text"
                           v-model="data.profile.v.ADDRESS" required>
                    <label class="placeholder" for="address"><?= \data::get('ADDRESS_ADDRESS') ?></label>
                </div>
                <label class="pr form-check-label d-inline-flex">
                    <div>
                        <input class="form-check-input" type="checkbox" name=""
                               :checked="data.profile.v.IS_MAIN_ADDRESS == 'Y'"
                               @change="data.profile.v.IS_MAIN_ADDRESS = $event.target.value ? 'Y' : 'N'" checked>
                        <span class="checkbox"></span>
                    </div>
                    <span><?= \data::get('MAIN_ADDRESS') ?></span>
                </label>
            </form>
        </div>
        <div class="mt-auto p-4 bottom">
            <button class="btn btn-100 btn-primary" @click.prevent="save(data.profile)" form="address-edit-form"><?= \data::get('SAVE_ADDRESS') ?></button>
        </div>
    </Teleport>
</div>
<? $this->SetViewTarget('footer-scripts'); ?>
<script src="<?= $templateFolder ?>/script.js"></script>
<script>
    BX.ready(function () {
        window.vApps = window.vApps || {};
        window.vApps['<?= $arResult['VUE']['ROOT_ID'] ?>'] = new gsvSaleProfile(
            <?= json_encode($arResult) ?>,
            <?= json_encode($arResult['VUE']) ?>
        );
    })
</script>
<? $this->EndViewTarget(); ?>
<? $this->SetViewTarget('head-styles'); ?>
<link rel="stylesheet" href="<?= $templateFolder ?>/style.css">
<? $this->EndViewTarget(); ?>
<? $this->SetViewTarget('modal-windows'); ?>
<!-- новый адрес адреса -->
<div class="d-flex flex-column border-left side-modal address-add-modal" id="address-add-modal"></div>
<!-- редактирование адреса -->
<div class="d-flex flex-column border-left side-modal address-edit-modal" id="address-edit-modal"></div>

<div class="dn p-4 mw-540 modal-box confirm-modal" id="address-confirm-delete">
    <div class="fs-12 ttu mb-4 tac title">Удалить адрес доставки?</div>
    <div class="d-flex justify-content-around gap-3">
        <button class="btn btn-sm btn-100 btn-primary" data-approve-fancybox>Да</button>
        <button class="btn btn-sm btn-100 btn-secondary" data-fancybox-close>Нет</button>
    </div>
</div>
<? $this->EndViewTarget(); ?>