<?php 
/*
Plugin Name: Woo Ingeni Map-Based Local Delivery Shipping Method
Plugin URI: http://ingeni.net
Description: Woo Ingeni Map-Based Local Delivery Shipping Method
Version: 2020.01
Author: Bruce McKinnon
Author URI: http://ingeni.net
*/


/**
 * Check if WooCommerce is active
 */

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function ingeni_local_delivery_shipping_method_init() {
		if ( ! class_exists( 'WC_Ingeni_Local_Delivery_Shipping_Method' ) ) {
      require_once 'class-ingeni-local-delivery-shipping-method.php';
      $obj_ingeni_woo = new WC_Ingeni_Local_Delivery_Shipping_Method();
    }
  }
  add_action( 'woocommerce_shipping_init', 'ingeni_local_delivery_shipping_method_init' );

  function add_ingeni_local_delivery_shipping_method( $methods ) {
    $methods['ingeni_local_delivery_shipping_method'] = 'WC_Ingeni_Local_Delivery_Shipping_Method';
    return $methods;
  }
  add_filter( 'woocommerce_shipping_methods', 'add_ingeni_local_delivery_shipping_method' );
}


function ingeni_update_local_delivery_shipping_method() {
	require 'plugin-update-checker/plugin-update-checker.php';
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/BruceMcKinnon/woo-ingeni-local-delivery-shipping-method',
		__FILE__,
		'woo-ingeni-local-delivery-shipping-method'
	);
}
add_action( 'init', 'ingeni_update_local_delivery_shipping_method' );

/*
echo "# woo-ingeni-local-delivery-shipping-method" >> README.md
git init
git add README.md
git commit -m "first commit"
git remote add origin https://github.com/BruceMcKinnon/woo-ingeni-local-delivery-shipping-method.git
git push -u origin master
*/