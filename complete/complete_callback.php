<?php
function complete_callback() {
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
    $cart_results = $wpdb->get_results($wpdb->prepare("SELECT shipping_address, billing_address, cart_contents, coupon_code,  is_express FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));
    $address_content = array();
    foreach ($cart_results as $cart_result) {
        $shipping_address = json_decode($cart_result->shipping_address);
        $billing_address = json_decode($cart_result->billing_address);
        error_log(print_r($shipping_address,true));
        $cart = json_decode($cart_result->cart_contents);
        $coupon_code = $cart_result->coupon_code;
        $is_express = $cart_result->is_express;
    }
    $customer_data = array(
        'first_name' => $billing_address->first_name,
        'last_name' => $billing_address->last_name,
        'email' => $billing_address->email,
    );
    if ($is_express) {
        $shipping_address = array(
            'first_name' => $data->shippingAddress->firstName,
            'last_name' => $data->shippingAddress->lastName,
            'address_1' => $data->shippingAddress->line1,
            'address_2' => $data->shippingAddress->line2,
            'company' => '',
            'city' => $data->shippingAddress->city,
            'state' => $data->shippingAddress->region,
            'postcode' => $data->shippingAddress->zipCode,
            'country' => $data->shippingAddress->country,
            'phone' => $billing_address->phone,
            'email' => $customer_data['email'],
        );

        $billing_address = array(
            'first_name' => $data->billingAddress->firstName ? $data->billingAddress->firstName : '',
            'last_name' => $data->billingAddress->lastName ? $data->billingAddress->lastName : '',
            'address_1' => $data->billingAddress->line1 ? $data->billingAddress->line1 : '',
            'address_2' => $data->billingAddress->line2 ? $data->billingAddress->line2 : '',
            'company' => '',
            'city' => $data->billingAddress->city ? $data->billingAddress->city : '',
            'state' => $data->billingAddress->region ? $data->billingAddress->region : '',
            'postcode' => $data->billingAddress->zipCode ? $data->billingAddress->zipCode : '',
            'country' => $data->billingAddress->country ? $data->billingAddress->country : '',
            'phone' => $billing_address->phone,
            'email' => $customer_data['email'],
        );
    }

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
    if ($coupon_code) {
        $order->apply_coupon( $coupon_code );
    }
    $order->calculate_totals();
    $orderId = $order->save();
    $wpdb->update(
        $custom_cart_session_table_name,
        array('order_id' => $orderId),
        array('cart_hash_id' => $cartHashId)
    );

    global $woocommerce;
    session_start();
    $data = [
        'redirectUrl' => get_site_url() . '/wp-json/ivy/v1/success_callback/',
        'displayId' => $order_id,
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

}