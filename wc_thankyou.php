<?php

/**
 * Check for proxy/VPN on WC thank you page and updates order accordingly
 */

use Composer\Installers\PPIInstaller;

add_action('woocommerce_thankyou', function () {

    // bail if order key not set for some reason so that we avoid any unnecessary errors
    if (!isset($_GET['key'])) :
        return;
    endif;


    // retrieve order id
    $order_id = wc_get_order_id_by_order_key($_GET['key']);

    // retrieve order object
    $ord_obj = wc_get_order($order_id);

    // retrieve user IP
    $user_ip = ($_SERVER['HTTP_CF_CONNECTING_IP'] ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

    // retrieve What's My IP API key
    $wmip_api_keys = get_option('proxy-order-on-hold-api-key') ? get_option('proxy-order-on-hold-api-key') : false;

    // if $wmip_api_key not set, bail
    if (false === $wmip_api_keys) :
        return;
    else :
        $all_keys = explode(' ', trim($wmip_api_keys));
    endif;

    // randomly pick key to use to avoid running into request limitations
    $key_count   = count($all_keys);
    $max_no      = $key_count - 1;
    $key_ref     = rand(0, $max_no);
    $request_key = $all_keys[$key_ref];

    // send request to What's My IP to check for proxy
    $request_url = "https://api.whatismyip.com/proxy.php?key=$request_key&input=$user_ip";
    $request = wp_remote_post($request_url);

    // if response code == 200, retrieve response body
    if ($request['response']['code'] == 200 && $request['body'] != '3') :

        // retrieve response and explode
        $response = explode(PHP_EOL, $request['body']);
        $response = array_filter($response);

        // uncomment below for testing purposes
        // $response_arr['is_proxy'] = 'yes';
        // $response_arr['proxy_type'] = 'VPN';
        // $response_arr['ip'] = '23.82.194.166';

        $is_proxy = $response[1];

        if (strpos($is_proxy, 'yes')) :

            // retrieve current order statuses (looking for follow-up status)
            $curr_statuses = wc_get_order_statuses();

            // add order note
            $note = __('<b><u><i>NOTE:</i></u> Order placed via Proxy/VPN.</b><br>');
            $note .= __('<b><u><i>IP Address:</i></u> ' . str_replace('ip:', '', $response[0]) . '</b><br>');
            $note .= __('<b><u><i>Proxy Type:</i></u> ' . str_replace('proxy_type:', '', $response[2]) . '</b><br><br>');

            // add note for orders already on hold, else place order on hold with notice
            if ($ord_obj->get_status() == 'on-hold' || $ord_obj->get_status() == 'follow-up') :
                $ord_obj->add_order_note($note, 0, false);
            elseif (in_array(['Follow-Up', 'Follow Up'], $curr_statuses)) :
                $ord_obj->set_status('follow-up', $note, true);
            else :
                $ord_obj->set_status('on-hold', $note, true);
            endif;

            // save
            $ord_obj->save();

        endif;

    // if request successful but max requests per day reached, bail with note
    elseif ($request['response']['code'] == 200 && $request['body'] == '3') :

        $note = __('<b><u><i>NOTE:</i></u> Could not determine whether order was placed via proxy/VPN or not.</b><br>');
        $note .= __('<b><u><i>Reason:</i></u> Daily request limit to What\'s My IP API exceeded.</b><br>');

        $ord_obj->add_order_note($note, 0, false);

        $ord_obj->save();

    endif;
});
