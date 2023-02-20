<?php
/*
Plugin Name: Ivy Payment Method
Author: Esparks
Description: Ivy integration on woocommerce.
Version: 1.0
 */

global $wpdb;
$table_name = $wpdb->prefix . 'ivy_address_table';

function create_ivy_address_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ivy_address_table';
    $charset_collate = $wpdb->get_charset_collate();
    $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';

    if ($wpdb->get_var("SHOW TABLES LIKE '$custom_cart_session_table_name'") != $custom_cart_session_table_name) {
        $sql = "CREATE TABLE $custom_cart_session_table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        cart_session_id varchar(32) NOT NULL,
        cart_hash_id varchar(50) NOT NULL,
        shipping_title varchar(100),
        shipping_method varchar(100),
        shipping_price float(10,2),
        coupon_code varchar(255),
        user_id bigint(20),
        session_expiry datetime,
        cart_contents longtext,
        address_contents longtext,
        PRIMARY KEY (id)
    ) $charset_collate;";
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

}

function save_cart_session_id_to_custom_table($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
{
    global $wpdb;
    $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
    $session = WC()->session;
    $cart_session_id = $session->get_session_cookie();
    $user_id = get_current_user_id();
    // error_log(print_r(,true)); // get the session cookie ID

    $cart_session_id = (string) $cart_session_id[3];
    // $db_cart_session_id = $wpdb->get_results('SELECT cart_session_id FROM ' . $custom_cart_session_table_name. ' WHERE cart_session_id = '. '$cart_session_id' . '');
    $db_cart_session_result = $wpdb->get_results(
        $wpdb->prepare("SELECT cart_session_id FROM $custom_cart_session_table_name WHERE cart_session_id = %s", $cart_session_id)
    );
    $user_id_result = $wpdb->get_results(
        $wpdb->prepare("SELECT user_id, cart_session_id FROM $custom_cart_session_table_name WHERE user_id = %d", $user_id)
    );

    $db_user_id = '';
    $db_cart_session_id = '';
    foreach ($user_id_result as $result) {
        // Access the user_id value for each row
        $db_user_id = $result->user_id;
        // Do something with $user_id here
    }

    foreach ($db_cart_session_result as $result) {
        // Access the user_id value for each row
        $db_cart_session_id = $result->cart_session_id;
        // Do something with $user_id here
    }
    // error_log($db_cart_session_id);
    // $new = $db_cart_session_id[0];

    // $cart_session_id = $session_id;
    $user_id = get_current_user_id();
    $session_expiry = date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)); // set session expiry to 2 days from now
    $cart_contents = json_encode(WC()->cart->get_cart()); // convert cart contents to JSON
    if ($db_cart_session_id) {
        $wpdb->update(
            $custom_cart_session_table_name,
            array('cart_contents' => $cart_contents),
            array('cart_session_id' => $cart_session_id)
        );
    } elseif ($db_user_id) {
        $wpdb->update(
            $custom_cart_session_table_name,
            array(
                'cart_contents' => $cart_contents,
                'cart_session_id' => $cart_session_id,
            ),
            array('user_id' => $user_id)
        );
    } else {
        $wpdb->insert($custom_cart_session_table_name, array(
            'cart_session_id' => $cart_session_id,
            'user_id' => $user_id,
            'session_expiry' => $session_expiry,
            'cart_contents' => $cart_contents,
        ));
    }
}

add_action('woocommerce_add_to_cart', 'save_cart_session_id_to_custom_table', 10, 6);

register_activation_hook(__FILE__, 'create_ivy_address_table');
add_action('plugins_loaded', 'ivypay_init', 0);
function ivypay_init()
{
    //if condition use to do nothin while WooCommerce is not installed
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    include_once 'ivy_payment_gateway.php';
    include_once plugin_dir_path(__FILE__) . '/frontend/buttons.php';
    include_once plugin_dir_path(__FILE__) . '/update_settings/update_merchant.php';
    include_once plugin_dir_path(__FILE__) . '/address_model.php';

    add_filter('woocommerce_payment_gateways', 'ivy_gateway');
    function ivy_gateway($methods)
    {
        $methods[] = 'ivy_Pay';
        return $methods;
    }
}
// Add custom action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ivypay_action_links');
function ivypay_action_links($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'ivy_pay') . '</a>',
    );
    return array_merge($plugin_links, $links);
}
// Adding Ivy js files
add_action('wp_enqueue_scripts', 'ivy_pay');
function ivy_pay()
{
    wp_register_script('ivycdn', 'https://cdn.getivy.de/button.js', null, null, true);
    wp_enqueue_script('ivycdn');
    wp_enqueue_script('ivy_custom_js', plugin_dir_url(__FILE__) . '/js/ivy_js.js', array('jquery'), time(), true);
    wp_localize_script(
        'ivy_custom_js',
        'ajax',
        array(
            'url' => admin_url('admin-ajax.php'),
        )
    );
}
