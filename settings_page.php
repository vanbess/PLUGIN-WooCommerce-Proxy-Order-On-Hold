<?php

/**
 * Renders and saves settings
 */

/**
 * WooCommerce settings tab
 */
add_filter('woocommerce_settings_tabs_array', function ($settings_tabs) {
    $settings_tabs['proxy-order-on-hold'] = __('Proxy Order Settings', 'woocommerce');
    return $settings_tabs;
}, 999);

/**
 * API input field
 */
add_action('woocommerce_settings_proxy-order-on-hold', function () { ?>

    <h2><?php _e("What's My IP API key(s)", 'woocommerce'); ?></h2>

    <p><i><b><?php _e('<p>These API keys are required in order to check order for proxy/VPN IP addresses. Enter each API key on a new line.</p>', 'woocommerce'); ?></b></i></p>
<?php
    $settings = [
        [
            'title'     => '',
            'type'      => 'textarea',
            'desc'      => '',
            'desc_tip'  => false,
            'id'        => 'proxy-order-on-hold-api-key',
            'css'       => 'min-width:350px; min-height: 150px;'
        ],
    ];

    WC_Admin_Settings::output_fields($settings);
});

/**
 * Save API key
 */
add_action('woocommerce_settings_save_proxy-order-on-hold', function () {

    global $current_section;

    $tab_id = 'proxy-order-on-hold-api-key';

    $settings = [
        [
            'title'     => __("What's My IP API key", 'woocommerce'),
            'type'      => 'text',
            'desc'      => __('This API is required in order to check order for proxy/VPN IP addresses', 'woocommerce'),
            'desc_tip'  => true,
            'id'        => 'proxy-order-on-hold-api-key',
            'css'       => 'min-width:350px;'
        ],
    ];

    WC_Admin_Settings::save_fields($settings);

    if ($current_section) :
        do_action('woocommerce_update_options_' . $tab_id . '_' . $current_section);
    endif;
});
