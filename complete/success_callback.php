<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
global $woocommerce;
$baseUrl = get_site_url();
$checkouturl = wc_get_checkout_url();
$orderId = 860;
// $orderId = $_GET['reference'];
$order = new WC_Order($OrderId);
$order_key = $order->order_key;
$woocommerce->cart->empty_cart();
$order->update_status('completed');
header('Location:' . $checkouturl . '&order-received=' . $OrderId . '&key=' . $OrderKey);
?>