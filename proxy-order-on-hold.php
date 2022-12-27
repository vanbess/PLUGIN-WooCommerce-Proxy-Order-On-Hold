<?php

/**
 * Plugin Name:       Proxy Order On-Hold
 * Description:       Checks whether WooCommerce order has been placed via proxy/VPN and puts it on hold if true
 * Version:           1.0.2
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

    // WooCommerce thank you action
    include PROXH_PATH . 'wc_thankyou.php';

    // Action Scheduler action
    include PROXH_PATH . 'as_action.php';

    // Logger
    function pc_logger($log_fname, $message, $time_stamp) {
    
        // delete log if it gets > 10mb
        if (filesize(PROXH_PATH . $log_fname . '.log') !== false && filesize(PROXH_PATH . $log_fname . '.log') > 10485760) :
            unlink(PROXH_PATH . $log_fname . '.log');
        endif;
    
        // write to log
        file_put_contents(PROXH_PATH . $log_fname . '.log', date('j F Y @ h:i:s', $time_stamp) . ': ' . $message . PHP_EOL, FILE_APPEND);
    }

});



