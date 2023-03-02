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
  // $table_name = $wpdb->prefix . 'ivy_address_table';
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
        order_id bigint(20),
        cart_total bigint(20),
        session_expiry datetime,
        cart_contents longtext,
        shipping_address longtext,
        billing_address longtext,
        is_express BOOLEAN DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";
  }

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);

}

function save_cart_session_id_to_custom_table($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
{
  global $wpdb;
  $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
  $session = WC()->session;
  $cart_session_id = $session->get_session_cookie();
  $user_id = get_current_user_id();
  $cart_session_id = (string) $cart_session_id[3];
  $db_cart_session_result = $wpdb->get_results(
    $wpdb->prepare("SELECT cart_session_id FROM $custom_cart_session_table_name WHERE cart_session_id = %s", $cart_session_id)
  );
  $user_id_result = $wpdb->get_results(
    $wpdb->prepare("SELECT user_id, cart_session_id FROM $custom_cart_session_table_name WHERE user_id = %d", $user_id)
  );

  $db_user_id = '';
  $db_cart_session_id = '';
  foreach ($user_id_result as $result) {
    $db_user_id = $result->user_id;
  }

  foreach ($db_cart_session_result as $result) {
    $db_cart_session_id = $result->cart_session_id;
  }
  $user_id = get_current_user_id();
  $session_expiry = date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)); // set session expiry to 2 days from now
  $cart_contents = json_encode(WC()->cart->get_cart());
  if ($db_cart_session_id) {
    $wpdb->update(
      $custom_cart_session_table_name,
      array(
        'cart_contents' => $cart_contents,
        'coupon_code' => ""
      ),
      array('cart_session_id' => $cart_session_id)
    );
  } elseif ($db_user_id) {
    $wpdb->update(
      $custom_cart_session_table_name,
      array(
        'cart_contents' => $cart_contents,
        'cart_session_id' => $cart_session_id,
        'coupon_code' => ""
      ),
      array('user_id' => $user_id)
    );
  } else {
    $wpdb->insert($custom_cart_session_table_name, array(
      'cart_session_id' => $cart_session_id,
      'user_id' => $user_id,
      'session_expiry' => $session_expiry,
      'cart_contents' => $cart_contents,
    )
    );
  }
}


add_action('woocommerce_add_to_cart', 'save_cart_session_id_to_custom_table', 10, 6);
add_action( 'woocommerce_update_cart_action_cart_updated', 'update_cart_in_custom_table', 10, 1 );
function update_cart_in_custom_table( $cart_updated ) {
  global $wpdb;
  $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
  $session = WC()->session;
  $cart_session_id = $session->get_session_cookie();
  $user_id = get_current_user_id();
  $cart_session_id = (string) $cart_session_id[3];
  $db_cart_session_result = $wpdb->get_results(
    $wpdb->prepare("SELECT cart_session_id FROM $custom_cart_session_table_name WHERE cart_session_id = %s", $cart_session_id)
  );
  $user_id_result = $wpdb->get_results(
    $wpdb->prepare("SELECT user_id, cart_session_id FROM $custom_cart_session_table_name WHERE user_id = %d", $user_id)
  );

  $db_user_id = '';
  $db_cart_session_id = '';
  foreach ($user_id_result as $result) {
    $db_user_id = $result->user_id;
  }

  foreach ($db_cart_session_result as $result) {
    $db_cart_session_id = $result->cart_session_id;
  }
  $user_id = get_current_user_id();
  $session_expiry = date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)); // set session expiry to 2 days from now
  $cart_contents = json_encode(WC()->cart->get_cart());
  if ($db_cart_session_id) {
    $wpdb->update(
      $custom_cart_session_table_name,
      array(
        'cart_contents' => $cart_contents,
        'coupon_code' => ""
      ),
      array('cart_session_id' => $cart_session_id)
    );
  } elseif ($db_user_id) {
    $wpdb->update(
      $custom_cart_session_table_name,
      array(
        'cart_contents' => $cart_contents,
        'cart_session_id' => $cart_session_id,
        'coupon_code' => ""
      ),
      array('user_id' => $user_id)
    );
  } else {
    $wpdb->insert($custom_cart_session_table_name, array(
      'cart_session_id' => $cart_session_id,
      'user_id' => $user_id,
      'session_expiry' => $session_expiry,
      'cart_contents' => $cart_contents,
    )
    );
  }
}

register_activation_hook(__FILE__, 'create_ivy_address_table');
add_action('plugins_loaded', 'ivypay_init', 0);
function ivypay_init()
{
  //if condition use to do nothin while WooCommerce is not installed
  if (!class_exists('WC_Payment_Gateway'))
    return;
  include_once('ivy_payment_gateway.php');
  include_once(plugin_dir_path(__FILE__) . '/frontend/buttons.php');
  include_once(plugin_dir_path(__FILE__) . '/update_settings/update_merchant.php');

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
// Adding Ivy js and css files
add_action('wp_enqueue_scripts', 'ivy_pay');
function ivy_pay()
{
  wp_register_script('ivycdn', 'https://cdn.getivy.de/button.js', array(), null, true);
  wp_enqueue_script('ivycdn');
  wp_enqueue_script('ivy_custom_js', plugin_dir_url(__FILE__) . 'js/ivy_js.js', array('jquery'), time(), true);
  wp_localize_script(
    'ivy_custom_js',
    'ajax',
    array(
      'url' => admin_url('admin-ajax.php')
    )
  );

  wp_register_style('custom_css', plugin_dir_url(__FILE__) . 'css/custom_css.css', false, '1.0.0');
  wp_enqueue_style('custom_css');
}

add_action( 'init', 'create_session_for_guest_user' );
function create_session_for_guest_user() {
    if ( is_user_logged_in() ) {
        return;
    }
    if ( ! session_id() ) {
        session_start();
        if ( function_exists( 'WC' ) && WC() && WC()->session ) {
          WC()->session->set_customer_session_cookie( true );
      }

    }
    if ( ! isset( $_COOKIE['woocommerce_session'] ) ) {
        $session_cookie = apply_filters( 'woocommerce_cookie_settings', array(
            'name' => 'woocommerce_session',
            'value' => '',
            'expire' => strtotime( '+2 days' ),
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true
        ) );
        setcookie( $session_cookie['name'], $session_cookie['value'], $session_cookie['expire'], $session_cookie['path'], $session_cookie['domain'], $session_cookie['secure'], $session_cookie['httponly'] );
    }
}
function ivy_deactivate() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'custom_cart_sessions';
  $sql = "DROP TABLE IF EXISTS $table_name;";
  $wpdb->query($sql);
}
// Hook the function to run on plugin deactivation
register_deactivation_hook( __FILE__, 'ivy_deactivate' );

?>