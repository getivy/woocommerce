<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
global $woocommerce;
session_start();
$baseUrl = get_site_url();
$checkouturl = wc_get_checkout_url();
$orderId = $_GET['reference'];
$order = new WC_Order($orderId);
$order_key = $order->order_key;
$woocommerce->cart->empty_cart();
$order->update_status('completed');
header('Location:' . $checkouturl . '?order-received=' . $orderId . '&key=' . $order_key);
?>