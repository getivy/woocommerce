<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
global $woocommerce;
$user_id = get_current_user_id();
$baseUrl = get_site_url();
$checkouturl = wc_get_checkout_url();
$cart = WC()->cart;
$order = new WC_Order();

$cartHashId = $_GET['reference'];
global $wpdb;
$custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
$address_result = $wpdb->get_results($wpdb->prepare("SELECT address_contents FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));
$shipping_title = $wpdb->get_results($wpdb->prepare("SELECT shipping_title FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));
$shipping_method = $wpdb->get_results($wpdb->prepare("SELECT shipping_method FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));
$shipping_price = $wpdb->get_results($wpdb->prepare("SELECT shipping_price FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));
$address_content = array();

$order->set_address($address_content, 'billing');
$order->set_address($address_content, 'shipping');

$order_key = $order->order_key;
$cart_items = $cart->get_cart();
foreach ($cart_items as $item_key => $item_values) {
    $product_id = $item_values['product_id'];
    $quantity = $item_values['quantity'];
    $variation_id = $item_values['variation_id'];
    $variation = $item_values['variation'];

    $product = wc_get_product($product_id);

    // Add the product to the order.
    $order->add_product($product, $quantity, array(
        'variation_id' => $variation_id,
        'variation' => $variation,
    ));
}

$shipping = new WC_Order_Item_Shipping();
$shipping->set_method_title('Local Pickup');
$shipping->set_method_id('local_pickup'); // set an existing Shipping method ID
$shipping->set_total(0); // optional
$order->add_item($shipping);
$order->set_customer_id($user_id);
$order->set_payment_method('ivy_payment');
$order->set_payment_method_title('Ivy Payment');
$order->calculate_totals();
$orderId = $order->save();
$woocommerce->cart->empty_cart();
$order->update_status('processing');
header('Location:' . $checkouturl . '?order-received=' . $orderId . '&key=' . $order_key);
