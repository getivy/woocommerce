<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
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
if ($header_value === $hash) {
    $request2 = json_decode(json_encode($request), true);
    $request2 = json_decode((string) $request);
    $array = json_decode(json_encode($request2), true);
    if (key_exists('shipping', $array)) {
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
        $customerData = array(
           'phone' => $array['shopperPhone'],
           'email' => $array['shopperEmail'],
        )
        $customer_data = json_encode($customerData);
        global $wpdb;
        $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
        $wpdb->update(
            $custom_cart_session_table_name,
            array('customer_data' => $customer_data),
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
            $shipping_methods = $shipping_zone->get_shipping_methods(true, 'values');
            $country_codes = array();
            foreach ($shippinglocations as $shipping_location) {
                $state_name = '';
                if ($shipping_location->type == 'state') {
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
                                'reference' => $shipping_method_id
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
                                'reference' => $shipping_method_id
                            ];
                        }
                    }
                    // Get all shipping method values for the shipping zone

                } else {
                    if ($shipping_location->code == $country_name) {
                        foreach ($shipping_methods as $instance_id => $shipping_method) {
                            $shipping_cost = $shipping_method->cost;
                            $shipping_method_title = $shipping_method->title;
                            $shipping_method_id = $shipping_method->id;

                            $shippingMethods[] = [
                                'price' => $shipping_cost ? $shipping_cost : 0,
                                'name' => $shipping_method_title,
                                'countries' => $country,
                                'reference' => $shipping_method_id
                            ];
                        }

                    }

                }
            }
        }
        $data['shippingMethods'] = $shippingMethods;
    }

    if (key_exists('discount', $array)) {
        $custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
        $cart_contents = $wpdb->get_results($wpdb->prepare("SELECT cart_contents FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));

        $coupon_code = $array['discount']['voucher'];
        $cart = WC()->cart;
        $coupon_amount = 0;
        foreach ($cart_contents as $cart_content) {
            $values = $cart_content->cart_contents;
            $values = json_decode($values);
            foreach ($values as $value) {
                $product_id = $value->product_id;
                $quantity = $value->quantity;
                $cart->add_to_cart($product_id, $quantity);
            }
        }
        if (!$cart->has_discount($coupon_code)) {
            $cart->apply_coupon($coupon_code);
        }

        $coupon_amount = $cart->get_discount_total();
        $discount = ['amount' => $coupon_amount];
        if ($coupon_amount > 0) {
            $data['discount'] = $discount;
            $total = $cart->get_cart_contents_total();

            $data['price'] = [
                'totalNet' => $cart->get_subtotal() ? $cart->get_subtotal() : 0,
                'vat' => $cart->tax_total,
                'total' => $total
            ];

            $wpdb->update(
                $custom_cart_session_table_name,
                array('coupon_code' => $coupon_code,
                    ),
                array('cart_hash_id' => $cartHashId)
            );
        }
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

?>