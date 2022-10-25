<?php

/**
 * Plugin Name:       Proxy Order On-Hold
 * Description:       Checks whether WooCommerce order has been placed via proxy/VPN and puts it on hold if true
 * Version:           1.0.1
 * Requires at least: 5.4
 * Requires PHP:      7.2
 * Author:            WC Bessinger
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       prox-ord-hold
 */

defined('ABSPATH') || exit();

add_action('plugins_loaded', function () {

    // bail if WooCommerce doesn't exist
    if (!class_exists('WooCommerce')) :
        exit();
    endif;

    // Constants
    define('PROXH_PATH', plugin_dir_path(__FILE__));
    define('PROXH_URL', plugin_dir_url(__FILE__));

    // Settings
    include PROXH_PATH . 'settings_page.php';

    // WooCommerce thank you
    include PROXH_PATH . 'wc_thankyou.php';
});
