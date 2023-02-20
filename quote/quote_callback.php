<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
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
// $order = wc_create_order();
$cartHashId = $_GET['reference'];
$request = file_get_contents("php://input");
$hash = hash_hmac(
    'sha256',
    $request,
    $ivysecret
);
if ($header_value === $hash) {
    $request2 = json_decode(json_encode($request), true);
    $request2 = json_decode((string) $request);
    $array = json_decode(json_encode($request2), true);
    // error_log(print_r($array,true));
    $address = array(
        'first_name' => $array['shipping']['shippingAddress']['firstName'],
        'last_name' => $array['shipping']['shippingAddress']['lastName'],
        'address_1' => $array['shipping']['shippingAddress']['line1'],
        'address_2' => $array['shipping']['shippingAddress']['line2'],
        'company' => '',
        'city' => $array['shipping']['shippingAddress']['city'],
        'state' => $array['shipping']['shippingAddress']['region'],
        'postcode' => $array['shipping']['shippingAddress']['zipCode'],
        'country' => $array['shipping']['shippingAddress']['country'],
        'phone' => $array['shopperPhone'],
        'email' => $array['shopperEmail'],
    );
    $address_contents = json_encode($address);
    global $wpdb;
    $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
    $wpdb->update(
        $custom_cart_session_table_name,
        array('address_contents' => $address_contents),
        array('cart_hash_id' => $cartHashId)
    );

    $country[] = $address['country'];
    $country_name = $address['country'];
    $zone_ids = array_keys(array('') + WC_Shipping_Zones::get_zones());
    $shippingMethods = array();

    // Loop through shipping Zones IDs
    foreach ($zone_ids as $zone_id) {
        // Get the shipping Zone object
        $shipping_zone = new WC_Shipping_Zone($zone_id);
        $shippinglocations = $shipping_zone->get_zone_locations();
        $country_codes = array();
        foreach ($shippinglocations as $shipping_location) {
            $state_name = '';

            if ($shipping_location->type == 'state') {
                // $state_code = $shipping_location->code;
                $wp_state = explode(":", $shipping_location->code);
                $state_code = $wp_state[1]; // Replace with the state code you want to look up

                $states = WC()->countries->states[$country_name]; // Get the list of states for the US

                if (isset($states[$state_code])) {
                    $state_name = $states[$state_code]; // Get the state name

                }

                if ($state_name == $address['state']) {
                    $shipping_methods = $shipping_zone->get_shipping_methods(true, 'values');
                    // Loop through each shipping methods set for the current shipping zone
                    foreach ($shipping_methods as $instance_id => $shipping_method) {
                        $shipping_cost = $shipping_method->cost;
                        $shipping_method_title = $shipping_method->title;
                        $shipping_method_id = $shipping_method->id;

                        $shippingMethods[] = [
                            'price' => $shipping_cost,
                            'name' => $shipping_method_title,
                            'countries' => $country,
                            'reference' => $shipping_method_id,
                        ];
                    }
                } elseif (strpos($state_code, $address['state']) !== false) {

                    $shipping_methods = $shipping_zone->get_shipping_methods(true, 'values');
                    // Loop through each shipping methods set for the current shipping zone
                    foreach ($shipping_methods as $instance_id => $shipping_method) {
                        $shipping_cost = $shipping_method->cost;
                        $shipping_method_title = $shipping_method->title;
                        $shipping_method_id = $shipping_method->id;

                        $shippingMethods[] = [
                            'price' => $shipping_cost ? $shipping_cost : 0,
                            'name' => $shipping_method_title,
                            'countries' => $country,
                            'reference' => $shipping_method_id,
                        ];
                    }
                }
            }
        }
    }
    $cart = WC()->cart;
    $cart->apply_coupon($coupon_code);
    $total_price = $cart->total;
    $coupon_amount = 0;
    $coupon = new WC_Coupon($coupon_code);
    $coupon_amount = $coupon->get_amount();

    if ($coupon_amount > 0) {
        $discount = ['amount' => $coupon_amount];
        $data['discount'] = $discount;
        $data['price'] = [
            'totalNet' => $total_price,
            'vat' => 123,
            'total' => $total_price,
        ];
    }
    $data['shippingMethods'] = $shippingMethods;

    if ($coupon_amount > 0) {
        $discount = ['amount' => $coupon_amount];
        $data['discount'] = $discount;
        $data['price'] = [
            'totalNet' => $total_price,
            'vat' => 32,
            'total' => $total_price,
        ];
    }
    $hash = hash_hmac(
        'sha256',
        json_encode($data, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE),
        $ivysecret
    );
    header('X-Ivy-Signature: ' . "$hash");

    echo json_encode($data);
} else {
    return false;
}
