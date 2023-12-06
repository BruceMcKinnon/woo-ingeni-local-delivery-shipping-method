=== Ingeni Local Delivery Shipping Method for WooCommerce ===

Contributors: Bruce McKinnon
Tags: woocommerce, local delivery
Requires at least: 4.8
Tested up to: 5.1.1
Stable tag: 2020.01

A custom shipping method for WooCommerce that allows custom delivery zones to be specified using a KML file. Handy where you offer local delivery for addresses within a specified distance from your shop.



== Description ==

* - Uses KML geospatial files to define local delivery zones.




== Installation ==

1. Upload the 'woo-ingeni-local-delivery-shipping-method' folder to the '/wp-content/plugins/' directory.

2. Activate the plugin through the 'Plugins' menu in WordPress.

3. Enable the shipping method via WooCommerce



== Frequently Asked Questions ==

Q - Does this use Google services?

A - Yes - You'll need your own Google Geocoder API key.


Q - Can I have multiple delivery zones?

A - Yes. The plugin supports up to four KML files, each defining a delivery zone. The files must be named DeliveryZone1.kml - DeliveryZone4.kml and reside in the 'woo-ingeni-local-delivery-shipping-method' plugin folder.


Q - Can I have different charges for each delivery zone?

A - Yes. You can specify a per-zone delivery charge.


Q - How do I make a KML file?

A - Use mymaps.google.com to create your own delivery area boundaries, which can be downloaded as KML files. One layer per KML, specifying the delivery area.





== Changelog ==

v2020.01 - Initial version.
