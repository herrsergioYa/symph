import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

import jQuery from 'jquery';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

jQuery(document).ready(function () {
    jQuery('[data-wishlist]').on('click', function (e) {
        alert(1);
        e.preventDefault();
    })
})
