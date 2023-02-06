<?php
/*
Plugin Name: Ivy Payment Method
Author: Esparks
Description: Ivy integration on woocommerce.
Version: 1.0
*/
add_action('plugins_loaded', 'ivypay_init', 0);
function ivypay_init()
{
  //if condition use to do nothin while WooCommerce is not installed
  if (!class_exists('WC_Payment_Gateway'))
    return;
  include_once('ivy_payment_gateway.php');
  include_once(plugin_dir_path(__FILE__) . '/frontend/buttons.php');
  include_once(plugin_dir_path(__FILE__) . '/update_settings/update_merchant.php');

  add_filter('woocommerce_payment_gateways', 'ivy_gateway');
  function ivy_gateway($methods)
  {
    $methods[] = 'ivy_Pay';
    return $methods;
  }

}
// Add custom action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ivypay_action_links');
function ivypay_action_links($links)
{
  $plugin_links = array(
    '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'ivy_pay') . '</a>',
  );
  return array_merge($plugin_links, $links);
}
// Adding Ivy js files
add_action('wp_enqueue_scripts', 'ivy_pay');
function ivy_pay()
{
  wp_register_script('evycdn', 'https://cdn.getivy.de/button.js', null, null, true);
  wp_enqueue_script('evycdn');
  wp_enqueue_script('ivy_custom_js', plugin_dir_url(__FILE__) . '/js/ivy_js.js', array('jquery'), time(), true);
  wp_localize_script(
    'ivy_custom_js',
    'ajax',
    array(
      'url' => admin_url('admin-ajax.php')
    )
  );
}
?>