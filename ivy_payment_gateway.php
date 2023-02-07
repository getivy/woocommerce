<?php
global $requestUrl;
class Ivy_Pay extends WC_Payment_Gateway
{
  function __construct()
  {
    // global ID
    $this->id = "ivy_payment";
    // Show Title
    $this->method_title = __("ivy_Pay", 'ivy_pay');
    // Show Description
    $this->method_description = __("ivy Payment Gateway Plug-in for WooCommerce", 'ivy_pay');
    // vertical tab title
    $this->title = __("ivy", 'ivy_pay');
    $this->icon = '';
    $this->has_fields = true;

    // setting defines
    $this->init_form_fields();
    // load time variable setting
    $this->init_settings();
    // Turn these settings into variables we can use
    foreach ($this->settings as $setting_key => $value) {
      $this->$setting_key = $value;
    }
    // Save settings
    if (is_admin()) {
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }
  } // Here is the  End __construct()
  // administration fields for specific Gateway
  public function init_form_fields()
  {
    $this->form_fields = array(
      'enabled' => array(
        'title' => __('Enabled', 'ivyPay'),
        'type' => 'select',
        'options' => array(
          'Yes' => __('Yes', 'ivypay'),
          'No' => __('No', 'ivypay')
        )
      ),
      'description' => array(
        'title' => __('Description', 'ivypay'),
        'type' => 'textarea',
        'desc_tip' => __('Payment title of checkout process.', 'ivypay'),
        'default' => __('Successfully pay through Ivy.', 'ivypay'),
        'css' => 'max-width:450px;'
      ),
      // 'storename' => array(
      //   'title' => __('Storename', 'ivyPay'),
      //   'type' => 'text',
      // ),
      'sandbox' => array(
        'title' => __('Activate Sand Box', 'ivyPay'),
        'type' => 'select',
        'options' => array(
          'Yes' => __('Yes', 'ivypay'),
          'No' => __('No', 'ivypay')
        )
      ),
      'ivyapikey' => array(
        'title' => __('Sandbox Ivy API Key', 'ivyPay'),
        'type' => 'text',
      ),
      'ivysigningsecret' => array(
        'title' => __('Sandbox webhook signing secret', 'ivyPay'),
        'type' => 'text',
      ),
      'sortorder' => array(
        'title' => __('Sort Order', 'ivyPay'),
        'type' => 'text',
      ),
      'product_page_theme' => array(
        'title' => __('Light theme on Catalog Product Page', 'ivyPay'),
        'type' => 'select',
        'options' => array(
          'No' => __('No', 'ivypay'),
          'Yes' => __('Yes', 'ivypay')
        )
      ),
      'mini_cart_theme' => array(
        'title' => __('Light theme on Mini Cart', 'ivyPay'),
        'type' => 'select',
        'options' => array(
          'Yes' => __('Yes', 'ivypay'),
          'No' => __('No', 'ivypay')
        ),
        'desc' => 'Select No for dark theme'
      ),
      'cart_page_theme' => array(
        'title' => __('Light theme on Cart Page', 'ivyPay'),
        'type' => 'select',
        'options' => array(
          'Yes' => __('Yes', 'ivypay'),
          'No' => __('No', 'ivypay')
        ),
        'desc' => __('Select No for dark theme', 'ivypay'),
      ),
      'button_show_product' => array(
        'title' => __('Show button on product page', 'ivyPay'),
        'type' => 'select',
        'options' => array(
          'Yes' => __('Yes', 'ivypay'),
          'No' => __('No', 'ivypay')
        )
      ),
    );
  }
  public function process_payment($order_id)
  {
    global $woocommerce;
    $customer_order = new WC_Order($order_id);
    $redirectlink = get_site_url() . '/wp-content/plugins/Ivy_Payment/checkout/normal_checkout.php';
    return array(
      'result' => 'success',
      'redirect' => $redirectlink,
    );
  }
}
?>