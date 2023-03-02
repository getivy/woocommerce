<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
require_once(ABSPATH . 'wp-includes/user.php');

$installed_payment_methods = WC()->payment_gateways()->payment_gateways();
$ivysandboxsecret = $installed_payment_methods["ivy_payment"]->ivysigningsecret;
$option = $installed_payment_methods["ivy_payment"]->sandbox;
$ivylivesecret = $installed_payment_methods["ivy_payment"]->ivysigningsecretlive;
$ivysecret = $ivysandboxsecret;
if ($option == "No") {
    $ivysecret = $ivylivesecret;
}
$header = getallheaders();
$header_value = $header['X-Ivy-Signature'];
$cartHashId = $_GET['reference'];
$request = file_get_contents("php://input");
$hash = hash_hmac(
    'sha256',
    $request,
    $ivysecret
);

if ($header_value !== $hash) {
    return false;
}

$request = file_get_contents("php://input");
$data = json_decode($request);
$cartHashId = $_GET['reference'];
$complete_request = $_GET;
$shipping_method = $data->shippingMethod->reference;
$shipping_title = $data->shippingMethod->name;
$shipping_price = $data->shippingMethod->price;
$total_price = $data->price->total;
$order = new WC_Order();
$cartHashId = $_GET['reference'];

global $wpdb;
$custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
$cart_results = $wpdb->get_results($wpdb->prepare("SELECT customer_data, cart_contents, coupon_code,  is_express FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));
$address_content = array();
$customerData = json_decode($cart_result->customer_data);

foreach ($cart_results as $cart_result) {

    $shipping_address = array(
            'first_name' => $data->shippingAddress->firstName ?? '',
            'last_name' => $data->shippingAddress->lastName ?? '',
            'address_1' => $data->shippingAddress->line1,
            'address_2' => $data->shippingAddress->line2 ?? '',
            'company' => '',
            'city' => $data->shippingAddress->city,
            'state' => $data->shippingAddress->region ?? '',
            'postcode' => $data->shippingAddress->zipCode,
            'country' => $data->shippingAddress->country,
            'phone' => $customerData->phone,
            'email' => $customerData->email,
    );

    $billing_address = array(
            'first_name' => $data->billingAddress->firstName ?? '',
            'last_name' => $data->billingAddress->lastName ?? '',
            'address_1' => $data->billingAddress->line1,
            'address_2' => $data->billingAddress->line2 ?? '',
            'company' => '',
            'city' => $data->billingAddress->city,
            'state' => $data->billingAddress->region ?? '',
            'postcode' => $data->billingAddress->zipCode,
            'country' => $data->billingAddress->country,
            'phone' => $customerData->phone,
            'email' => $customerData->email,
    );

    $cart = json_decode($cart_result->cart_contents);
    $coupon_code = $cart_result->coupon_code;
    $is_express = $cart_result->is_express;
}

$customer_data = array(
    'first_name' => $billing_address->first_name,
    'last_name' => $billing_address->last_name,
    'email' => $billing_address->email,
);

$existing_customer = get_user_by('email', $customer_data['email']);

if ($existing_customer) {
    $customer = new WC_Customer($existing_customer->ID);
} else {
    $customer = new WC_Customer();
    $customer->set_first_name($customer_data['first_name']);
    $customer->set_last_name($customer_data['last_name']);
    $customer->set_email($customer_data['email']);
    $customer->save();
}

$order->set_customer_id($customer->get_id());
$order->set_address($billing_address, 'billing');
$order->set_address($shipping_address, 'shipping');
$shipping = new WC_Order_Item_Shipping();
$shipping->set_method_title($shipping_title);
$shipping->set_method_id($shipping_method);
$shipping->set_total($shipping_price);
$order->add_item($shipping);

if($coupon_code){
    $coupon = new WC_Coupon( $coupon_code );
    $discount_total = $coupon->get_amount();
    $item = new WC_Order_Item_Coupon();
    $item->set_props( array( 'code' => $coupon_code, 'discount' => $discount_total ) );
    $item->set_order_id( $order->get_id() );
    $order->add_item( $item );
}
$order_key = $order->order_key;
$cart_items = $cart;
foreach ($cart_items as $item_values) {
    $product_id = $item_values->product_id;
    $quantity = $item_values->quantity;
    $variation_id = $item_values->variation_id;
    $variation = $item_values->variation;
    $product = wc_get_product($product_id);
    $order->add_product(
        $product,
        $quantity,
        array(
            'variation_id' => $variation_id,
            'variation' => $variation,
        )
    );
}

$order->set_payment_method('ivy_payment');
$order->set_payment_method_title('Ivy Payment');
$order->calculate_totals();
$order->set_total( $total_price );
$orderId = $order->save();
$wpdb->update(
    $custom_cart_session_table_name,
    array('order_id' => $orderId),
    array('cart_hash_id' => $cartHashId)
);

$installed_payment_methods = WC()->payment_gateways()->payment_gateways();
$ivysandboxsecret = $installed_payment_methods["ivy_payment"]->ivysigningsecret;
$option = $installed_payment_methods["ivy_payment"]->sandbox;
$ivylivesecret = $installed_payment_methods["ivy_payment"]->ivysigningsecretlive;
$ivysecret = $ivysandboxsecret;

if ($option == "No") {
    $ivysecret = $ivylivesecret;
}
$header = getallheaders();
$header_value = $header['X-Ivy-Signature'];
$request = file_get_contents("php://input");

$hash = hash_hmac(
    'sha256',
    $request,
    $ivysecret
);
if ($header_value === $hash) {
    global $woocommerce;
    session_start();
    $data = [
        'redirectUrl' => get_site_url().'/wp-content/plugins/Ivy_Payment/success/success_callback.php'
    ];
    $hash = hash_hmac(
        'sha256',
        json_encode($data, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE),
        $ivysecret
    );
    header('X-Ivy-Signature: ' . $hash);

    echo json_encode($data);
} else {
    return false;
}
?>