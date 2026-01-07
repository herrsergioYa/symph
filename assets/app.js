import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

import jQuery from 'jquery';
import {createApp} from 'vue';
import Cart from './vue/Cart.vue';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

const $ = jQuery;

jQuery(document).ready(function () {
    jQuery('[data-wishlist]').on('click', function (e) {
        alert(1);
        e.preventDefault();
    })
    $('[data-add-to-cart]').on('click', async function (e) {
        e.preventDefault();

        let productId = $(this).data('add-to-cart');

        let data = {
            productId,
            quantity: 1,
        };

        data = JSON.stringify(data);

        let response = await $.ajax({
            url: '/api/cart/add',
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data,
        });

        console.log(response);

    })
debugger;
    let app = createApp(Cart);
    let mount = app.mount('#cart-modal');
    debugger;
})
