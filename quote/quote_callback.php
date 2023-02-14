<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
if (!class_exists('WooCommerce')) {
    include_once('wp-content/plugins/woocommerce/woocommerce.php');
}
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
$orderId = $_GET['reference'];
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
    $address = array(
        'first_name' => $array['shipping']['shippingAddress']['firstName'],
        'last_name' => $array['shipping']['shippingAddress']['lastName'],
        'address_1' => $array['shipping']['shippingAddress']['line1'],
        'address_2' => $array['shipping']['shippingAddress']['line2'],
        'company' => '',
        'city' => $array['shipping']['shippingAddress']['city'],
        'state' => $array['shipping']['shippingAddress']['country'],
        'postcode' => $array['shipping']['shippingAddress']['zipCode'],
        'country' => $array['shipping']['shippingAddress']['country'],
        'phone' => $array['shopperPhone'],
        'email' => $array['shopperEmail']
    );
    $order = wc_get_order($orderId);
    $order->set_address($address, 'shipping');
    $order->set_address($address, 'billing');
    $order->save();
    $country[] = $address['country'];
    $country_name = $address['country'];
    $zone_ids = array_keys(array('') + WC_Shipping_Zones::get_zones());
    // Loop through shipping Zones IDs
    foreach ($zone_ids as $zone_id) {
        // Get the shipping Zone object
        $shipping_zone = new WC_Shipping_Zone($zone_id);
        $shippinglocations = $shipping_zone->get_zone_locations();
        $country_codes = array();
        foreach ($shippinglocations as $shipping_location) {
            if ($shipping_location->code == $country_name) {
                // Get all shipping method values for the shipping zone
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
            }
        }
    }

    $data['shippingMethods'] = $shippingMethods;
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