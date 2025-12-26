<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$mess = \data::get('GSV_CATALOG_ELEMENT');
if(!is_array($mess)) {
    $mess = [];
}
?>
<?if($arResult['SLIDES']):?>
    <div class="swiper product-photo-slider-gsv" data-catalog-element-holder="slider">
        <div class="swiper-wrapper">
            <?foreach ($arResult['SLIDES'] as $arSlide):?>
            <div class="swiper-slide">
                <?if($arSlide['IS_VIDEO']):?>
                <video class="object-fit" autoplay muted loop playsinline>
                    <source src="<?= $arSlide['VIDEO']['SRC'] ?>" type="<?= $arSlide['VIDEO']['CONTENT_TYPE'] ?>">
                </video>
                <?else:?>
                <div class="swiper-zoom-container">
                    <a href="<?= $arSlide['PHOTO']['SRC'] ?>" data-fancybox="product-photo"><img src="<?= $arSlide['PREVIEW']['SRC'] ?>" loading="lazy" alt=""></a>
                </div>
                <div class="swiper-lazy-preloader"></div>
                <?endif;?>
            </div>
            <?endforeach;?>
        </div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>
<?else:?>
    <div class="d-none" data-catalog-element-holder="slider"></div>
<?endif;?>
    <div class="container-xxl" data-catalog-element-holder="description">
        <div class="row gx-0 gy-4 pt-4 pt-md-5 pb-5 justify-content-between product-info">
            <div class="col-lg-7 col-xl-auto d-flex flex-column flex-sm-row justify-content-between justify-content-lg-end gap-4 order-lg-1">
                <div class="tac product-sizes-wrap">
                    <div class="fs-13 fw-500 ttu notice"><?= $mess['SELECT_CUP_SIZE'] ?></div>
                    <?foreach ($arResult['SKU_PROPS'] as $arSkuProp):?>
                    <?if($arSkuProp['CODE'] == 'SIZE' || $arSkuProp['CODE'] == 'SIZE_SKIRT'):?>
                    <div class="d-none d-flex flex-wrap justify-content-center gap-2 product-sizes" data-sku-prop="<?= $arSkuProp['ID'] ?>">
                        <?foreach ($arSkuProp['VALUES'] as $id => $arValue):?>
                        <?if(empty($arValue['OFFER_CNT'])) continue;?>
                        <button class="btn size<?if(empty($arValue['AVAILABLE_OFFER_CNT'])):?> disabled<?endif;?>" data-sku-prop-val="<?= $id ?>"><?= $arValue['NAME'] ?></button>
                        <?endforeach;?>
                    </div>
                    <?endif;?>
                    <?endforeach;?>
                    <?foreach ($arResult['SKU_PROPS'] as $arSkuProp):?>
                    <?if($arSkuProp['CODE'] == 'SIZE_CUP' || $arSkuProp['CODE'] == 'SIZE_TOP'):?>
                    <div class="d-inline-flex mt-3 product-sizes-switcher" data-sku-prop="<?= $arSkuProp['ID'] ?>">
                        <?foreach ($arSkuProp['VALUES'] as $id => $arValue):?>
                        <?if(empty($arValue['OFFER_CNT'])) continue;?>
                        <button class="btn<?if(empty($arValue['AVAILABLE_OFFER_CNT'])):?> disabled<?endif;?>" type="button" data-sku-prop-val="<?= $id ?>"><?= $arValue['NAME'] ?></button>
                        <?endforeach;?>
                    </div>
                    <?endif;?>
                    <?endforeach;?>
                    <div class="mt-3 mt-lg-2">
                        <a class="fs-10 fw-400 ttu u-line" href="#"><?= $mess['SIZE_GUIDE'] ?></a>
                    </div>
                </div>
                <div class="d-flex flex-column gap-3">
                    <button class="btn btn-primary btn-m100 ttu btn-addcart" type="button" data-btn-add-to-cart="<?= $arResult['SELECTED_ID_OFFER']?>"
                            data-add-to-cart-text="<?= $mess['ADD_TO_CART'] ?>" data-go-to-cart-text="<?= $mess['VIEW_IN_CART'] ?>"
                            data-href="<?= $arResult['ADD_URL_TEMPLATE']?>" data-__fancybox data-src="#error-size"><?= $mess['ADD_TO_CART'] ?></button>
                    <!-- смена класса в иконке .fal -> .fas -->
                    <button class="btn btn-wishlist" type="button" data-element-id="<?=$arResult['ID']?>"
                            data-add-to-cart-text="<?= $mess['ADD_TO_WISHLIST'] ?>" data-go-to-cart-text="<?= $mess['IN_WISHLIST'] ?>"
                        ><i class="fal fa-heart"></i><?= $mess['ADD_TO_WISHLIST'] ?></button>
                </div>
            </div>

            <div class="col-lg-5 col-xl-6 d-flex flex-column flex-xl-row justify-content-between gap-3">
                <div class="d-flex justify-content-between gap-3 mw-400 w-100">
                    <div>
                        <div class="fw-400 mb-1 product-title"><?= $arResult['NAME_MODEL'] ?></div>
                        <div class="product-subtitle"><?= $arResult['NAME'] ?></div>
                    </div>
                    <div class="d-flex flex-column tar">
                        <span class="wsn fw-400 mb-1 product-price"><?= $arResult['PRICE']['PRINT_DISCOUNT_VALUE'] ?></span>
                        <?if($arResult['PRICE']['VALUE'] > $arResult['PRICE']['DISCOUNT_VALUE']):?>
                        <span class="wsn c-gray ml-3 old-price"><?= $arResult['PRICE']['PRINT_VALUE'] ?></span>
                        <?endif;?>
                    </div>
                </div>
                <?if($arResult['COLORS']):?>
                <div class="d-flex flex-wrap mt-2 ms-1 product-colors">
                    <?foreach($arResult['COLORS'] as $arColor):?>
                    <?if($arColor['ID'] == $arResult['ID']):?>
                    <span class="color current" style="--product-color: <?= $arColor['COLOR']['COLOR_CODE']?>"
                          title="<?= $arColor['COLOR']['NAME'] ?>"></span>
                    <?else:?>
                        <a class="o-hover color" data-change-color-action="<?= $arColor['ID'] ?>" href="<?= $arColor['DETAIL_PAGE_URL'] ?>"
                           style="--product-color: <?= $arColor['COLOR']['COLOR_CODE']?>" title="<?= $arColor['COLOR']['NAME'] ?>"></a>
                    <?endif;?>
                    <?endforeach;?>
                </div>
                <?endif;?>
            </div>
        </div>

        <div class="row g-0 gap-5 py-5 border-top">
            <div class="col-lg">
                <div class="fw-500 dib ttu bg-pink px-3 py-2 mb-4"><?= $mess['FREE_DELIVERY'] ?></div>
                <?if($arResult['DETAIL_TEXT']):?>
                <div class="fs-14 product-description">
                    <h3 class="fw-500"><?= $mess['DESCRIPTION'] ?></h3>
                    <?if(strtolower($arResult['DETAIL_TEXT_TYPE']) == 'html'):?>
                        <?= $arResult['DETAIL_TEXT'] ?>
                    <? else: ?>
                        <p><?= $arResult['DETAIL_TEXT'] ?></p>
                    <? endif;?>
                    <?/*p>'Hallie' is our romantic date-night corset that looks so beautiful paired with our 'Cassidy' maxi skirt.
                        Made from stretch mesh, it has a strapless neckline and the bodice is fully corseted to cinch and shape your waist.
                        The pretty gathering creates that ultra soft and feminine vibe and we adore the neck scarf that trails for added drama.</p>
                    <p>Compliment the peach hue with neutral heels as the Pre-Fall season approaches.
                        It zips to the side for easy on. The 'Regular Cup' option suits cup size A-C whilst the 'Bigger Cup' option suits cup size D-E.</p>
                    <p>WHERE TO WEAR:<br>
                        Romantic date nights, stylish dinner dates, bottomless brunches, champagne with the girls, special occasions and events.</p>
                    <p>TEAM YOURS WITH:<br>
                        <a href="">'Cassidy' maxi skirt in peach</a>, neutral heels and a top handle bag.</p>
                    <p>UNDERWEAR SOLUTION:<br>
                        We suggest a balconette strapless bra if required.</p>
                    <p>Main - Mesh: 85% polyamide, 15% elastane<br>
                        Lining - Stretch Crepe</p>
                    <p>Stretch Factor: Some Stretch</p>
                    <p>Designed For House Of CB<br>
                        Shop more <a href="">Tops</a> here</p>
                    <p>Length: Approx 36cm<br>
                        Gentle Dry Clean Only</p>
                    <p>Model is 5 ft 8 and wears size XS</p>
                    <p>Item runs true to size chart and is cut to suit our size chart. Please refer to our size chart for the best fit. Do not size up or down.</p>
                    <p>Colour may vary due to lighting on images. The product images (without model) are closest to the true colour of the product.</p*/?>
                </div>
                <?endif;?>
            </div>

            <div class="col-lg">
                <div class="row g-0 gap-4 p-4 fs-14 bg-pink">
                    <div class="col-sm">
                        <div class="fw-500 mb-3"><i class="fal fa-truck fa-lg me-2"></i>Delivery</div>
                        <ul class="mb-3">
                            <li>- Delivery to most countries is free.</li>
                            <li>- Delivery to most countries is 2-3 working days.</li>
                        </ul>
                        <div class="mb-2">Check your shipping cost and times:</div>
                        <select class="w-100 mb-3 p-1 lh-1_5 appearance-auto border">
                            <option value="Select country/region" selected disabled>Select country/region</option>
                            <option value="Afghanistan">Afghanistan</option>
                            <option value="Aland Islands">Aland Islands</option>
                            <option value="Albania">Albania</option>
                            <option value="Algeria">Algeria</option>
                            <option value="American Samoa">American Samoa</option>
                            <option value="Andorra">Andorra</option>
                            <option value="Angola">Angola</option>
                            <option value="Anguilla">Anguilla</option>
                            <option value="Antarctica">Antarctica</option>
                            <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                            <option value="Argentina">Argentina</option>
                            <option value="Armenia">Armenia</option>
                            <option value="Aruba">Aruba</option>
                            <option value="Australia">Australia</option>
                            <option value="Austria">Austria</option>
                            <option value="Azerbaijan">Azerbaijan</option>
                            <option value="Bahamas">Bahamas</option>
                            <option value="Bahrain">Bahrain</option>
                            <option value="Bangladesh">Bangladesh</option>
                            <option value="Barbados">Barbados</option>
                            <option value="Belgium">Belgium</option>
                            <option value="Belize">Belize</option>
                            <option value="Benin">Benin</option>
                            <option value="Bermuda">Bermuda</option>
                            <option value="Bhutan">Bhutan</option>
                            <option value="Bolivia">Bolivia</option>
                            <option value="Bosnia and Herzegowina">Bosnia and Herzegowina</option>
                            <option value="Botswana">Botswana</option>
                            <option value="Bouvet Island">Bouvet Island</option>
                            <option value="Brazil">Brazil</option>
                            <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                            <option value="British Virgin Islands">British Virgin Islands</option>
                            <option value="Brunei Darussalam">Brunei Darussalam</option>
                            <option value="Bulgaria">Bulgaria</option>
                            <option value="Burkina Faso">Burkina Faso</option>
                            <option value="Burundi">Burundi</option>
                            <option value="Cambodia">Cambodia</option>
                            <option value="Cameroon">Cameroon</option>
                            <option value="Canada">Canada</option>
                            <option value="Cape Verde">Cape Verde</option>
                        </select>
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-400 mb-1">Delivery time</div>
                                2-3 Working days
                            </div>
                            <div>
                                <div class="fw-400 mb-1">Cost</div>
                                €13
                            </div>
                        </div>
                    </div>
                    <div class="col-sm">
                        <div class="fw-500 mb-3"><i class="fal fa-box-alt fa-lg me-2"></i>Returns</div>
                        <div class="fw-400 mb-3">For full priced items we offer a full refund or free exchange if the item is unsuitable</div>
                        <div>For more info on our delivery & returns processes & policies please <a class="u-line" href="">Click Here</a></div>
                    </div>
                    <div class="">
                        Список контактов сразу
                    </div>
                </div>
            </div>
        </div>
    </div>
<script data-catalog-element-holder="init-script">
    BX.ready(function () {
        window.apps = window.apps || {};
        window.apps[<?= json_encode($arResult['JS_DATA']['SEL']) ?>] = new gsvCatalogElement(<?= \CUtil::PhpToJSObject($arResult['JS_DATA']) ?>, window.BASKET || {});
    });
</script>