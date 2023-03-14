<?php

function success_callback() {
   $checkouturl = wc_get_checkout_url();
   $cartHashId = $_GET['reference'];
   $ivyorderId = $_GET['order-id'];

   global $wpdb;
   $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
   $cart_results = $wpdb->get_results($wpdb->prepare("SELECT order_id, cart_total, is_express FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));

   foreach ($cart_results as $cart_result) {
   $orderId = $cart_result->order_id;
   }

   $wpdb->update(
   $custom_cart_session_table_name,
   array('ivy_order_id' => $ivyorderId),
   array('cart_hash_id' => $cartHashId)
   );

   $order = wc_get_order($orderId);
   $order_key = $order->order_key;
   header('Location:' . $checkouturl . '?order-received=' . $orderId . '&key=' . $order_key);
}

?>