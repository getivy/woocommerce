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
        ivy_order_id varchar(50),
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
  include_once(plugin_dir_path(__FILE__) . '/checkout/express_checkout.php');
  include_once(plugin_dir_path(__FILE__) . '/checkout/normal_checkout.php');
  include_once(plugin_dir_path(__FILE__) . '/success/success_callback.php');
  include_once(plugin_dir_path(__FILE__) . '/complete/complete_callback.php');
  include_once(plugin_dir_path(__FILE__) . '/webhook/webhook_callback.php');

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
        if ( function_exists( 'WC' ) && WC() && WC()->session ) {
          WC()->session->set_customer_session_cookie( true );
      }

    }
}

add_action( 'woocommerce_order_refunded', 'my_custom_refund_action', 10, 2 );
function my_custom_refund_action( $order_id, $refund_id ) {
    $order = wc_get_order( $order_id );
    $payment_method = $order->get_payment_method();
    $order_amount = $order->get_total();
    $refunds = $order->get_refunds();
    if ($refunds) {
      foreach ($refunds as $refund) {
          $refund_amount = $refund->get_amount();
      }
    }
    if ( $payment_method === 'ivy_payment' ) {
      global $wpdb;
      $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
      $cart_results = $wpdb->get_results($wpdb->prepare("SELECT ivy_order_id FROM $custom_cart_session_table_name WHERE order_id = %s", $order_id));
      foreach ($cart_results as $cart_result) {
        $ivyorderId = $cart_result->ivy_order_id;
      }
      $data = [
        'orderId' => $ivyorderId,
        'amount' => $refund_amount,
    ];

    $url = "https://api.sand.getivy.de/api/service/merchant/payment/refund";
    $post = json_encode($data); # all data that going to send
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $ivysandboxkey = $installed_payment_methods["ivy_payment"]->ivyapikey;
    $option = $installed_payment_methods["ivy_payment"]->sandbox;
    $ivylivekey = $installed_payment_methods["ivy_payment"]->ivyapikeylive;
    $ivykey = $ivysandboxkey;
    if ($option == "No") {
      $ivykey = $ivylivekey;
      $url = "https://api.getivy.de/api/service/merchant/payment/refund";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT    5.0');
    $headers = [
      'content-type: application/json',
      'X-Ivy-Api-Key:'.$ivykey.''
      ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $exe = curl_exec($ch);
    error_log($exe);
    if (curl_error($ch)) {
        $output .= "\n" . curl_error($ch);
    }
    curl_close($ch);
}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'ivy/v1', '/success_callback/', array(
    'methods' => 'GET',
    'callback' => 'success_callback',
    'permission_callback' => '__return_true'
  ) );
  register_rest_route( 'ivy/v1', '/complete_callback/', array(
    'methods' => 'POST',
    'callback' => 'complete_callback',
    'permission_callback' => '__return_true'
  ) );
  register_rest_route( 'ivy/v1', '/quote_callback/', array(
    'methods' => 'POST',
    'callback' => 'quote_callback',
    'permission_callback' => '__return_true'
  ) );
  register_rest_route( 'ivy/v1', '/failed_callback/', array(
    'methods' => 'POST',
    'callback' => 'failed_callback',
    'permission_callback' => '__return_true'
  ) );
  register_rest_route( 'ivy/v1', '/webhook_callback/', array(
    'methods' => 'POST',
    'callback' => 'webhook_callback',
    'permission_callback' => '__return_true'
  ) );
} );

?>