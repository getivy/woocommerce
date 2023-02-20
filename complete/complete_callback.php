<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
$installed_payment_methods = WC()->payment_gateways()->payment_gateways();
$ivysandboxsecret = $installed_payment_methods["ivy_payment"]->ivysigningsecret;
$option = $installed_payment_methods["ivy_payment"]->sandbox;
$ivylivesecret = $installed_payment_methods["ivy_payment"]->ivysigningsecretlive;
$ivysecret = $ivysandboxsecret;

$request = file_get_contents("php://input");
$data = json_decode($request);
$cartHashId = $_GET['reference'];
$shipping_method = $data->shippingMethod->reference;
$shipping_title = $data->shippingMethod->name;
$shipping_price = $data->shippingMethod->price;

global $wpdb;
$custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
// $db_cart_session_id = $wpdb->get_results('SELECT cart_session_id FROM ' . $custom_cart_session_table_name. ' WHERE cart_session_id = '. '$cart_session_id' . '');
$wpdb->update(
    $custom_cart_session_table_name,
    array('shipping_title' => $shipping_title,
        'shipping_method' => $shipping_method,
        'shipping_price' => $shipping_price),
    array('cart_hash_id' => $cartHashId)
);

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
        'redirectUrl' => get_site_url() . '/wp-content/plugins/Ivy_Payment/success/success_callback.php',
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
