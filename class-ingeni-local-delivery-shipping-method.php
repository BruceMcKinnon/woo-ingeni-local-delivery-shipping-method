<?php

// 
// https://gist.github.com/mikejolley/6713608
// https://code.tutsplus.com/tutorials/create-a-custom-shipping-method-for-woocommerce--cms-26098
//
class WC_Ingeni_Local_Delivery_Shipping_Method extends WC_Shipping_Method {

  public function __construct( $instance_id = 0 ) {
		$this->instance_id = absint( $instance_id );
		$this->id = 'ingeni_local_delivery_shipping_method';
	  $this->method_title = __( 'Map-Based Local Delivery Area', 'woocommerce' );
		$this->method_description = __( 'Shipping Method for Map-Based Local Delivery areas' ); // Description shown in admin

		$this->supports = array(
			'shipping-zones',
			'settings',
			//'instance-settings',
			//'instance-settings-modal',
		);

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		// Define user set variables
		if ( !isset( $this->settings['enabled'] ) ) {
			$this->settings['enabled'] = 'no';
		}

		$this->enabled = $this->settings['enabled'];

    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Map-Based Local Delivery Area', 'woocommerce' );

		$this->init();

		// Delivery zone maps
		$this->zone_1_map = array();
		$this->zone_2_map = array();
		$this->zone_3_map = array();
		$this->zone_4_map = array();


		// Load the delivery area boundaries from a mympas.google.com KML file - 1 layer per KML
		$this->loadMaps();

	}

	function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		
		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array(&$this, 'process_admin_options'));

		//$this->fb_log('key='. $this->id . '_'.$this->instance_id);
	}

  public function init_form_fields(){
		$this->enabled = $this->get_option( 'enabled', 'yes' );
		$this->title = $this->get_option( 'title', 'Map-based Local Delivery' );
		$this->cost_zone_1 = $this->get_option( 'cost_zone_1', 5 );
		$this->cost_zone_2 = $this->get_option( 'cost_zone_2', 10 );
		$this->cost_zone_3 = $this->get_option( 'cost_zone_3', 15 );
		$this->cost_zone_4 = $this->get_option( 'cost_zone_4', 20 );
		$this->api_key = $this->get_option( 'api_key', '' );
		$this->cost_zone_4 = $this->get_option( 'cost_zone_4', 20 );


  		$this->form_fields = array(
		    'enabled' => array(
		      'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
		      'type' 			=> 'checkbox',
		      'label' 		=> __( 'Enable Local Delivery Shipping', 'woocommerce' ),
		      'default' 		=> $this->enabled
		    ),
		    'title' => array(
		      'title' 		=> __( 'Method Title', 'woocommerce' ),
		      'type' 			=> 'text',
		      'description' 	=> __( 'Map-based Local Delivery.', 'woocommerce' ),
		      'default'		=> __( $this->title, 'woocommerce' ),
				),
				'cost_zone_1' => array(
					'title' => __( 'Zone 1', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'Zone 1 charge', 'woocommerce' ),
					'default' => $this->cost_zone_1
				),
				'cost_zone_2' => array(
					'title' => __( 'Zone 2', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'Zone 2 charge', 'woocommerce' ),
					'default' => $this->cost_zone_2
				),
				'cost_zone_3' => array(
					'title' => __( 'Zone 3', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'Zone 3 charge', 'woocommerce' ),
					'default' => $this->cost_zone_3
				),
				'cost_zone_4' => array(
					'title' => __( 'Zone 4', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'Zone 4 charge', 'woocommerce' ),
					'default' => $this->cost_zone_4
				),
				'api_key' => array(
		      'title' 		=> __( 'Google API key', 'woocommerce' ),
		      'type' 			=> 'text',
		      'description' 	=> __( 'Google Geocoder API key.', 'woocommerce' ),
		      'default'		=> $this->api_key,
				),
			);
  	}

		private function load_map($filename, &$zone) {
			$retVal = false;
			$curr_dir = plugin_dir_path( __FILE__ );
//$this->fb_log('current: '.$curr_dir);
			if (file_exists($curr_dir . $filename)) {
			
				$contents = file_get_contents($curr_dir . $filename);
				$xml = new SimpleXMLElement($contents);
			
				$values = (string)$xml->Document->Placemark->Polygon->outerBoundaryIs->LinearRing->coordinates;
	
				$coords = explode(',0',$values);
				foreach($coords as $coord) {   
					$args = explode(',', $coord);
					if ( ($args[0] != '') && ($args[1] != '') ) {
						// Store as lat / lng
						$zone[] = array($args[1], $args[0]);
					}
				}

				$retVal = true;
			}

			return $retVal;
		}


		function loadMaps() {
			$this->zone_1_map = array();
			$this->zone_2_map = array();
			$this->zone_3_map = array();
			$this->zone_4_map = array();

			$this->load_map('DeliveryZone1.kml',$this->zone_1_map);
			$this->load_map('DeliveryZone2.kml',$this->zone_2_map);
			$this->load_map('DeliveryZone3.kml',$this->zone_3_map);
			$this->load_map('DeliveryZone4.kml',$this->zone_4_map);
		}


  	public function is_available( $package ){
			$retVal = true;
//$this->fb_log('package = '.print_r($package,true));
			if ( $this->settings['enabled'] != 'yes' ) {
				return $retVal;
			}
/*
  		foreach ( $package['contents'] as $item_id => $values ) {
	      $_product = $values['data'];
	      $weight =	$_product->get_weight();
	      if( $weight > 5 ){
//fb_log('too heavy!!! = '.$weight);
	      	return false;
	      }
	  	}
*/
	  	return $retVal;
		}
		



		//
		// Test if a point is inside a polygon. http://tutorialspots.com/php-detect-point-in-polygon-506.html
		//
		//
		function in_delivery_area($point, $polygon) {
				if($polygon[0] != $polygon[count($polygon)-1])
						$polygon[count($polygon)] = $polygon[0];
				$j = 0;
				$oddNodes = false;
				$x = $point[1];
				$y = $point[0];
				$n = count($polygon);
				for ($i = 0; $i < $n; $i++)
				{
						$j++;
						if ($j == $n)
						{
								$j = 0;
						}
						if ((($polygon[$i][0] < $y) && ($polygon[$j][0] >= $y)) || (($polygon[$j][0] < $y) && ($polygon[$i][0] >=
								$y)))
						{
								if ($polygon[$i][1] + ($y - $polygon[$i][0]) / ($polygon[$j][0] - $polygon[$i][0]) * ($polygon[$j][1] -
										$polygon[$i][1]) < $x)
								{
										$oddNodes = !$oddNodes;
								}
						}
				}
				return $oddNodes;
		}


		function geocode_delivery_address($address, &$latlng) {
			$retVal = false;
//$this->fb_log("geocode_delivery_address: >".$address."<");		


			$reduced_addr = "latlng-".sanitize_title($address);
			$stored_latlng = get_option($reduced_addr);


			if ( $stored_latlng) {
				// Pull town and lat/lng from the WP database
				$stored = explode('|',$stored_latlng);
		//local_debug_log('pulled from db: '.print_r($stored,true));
				$town = $stored[0];
				$lat = $stored[1];
				$lng = $stored[2];

				$latlng = array( $stored[1], $stored[2] );

				$retVal = true;
			}


			if (!$stored_latlng) {
				// This is a new address, so hit up Google
				$baseURL = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
				$api_key = $this->settings['api_key'];

				if ( $api_key != '' ) {
					$addressURL = urlencode($address) . '&key=' . $api_key;
					$lookup_url = $baseURL . $addressURL;

//$this->fb_log('url: '.$lookup_url);	
					
					$ch = curl_init($lookup_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
						
					$jsonfile = curl_exec($ch);
					curl_close($ch);
//$this->fb_log('raw: '.print_r($jsonfile,true));


					$latlng = json_decode($jsonfile,true);
					
//$this->fb_log('latlng: '.print_r($latlng,true));


					if ($latlng['status'] == 'OK') {
						// get the lat lng and town - these get stored into the WP database
						$lat = $latlng["results"][0]["geometry"]["location"]["lat"];
						$lng = $latlng["results"][0]["geometry"]["location"]["lng"];
			//local_debug_log('lat/lng: '.$lat.'/'.$lng);			
						//Get town name from json data
						for ( $idx = 0; $idx < count( $latlng["results"][0]["address_components"] ); $idx++ ) {
							$types = array($latlng["results"][0]["address_components"][$idx]["types"][0]);
							if (in_array( "locality", $types ) ) {
									$town = $latlng["results"][0]["address_components"][$idx]["long_name"];
			//local_debug_log('town: '.$town);						
							}
						}

						update_option($reduced_addr,$town.'|'.$lat.'|'.$lng);

						$latlng = array( $lat, $lng );
						$retVal = true;
					} 
				}
			}

			return $retVal;
		}



  	public function calculate_shipping( $package = array() ){
//$this->fb_log('calc this package:'.print_r($package['destination'],true));
			try {

				if ( $this->settings['enabled'] == 'no' ) {
					return;
				}

				// Local delivery charges
				$zone_1_cost = $this->settings['cost_zone_1'];
				$zone_2_cost = $this->settings['cost_zone_2'];
				$zone_3_cost = $this->settings['cost_zone_3'];
				$zone_4_cost = $this->settings['cost_zone_4'];

				// Allowed shipping classes
				//$permitted_shipping_classes = $this->settings['avail_classes'];
//$this->fb_log('permitted: '.print_r($permitted_shipping_classes,true));



				// First, get the lat/lng of the shipping address
				$delivery_latlng = array();
				$addr = $package['destination']['address'].' '.$package['destination']['city'].' '.$package['destination']['state'].' '.$package['destination']['postcode'];
				if ( $this->geocode_delivery_address( $addr, $delivery_latlng ) ) {


					if ( count($this->zone_1_map) > 0) {
						// Test Zone 1
						if ( $this->in_delivery_area( $delivery_latlng, $this->zone_1_map ) ) {
							// send the final rate to the user. 
							$this->add_rate( array(
								'id' 	=> $this->id,
								'label' => $this->title . ' Zone #1',
								'cost' 	=> $this->settings['cost_zone_1']
							) );

						} else {
$this->fb_log('***** not in area!! ****' );
						}

					}

				}



//$this->fb_log(print_r($package,true));
/*
				//get the total weight and dimensions
				$weight = 0;
				$dimensions = 0;
				$shipping_class_ok = true;
				foreach ( $package['contents'] as $item_id => $values ) {
					$_product  = $values['data'];
//fb_log(print_r($_product,true));

					$shipping_class_id = $_product->shipping_class_id; // Shipping class ID
//fb_log('shipping class: ['.$shipping_class_id.'] ');

					// Check that this product is in the listy of permitted shipping classes
					if ( is_array($permitted_shipping_classes) ) {
						if ( !in_array( $shipping_class_id, $permitted_shipping_classes) ) {
							$shipping_class_ok = false;
//fb_log('shipping class OK');
						}
					}

//fb_log($_product->get_title().'  weight:'.$weight.' prod weight:'.$_product->get_weight().' qty:'.$values['quantity']);
//fb_log($_product->get_title().'  prod length:'.$_product->length.' width:'.$_product->width );
					$weight =	$weight + ($_product->get_weight() * $values['quantity']);
					$dimensions = $dimensions + (($_product->length * $values['quantity']) * $_product->width * $_product->height);
				}

//fb_log('weight:'.$weight.' volume:'.$dimensions);
				
				//calculate the cost according to the volume
				$cost = 0;
				$satchel_name = "";
				if ( ( $weight < $satchel_max_weight ) && ( $shipping_class_ok ) ) {
					if ($dimensions < $satchel_small_volume) {
						$cost = $satchel_small_cost;
						$satchel_name = "Small";
					} elseif ($dimensions < $satchel_medium_volume) {
						$cost = $satchel_medium_cost;
						$satchel_name = "Medium";
					} elseif ($dimensions < $satchel_large_volume) {
						$cost = $satchel_large_cost;
						$satchel_name = "Large";
					} elseif ($dimensions < $satchel_xlarge_volume) {
						$cost = $satchel_xlarge_cost;
						$satchel_name = "Extra large";
					}
				}
//fb_log('satchel_name'.	$satchel_name);
				if ( $satchel_name != "" ) {
					// send the final rate to the user. 
					$this->add_rate( array(
						'id' 	=> $this->id,
						'label' => $this->title . ' '.$satchel_name,
						'cost' 	=> $cost
					));
				}
*/
			} catch (Exception $ex) {
				fb_log( 'calculate_shipping() satchel_name' .	$satchel_name . ' : ' . $ex->getMessage() );
			}
  	}



  private function fb_log($msg) {
    $upload_dir = wp_upload_dir();
    $logFile = $upload_dir['basedir'] . '/' . 'fb_log.txt';
    date_default_timezone_set('Australia/Sydney');

    // Now write out to the file
    $log_handle = fopen($logFile, "a");
    if ($log_handle !== false) {
      fwrite($log_handle, date("H:i:s").": ".$msg."\r\n");
      fclose($log_handle);
    }
  }



}
