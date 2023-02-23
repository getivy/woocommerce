<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$product = WC()->cart->get_cart();
$cart = WC()->cart;
$session = WC()->session;
$unique_id = wc_rand_hash();
global $wpdb;
$custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
$cart_session_id = $session->get_session_cookie();
$user_id = get_current_user_id();

$cart_session_id = (string)$cart_session_id[3];
$db_cart_session_result = $wpdb->get_results(
    $wpdb->prepare("SELECT cart_session_id FROM $custom_cart_session_table_name WHERE cart_session_id = %s", $cart_session_id)
);
$user_id_result = $wpdb->get_results(
    $wpdb->prepare("SELECT user_id, cart_session_id FROM $custom_cart_session_table_name WHERE user_id = %d", $user_id)
);

$db_user_id = '';
$db_cart_session_id = '';  
foreach ($user_id_result as $result) {
    $db_user_id = $result->user_id;
}

foreach ($db_cart_session_result as $result) {
    $db_cart_session_id = $result->cart_session_id;
}

if($db_cart_session_id){
    $wpdb->update(
        $custom_cart_session_table_name,
        array(
            'cart_hash_id' => $unique_id,
            'is_express' => true
        ),
        array('cart_session_id' => $cart_session_id)
    );
}
elseif($db_user_id)
{
    
    $wpdb->update(
        $custom_cart_session_table_name,
        array(
            'cart_hash_id' => $unique_id,
            'cart_session_id' => $cart_session_id,
            'is_express' => true
        ),
        array('user_id' => $user_id)
    );
}

$subtotal = WC()->cart->get_subtotal();
$carttotal = WC()->cart->cart_contents_total;
$tax = WC()->cart->tax_total;
$cart_id = WC()->cart->get_cart_hash();
$currency = get_option('woocommerce_currency');
$ivyLineItems = array();
global $woocommerce, $post;
foreach ($product as $item => $values) {
    $items['name'] = $values['data']->name;
    $items['referenceId'] = $values['product_id'];
    $items['singleNet'] = $values['data']->price;
    $items['singleVat'] = 0;
    $items['amount'] = $values['data']->price;
    $items['quantity'] = $values['quantity'];
    $items['image'] = get_the_post_thumbnail_url($values['product_id'], 'thumbnail');
    $ivyLineItems[] = $items;

}

$data = array(
    'express' => true,
    'referenceId' => $unique_id,
    'category' => "5712",
    'price' => array(
        'totalNet' => $subtotal,
        'vat' => $tax?$tax:0,
        'shipping' => 0,
        'total' => $carttotal,
        'currency' => $currency
    ),
    'lineItems' => $ivyLineItems,
    'required' => array('phone' => true),
);
$url = "https://api.stage.getivy.de/api/service/checkout/session/create";
$post = json_encode($data); # all data that going to send
$installed_payment_methods = WC()->payment_gateways()->payment_gateways();
$ivysandboxkey = $installed_payment_methods["ivy_payment"]->ivyapikey;
$option = $installed_payment_methods["ivy_payment"]->sandbox;
$ivylivekey = $installed_payment_methods["ivy_payment"]->ivyapikeylive;
$ivykey = $ivysandboxkey;
if ($option == "No") {
    $ivykey = $ivylivekey;
    $url = "https://api.getivy.de/api/service/checkout/session/create";
}
// $chosen_shipping_method = WC()->session->get( 'chosen_shipping_methods' );
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT    5.0');
$headers = [
    'content-type: application/json',
    'X-Ivy-Api-Key:' . $ivykey . ''
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$exe = curl_exec($ch);
$getInfo = curl_getinfo($ch);
if ($getInfo['http_code'] === 200) {
    echo $exe;
}
if (curl_error($ch)) {
    $output .= "\n" . curl_error($ch);
}
curl_close($ch);
?>