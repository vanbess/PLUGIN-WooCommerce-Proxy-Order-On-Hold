<?php

// check and update order proxy status every 5 minutes

// schedule action
add_action('init', function () {
    if (false === as_has_scheduled_action('proxy_order_check') && function_exists('as_has_scheduled_action')) :
        as_schedule_recurring_action(strtotime('now'), 300, 'proxy_order_check', [], 'order_proxy_check');
    endif;
});

// function to run
add_action('proxy_order_check', function () {

    pc_logger('pc_log', '================================================================================================', strtotime('now'));
    pc_logger('pc_log', 'Proxy check START', strtotime('now'));
    pc_logger('pc_log', 'Fetching orders', strtotime('now'));

    // retrieve orders
    $order_query = wc_get_orders([
        'limit'   => -1,
        'orderby' => 'ID',
        'order'   => 'DESC',
        'return'  => 'ids'
    ]);

    // file_put_contents(PROXH_PATH . 'orders.txt', print_r($order_query, true));

    pc_logger('pc_log', 'Fetching current order statuses', strtotime('now'));

    // retrieve current order statuses
    $curr_statuses = wc_get_order_statuses();

    pc_logger('pc_log', 'Starting order ID loop', strtotime('now'));

    // start loop
    if (is_array($order_query) && !empty($order_query)) :

        foreach ($order_query as $order_id) :

            pc_logger('pc_log', 'Checking for presence of order IP address', strtotime('now'));

            $ip_address = get_post_meta($order_id, '_proxy_check_user_ip', true) ? get_post_meta($order_id, '_proxy_check_user_ip', true) : get_post_meta($order_id, '_customer_ip_address', true);

            // if order does not have required meta key, continue
            if (!$ip_address) :

                pc_logger('pc_log', 'IP address not found for order ID' . $order_id . ', moving on to next order', strtotime('now'));

                continue;
            endif;

            pc_logger('pc_log', 'IP address found for order ID ' . $order_id . ' is ' . $ip_address . ', executing check', strtotime('now'));

            // if order has already been checked, continue
            if (get_post_meta($order_id, '_proxy_check_completed', true)) :
                pc_logger('pc_log', 'Proxy check already done for order ID' . $order_id . ', moving on to next order', strtotime('now'));
                continue;
            endif;

            pc_logger('pc_log', 'Retrieving order object for order ID' . $order_id, strtotime('now'));

            // retrieve order object
            $ord_obj = wc_get_order($order_id);

            pc_logger('pc_log', 'Retrieving IP address for order ID' . $order_id, strtotime('now'));

            pc_logger('pc_log', 'Retrieving What\'s My IP API keys ', strtotime('now'));

            // retrieve What's My IP API key
            $wmip_api_keys = get_option('proxy-order-on-hold-api-key') ? get_option('proxy-order-on-hold-api-key') : false;

            // if $wmip_api_key not set, bail
            if (false === $wmip_api_keys) :
                pc_logger('pc_log', 'What\'s My IP API keys not retrieved, stopping function execution', strtotime('now'));
                return;
            else :
                pc_logger('pc_log', 'What\'s My IP API keys retrieved, exploding', strtotime('now'));
                $all_keys = explode(' ', trim($wmip_api_keys));
            endif;

            // randomly pick key to use to avoid running into request limitations
            $key_count   = count($all_keys);
            $max_no      = $key_count - 1;
            $key_ref     = rand(0, $max_no);
            $request_key = $all_keys[$key_ref];

            pc_logger('pc_log', 'Choosing random API key - ' . $request_key . ' chosen', strtotime('now'));

            // send request to What's My IP to check for proxy
            $request_url = "https://api.whatismyip.com/proxy.php?key=$request_key&input=$ip_address&output=json";
            $request     = wp_remote_post($request_url);

            pc_logger('pc_log', 'Full request URL is ' . $request_url, strtotime('now'));

            pc_logger('pc_log', 'Setting up request and executing', strtotime('now'));

            // check for request errors and log
            if (is_wp_error($request)) :
                pc_logger('pc_log', 'Request error returned (' . $request->get_error_message() . '), bailing', strtotime('now'));
                return;
            endif;

            pc_logger('pc_log', 'Parsing response body', strtotime('now'));

            // retrieve order proxy response and decode
            $response = json_decode($request['body'], true);

            // if response code 6 (unknown error returned from What's My IP), put order on hold
            if ($response === 6) :

                pc_logger('pc_log', 'Response 6 returned (unknown error), adding order note', strtotime('now'));

                $note = sprintf(__('Error code %d (Unknown error) returned during proxy/VPN check from What\'s My IP.<br> Order has been put on hold as a precaution.', 'woocommerce'), $response);

                // add note and/or update status
                if ($ord_obj->get_status() == 'on-hold' || $ord_obj->get_status() == 'follow-up') :
                    $ord_obj->add_order_note($note, 0, false);
                elseif (in_array(['Follow-Up', 'Follow Up'], $curr_statuses)) :
                    $ord_obj->set_status('follow-up', $note, true);
                else :
                    $ord_obj->set_status('on-hold', $note, true);
                endif;

                $ord_obj->save();

                // mark order as already checked
                update_post_meta($order_id, '_proxy_check_completed', 'yes');

            endif;

            // if proxy check status is ok
            if (is_array($response) && key_exists('proxy-check', $response) && $response['proxy-check'][0]['status'] === 'ok') :

                pc_logger('pc_log', 'Proxy check request has status of 200, retrieving request result', strtotime('now'));

                // retrieve test results
                $test_results = $response['proxy-check'][1];

                // if proxy test returns a result of no
                if (isset($test_results['is_proxy']) && $test_results['is_proxy'] === 'no') :

                    pc_logger('pc_log', 'No proxy found for IP address ' . $ip_address . ', adding order note', strtotime('now'));


                    // extract order IP address
                    $order_ip_address = $test_results['ip'];

                    // generate note
                    $note = sprintf(__('No proxy/VPN detected on this order.<br> Order IP address: %s', 'woocommerce'), $order_ip_address);

                    // add note to order
                    $ord_obj->add_order_note($note);

                    $ord_obj->save();

                    pc_logger('pc_log', 'Marking order ' . $order_id . ' as checked', strtotime('now'));

                    // mark order as already checked
                    update_post_meta($order_id, '_proxy_check_completed', 'yes');

                endif;

                // if proxy returns a result of yes
                if (isset($test_results['is_proxy']) && $test_results['is_proxy'] === 'yes') :

                    pc_logger('pc_log', 'Proxy found for IP address ' . $ip_address . ', adding order note', strtotime('now'));

                    // extract order IP address
                    $order_ip_address = $test_results['ip'];

                    // extract proxy type
                    $proxy_type = $test_results['proxy_type'];

                    // generate note
                    $note = sprintf(__('Proxy/VPN detected on this order.<br> Order IP address: %s <br> Proxy type: %s <br>', 'woocommerce'), $order_ip_address, $proxy_type);

                    // add note and/or update status
                    if ($ord_obj->get_status() == 'on-hold' || $ord_obj->get_status() == 'follow-up') :
                        $ord_obj->add_order_note($note);
                    elseif (in_array(['Follow-Up', 'Follow Up'], $curr_statuses)) :
                        $ord_obj->set_status('follow-up', $note, true);
                    else :
                        $ord_obj->set_status('on-hold', $note, true);
                    endif;

                    $ord_obj->save();

                    pc_logger('pc_log', 'Marking order ' . $order_id . ' as checked', strtotime('now'));

                    // mark order as already checked
                    update_post_meta($order_id, '_proxy_check_completed', 'yes');

                endif;

            elseif (!is_array($response)) :

                // response not array
                pc_logger('pc_log', 'Response returned is not array. Response: ' . $response, strtotime('now'));

            endif;

            sleep(5);

        endforeach;

    endif;

    pc_logger('pc_log', 'Proxy check END', strtotime('now'));
    pc_logger('pc_log', '================================================================================================', strtotime('now'));
});
