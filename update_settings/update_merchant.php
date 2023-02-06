<?php
// check if the user have submitted the settings
// wordpress will add the "settings-updated" $_GET parameter to the url
add_action('woocommerce_update_options', 'ivyconfig_updated_option', 10, 3);
function ivyconfig_updated_option()
{
    $data = [
        'successCallbackUrl' => get_site_url() . '/wp-content/plugins/Ivy_Payment/complete/success_callback.php',
        'errorCallbackUrl' => get_site_url() . '/wp-content/plugins/Ivy_Payment/fail/failed_callback.php',
        'quoteCallbackUrl' => get_site_url() . '/wp-content/plugins/Ivy_Payment/complete/success_callback.php',
        'webhookUrl' => get_site_url() . '/wp-content/plugins/Ivy_Payment/complete/success_callback.php',
        'completeCallbackUrl' => get_site_url() . '/wp-content/plugins/Ivy_Payment/complete/success_callback.php',
        'shopLogo' => 'https://49ff7bbedf.nxcli.net/media/logo/stores/1/schraubdoc_final_logo-2019-v4-lang-375x100.png'
    ];
    $url = "https://api.stage.getivy.de/api/service/merchant/update";
    $post = json_encode($data, JSON_UNESCAPED_SLASHES);
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $ivysandboxkey = $installed_payment_methods["ivy_payment"]->ivyapikey;
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
        'X-Ivy-Api-Key:'.$ivysandboxkey.''
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_exec($ch);
    if (curl_error($ch)) {
        $output .= "\n" . curl_error($ch);
    }
    curl_close($ch);
}
?>