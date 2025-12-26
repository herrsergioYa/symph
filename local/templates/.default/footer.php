        </div>

        <!-- Dependency Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/mustache@4.2.0/mustache.min.js"></script>
        <!--script src="/local/mustache/mustache.min.js"></script>
        <script src="/local/mustache/mustache.jq.js"></script-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.8/handlebars.min.js" integrity="sha512-E1dSFxg+wsfJ4HKjutk/WaCzK7S2wv1POn1RRPGh8ZK+ag9l244Vqxji3r6wgz9YBf6+vhQEYJZpSjqWFPg9gg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!--script src="/local/handlebars/handlebars@4.7.8/handlebars.min.js"></script>
        <script src="/local/handlebars/handlebars.jq.js"></script-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes-umd.min.js" integrity="sha512-TyfcDRCZyEYxxkHapV5DrDNB6p+pPdCY29LKNVWG4Kp8PU1/cyOioG9tHVh9syWV4n57Aza+xmz+oYTYRNeN2A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Select2 -->
        <!--link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script-->
        <!--script src="<?= SITE_TEMPLATE_PATH ?>/js/select2_en.js"></script>
        <script src="<?= SITE_TEMPLATE_PATH ?>/js/select2_ru.js"></script-->
        <script src="<?= SITE_TEMPLATE_PATH ?>/js/v-select2.js"></script>

        <!-- jQueryUI Autocomplete -->
        <!--script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/themes/base/jquery-ui.min.css" integrity="sha512-TFee0335YRJoyiqz8hA8KV3P0tXa5CpRBSoM0Wnkn7JoJx1kaq1yXL/rb8YFpWXkMOjRcv5txv+C6UluttluCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" /-->
        <!--link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/themes/base/theme.min.css" integrity="sha512-lfR3NT1DltR5o7HyoeYWngQbo6Ec4ITaZuIw6oAxIiCNYu22U5kpwHy9wAaN0vvBj3U6Uy2NNtAfiaKcDxfhTg==" crossorigin="anonymous" referrerpolicy="no-referrer" /-->
        <!--script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js" integrity="sha512-MSOo1aY+3pXCOCdGAYoBZ6YGI0aragoQsg1mKKBHXCYPIWxamwOE7Drh+N5CPgGI5SA9IEKJiPjdfqWFWmZtRA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script-->
        <script src="<?= SITE_TEMPLATE_PATH ?>/js/v-popupJq.js"></script>
        <script src="<?= SITE_TEMPLATE_PATH ?>/js/v3PopupJq.js"></script>

        <!-- IMask -->
        <!--script src="https://cdnjs.cloudflare.com/ajax/libs/imask/7.6.1/imask.min.js" integrity="sha512-+3RJc0aLDkj0plGNnrqlTwCCyMmDCV1fSYqXw4m+OczX09Pas5A/U+V3pFwrSyoC1svzDy40Q9RU/85yb/7D2A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script-->
        <script src="<?= SITE_TEMPLATE_PATH ?>/js/v-imask.js"></script>

		<!-- Site Scripts -->
        <script src="/local/js/gsv-helpers.js"></script>
        <script src="<?=SITE_TEMPLATE_PATH?>/script.js"></script>

        <?if(!defined('BITRIX_ACTIVE') || BITRIX_ACTIVE):?>
            <? $APPLICATION->ShowBodyScripts() ?>
        <?else:?>
            <?$APPLICATION->ShowViewContent("footer-scripts");?>
        <?endif;?>
	</body>
</html>