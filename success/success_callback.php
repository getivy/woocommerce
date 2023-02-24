<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
global $woocommerce;
$checkouturl = wc_get_checkout_url();
$cartHashId = $_GET['reference'];

global $wpdb;
$custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
$cart_results = $wpdb->get_results($wpdb->prepare("SELECT order_id, cart_total, is_express FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));

foreach ($cart_results as $cart_result) {
   $orderId = $cart_result->order_id;
   $cart_total = $cart_result->cart_total;
   $is_express = $cart_result->is_express;
}

$order = wc_get_order($orderId);

$order->set_total( $cart_total );
$order->save();

$order_key = $order->order_key;
$woocommerce->cart->empty_cart();
header('Location:' . $checkouturl . '?order-received=' . $orderId . '&key=' . $order_key);
?>