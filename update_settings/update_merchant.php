<?php
// check if the user have submitted the settings
// wordpress will add the "settings-updated" $_GET parameter to the url

add_action('woocommerce_update_options', 'ivyconfig_updated_option', 10, 3);
function ivyconfig_updated_option() 
{
    $logo_id = get_theme_mod( 'custom_logo' ); 
    $logo_url = wp_get_attachment_image_src( $logo_id , 'full' )[0];
    $data = [
        'successCallbackUrl' => get_site_url() . '/wp-json/ivy/v1/success_callback/',
        'errorCallbackUrl' => get_site_url() . '/wp-json/ivy/v1/failed_callback/',
        'quoteCallbackUrl' => get_site_url() . '/wp-content/plugins/Ivy_Payment/quote/quote_callback.php',
        'webhookUrl' => get_site_url() . '/wp-json/ivy/v1/webhook_callback/',
        'completeCallbackUrl' => get_site_url() . '/wp-json/ivy/v1/complete_callback/',
        'shopLogo' => $logo_url
    ];
    $url = "https://api.sand.getivy.de/api/service/merchant/update";
    $post = json_encode($data, JSON_UNESCAPED_SLASHES);
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $ivysandboxkey = $installed_payment_methods["ivy_payment"]->ivyapikey;
    $option = $installed_payment_methods["ivy_payment"]->sandbox;
    $ivylivekey = $installed_payment_methods["ivy_payment"]->ivyapikeylive;
    $ivykey = $ivysandboxkey;
    if ($option == "No") {
      $ivykey = $ivylivekey;
      $url = "https://api.getivy.de/api/service/merchant/update";
    }
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
        'X-Ivy-Api-Key:'.$ivykey.''
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_exec($ch);
    if (curl_error($ch)) {
        $output .= "\n" . curl_error($ch);
    }
    curl_close($ch);
  
}
?>