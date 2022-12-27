<?php

/**
 * Check for proxy/VPN on WC thank you page and updates order accordingly
 */

add_action('woocommerce_thankyou', function () {

    // bail if order key not set for some reason so that we avoid any unnecessary errors
    if (!isset($_GET['key'])) :
        return;
    endif;

    // retrieve user IP
    $user_ip = ($_SERVER['HTTP_CF_CONNECTING_IP'] ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

    // retrieve order id
    $order_id = wc_get_order_id_by_order_key($_GET['key']);

    // set user ip to order meta
    update_post_meta($order_id, '_proxy_check_user_ip', $user_ip);
    
});