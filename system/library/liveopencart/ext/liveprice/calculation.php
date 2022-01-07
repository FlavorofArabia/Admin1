<?php

namespace liveopencart\ext\liveprice;

class calculation {
  
	use \liveopencart\lib\v0020\traits\cache;
	
	protected $registry;
	// option_quantity is 'option_select' for 'price starting from', but is is 'checkbox' for basic calculations
	private $options_selects = array('select','radio','image','block','color','nwd_color','multicolorimage', 'option_quantity');
	private $cache_cart = null;
	private $query_cache_name = 'query_cache';
  
	public function __construct($registry) {
		$this->registry = $registry;
	}
	public function __get($key) {
		return $this->registry->get($key);
	}
	
	public function __call($name, $arguments) {
		$main_extension_methods = array('getSettings', 'getSetting', 'installed', 'getSubPSO', 'getSubIO', 'getSubRO', 'getSubTMDCustomerGroupPrice', 'versionPRO');
		if (in_array($name, $main_extension_methods)) {		
			return call_user_func_array(array($this->liveopencart_ext_liveprice, $name), $arguments);	
		} else {
			$trace = debug_backtrace();
				
			trigger_error('<b>Notice</b>:  Undefined property: '.get_class().'::' . $name . ' in <b>' . $trace[1]['file'] . '</b> on line <b>' . $trace[1]['line'] . '</b>', E_USER_ERROR);
			exit();
		}
	}
  
  //protected function getSetting($key, $default_value=false) {
  //  return $this->liveopencart_ext_liveprice->getSetting($key, $default_value);
  //}
  //protected function installed() {
  //  return $this->liveopencart_ext_liveprice->installed();
  //}
  //
	public function clearCachePrice() {
		$this->clearCache('prices');
	}
	public function clearCacheQueries($query_cache_key='') {
		$this->clearCache($this->query_cache_name, $query_cache_key);
	}
	
	protected function hasQueryCache($cache_name, $cache_key) {
		return $this->hasCache($this->query_cache_name, $cache_name, $cache_key);
	}
	protected function getQueryCache($cache_name, $cache_key) {
		return $this->getCache($this->query_cache_name, $cache_name, $cache_key);
	}
	protected function setQueryCache($cache_name, $cache_key, $cache_value) {
		$this->setCache($this->query_cache_name, $cache_name, $cache_key, $cache_value);
	}
	
	public function hasGlobalDiscounts() {
		if ( !$this->hasCacheSimple(__FUNCTION__) ) {
			$query = $this->db->query("SELECT * FROM ".DB_PREFIX."liveprice_global_discount LIMIT 1");
			$this->setCacheSimple(__FUNCTION__, $query->num_rows);
		}
		return $this->getCacheSimple(__FUNCTION__);
	}
	public function hasGlobalSpecials() {
		if ( !$this->hasCacheSimple(__FUNCTION__) ) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "liveprice_global_special LIMIT 1");
			$this->setCacheSimple(__FUNCTION__, $query->num_rows);
		}
		return $this->getCacheSimple(__FUNCTION__);
	}
	
	public function loadLanguage() {
		$this->load->language('extension/liveopencart/liveprice');
	}
  
	
	protected function getCurrency() {
		if ( isset($this->session->data['currency']) ) {
			$currency =  $this->session->data['currency'];
		} else {
			if ( !$this->model_localisation_currency ) {
				$this->load->model('localisation/currency');
			}
			$currencies = $this->model_localisation_currency->getCurrencies();
			$currency = '';
			if (isset($this->request->cookie['currency']) && !array_key_exists($currency, $currencies)) {
				$currency = $this->request->cookie['currency'];
			}
			if (!array_key_exists($currency, $currencies)) {
				$currency = $this->config->get('config_currency');
			}
		}
		return $currency;
	}
  
	public function format($number) {
		return $this->currency->format($number, $this->getCurrency());
	}
  
	public function getPriceStartingFrom($product_info, $price, $special, $tax ) {
	  
		if ( $this->installed() && ($this->customer->isLogged() || !$this->config->get('config_customer_price')) && $this->versionPRO() ) {
		  
			$starting_prices = array();
			if ( $this->getSetting('starting_from') ) {
				$starting_prices = $this->getProductDefaultPrice($product_info['product_id'], $this->getSetting('starting_from'));
			}
			  
			$f_addon = '';
			if ( $this->getSetting('show_from') ) {
					  
				$show_from = $this->getSetting('show_from');
				if ( ($show_from == 1 && !empty($starting_prices['minimal']))
					|| $show_from == 2
					|| ($show_from == 3 && $this->productHasOptions($product_info['product_id']))
					|| ($show_from == 4 && $this->productHasOptionsWithPrices($product_info['product_id'])) ) {
					$this->loadLanguage();
					$f_addon = $this->language->get('liveprice_from');
				}
			}
			if ( $starting_prices ) {
				$starting_prices['f_price']   = $starting_prices['f_price'] ? $f_addon.$starting_prices['f_price'] : $starting_prices['f_price'];
				$starting_prices['f_special'] = $starting_prices['f_special'] ? $f_addon.$starting_prices['f_special'] : $starting_prices['f_special']; 
				$starting_prices['f_tax']     = $starting_prices['f_tax'] ? $f_addon.$starting_prices['f_tax'] : $starting_prices['f_tax'];
			} else {
				$starting_prices = array();
				$starting_prices['f_price']   = $price ? $f_addon.$price : $price;
				$starting_prices['f_special'] = $special ? $f_addon.$special : $special; 
				$starting_prices['f_tax']     = $tax ? $f_addon.$tax : $tax;
			}
				
			return $starting_prices;
		}
	}
  
	public function getLowestPossiblePriceForProductPageIfEnabled($product_id, $price, $special, $tax, $options=array(), $quantity=1 ) {
	  
		if ( $this->installed() && ($this->customer->isLogged() || !$this->config->get('config_customer_price')) && $this->versionPRO() ) {
		  
			$f_addon = '';
			$lowest_prices = array();
			if ( $this->versionPRO() && $this->getSetting('starting_from_current') ) {
				$lowest_prices = $this->getProductDefaultPrice($product_id, $this->getSetting('starting_from_current'), $options, $quantity);
				//if ( !$lowest_prices['minimal'] ) {
				//  $lowest_prices = array();
				//}
				if ( $lowest_prices['minimal'] && ($this->getSetting('show_from_current') == 1 || ($this->getSetting('show_from_current') == 2 && !$options ) ) ) {
				  $this->loadLanguage();
				  $f_addon = $this->language->get('liveprice_from');
				}
			}
		  
			$lowest_prices['f_price']   = isset($lowest_prices['f_price']) ? $f_addon.$lowest_prices['f_price'] : $price;
			$lowest_prices['f_special'] = isset($lowest_prices['f_special']) ? ($lowest_prices['f_special'] ? $f_addon.$lowest_prices['f_special'] : $lowest_prices['f_special']) : $special; 
			$lowest_prices['f_tax']     = !empty($lowest_prices['f_tax']) ? $f_addon.$lowest_prices['f_tax'] : $tax;
			
			return $lowest_prices;
		}
	}
	
	protected function productHasOptions($product_id) {
		$query = $this->db->query(" SELECT product_option_id FROM ".DB_PREFIX."product_option_value WHERE product_id = ".(int)$product_id." LIMIT 1 ");
		return $query->num_rows;
	}
	protected function productHasOptionsWithPrices($product_id) {
		$query = $this->db->query(" SELECT product_option_id FROM ".DB_PREFIX."product_option_value WHERE product_id = ".(int)$product_id." AND price <> 0 LIMIT 1 ");
		return $query->num_rows;
	}
  
  
  
	function getProductDefaultPrice($product_id, $include_starting_from = false, $options=array(), $quantity=1) {  
    
		if ( $this->installed() && $this->versionPRO() ) {
		  
			$ro_minimal = array();
			
			// to get product price including possible discounts with quantity = 0 and possible specials
			$lp_prices = $this->getProductPrice( $product_id, 1, array(), 0, false, true, true );
			$product_price = $lp_prices['price_opt'];
			
			$sql_min_options = "
				SELECT  POV.product_option_value_id
					  , POV.product_option_id
					  , POV.option_value_id
					  , PO.required
					  , OV.sort_order
					  , O.type
					  , POV.price pov_price
					  , (CASE POV.price_prefix
						  WHEN '+' THEN '".(float)$product_price."' + POV.price
						  WHEN '%' THEN '".(float)$product_price."'/100*(100+POV.price)
															  WHEN '+%' THEN '".(float)$product_price."'/100*(100+POV.price)
															  WHEN '-%' THEN '".(float)$product_price."'/100*(100-POV.price)
															  WHEN '=%' THEN '".(float)$product_price."'/100*(POV.price)
						  WHEN '*' THEN '".(float)$product_price."'*POV.price
						  WHEN '/' THEN (CASE POV.price WHEN 0 THEN '".(float)$product_price."' ELSE '".(float)$product_price."'/POV.price END)
						  WHEN '=' THEN POV.price
						  WHEN '-' THEN '".(float)$product_price."' - POV.price
						  ELSE '".(float)$product_price."'
						END - '".(float)$product_price."') price
				FROM ".DB_PREFIX."product_option_value POV
					,".DB_PREFIX."option_value OV
					,".DB_PREFIX."product_option PO
					,`".DB_PREFIX."option` O
				WHERE POV.product_id = ".(int)$product_id."
				  AND POV.option_value_id = OV.option_value_id
				  AND PO.product_option_id = POV.product_option_id
				  AND OV.option_id = O.option_id
				  AND (POV.subtract = 0 OR POV.quantity > 0)
			";
							  
							  
			$io_defaults = $options;
			// defaults from IO and RO has higher priority than lowest price of option
			// but if some options are specified, IO and RO defaults should be ignored
			if ( $this->getSetting('default_price') ) { 
			  
				// get improved options defaults
				$io_defaults = $this->getSubIO()->getProductDefaultOptions($product_id, $io_defaults);
			  
			}
			
			// only minimal price calculation works for RO (not defaults)
			// calculation is based on the mind that related options are required
			// RO discounts and specials has not affect to the calculation
			$ro_has_different_prices = false;
			if ( $include_starting_from ) {
				$ro_lowest_data = $this->getSubRO()->getProductStartingFromOptions($product_id, $io_defaults, $product_price, $sql_min_options);
				$ro_minimal = $ro_lowest_data['options'];
				$ro_has_different_prices = $ro_lowest_data['has_different_prices'];
			}
			
			$query = $this->db->query(" SELECT PRICES.*
										FROM ( ".$sql_min_options." ) PRICES
										ORDER BY price ASC, sort_order ASC
										");
										//AND PO.required = 1 
		  
			$values_for_calc = array();
			$minimal_options_used = false;
			
			$all_pov_prices_zero = true;
			foreach ($query->rows as $row) {
				if ( $row['pov_price'] != 0 ) {
					$all_pov_prices_zero = false;
					break;
				}
			}
		  
			foreach ($query->rows as $row) {
			  
				// may be shold be improved for checkboxes
				if ( !isset($values_for_calc[$row['product_option_id']]) ) {
				
					$count_before = count($values_for_calc);
				  
					if ( $include_starting_from && isset($ro_minimal[$row['product_option_id']]) ) { // RO min has a priority. price may be in options combination
						$values_for_calc[$row['product_option_id']] = $ro_minimal[$row['product_option_id']];
						$minimal_options_used = true;
					  
					} elseif ( isset($io_defaults[$row['product_option_id']]) ) {
						$values_for_calc[$row['product_option_id']] = $io_defaults[$row['product_option_id']];
					
					} elseif ( $include_starting_from ) { // exclude zero options prices 
						if ( $include_starting_from == 1 && $row['required'] ) { // minimal options prices modifiers for required options
							$values_for_calc[$row['product_option_id']] = $row['product_option_value_id'];
						  
						} elseif ( $include_starting_from == 2 ) { // minimal options prices modifiers for all options
							$values_for_calc[$row['product_option_id']] = $row['product_option_value_id'];
						}
					}
					
					if ( $count_before != count($values_for_calc) ) { // new value added
						if ($row['type'] == 'checkbox') {
							$values_for_calc[$row['product_option_id']] = array( $values_for_calc[$row['product_option_id']] );
						}
						if ( $values_for_calc[$row['product_option_id']] == $row['product_option_value_id'] ) { // minimal option value (it may be equal to defaul value)
						  
							if ( $row['required'] || ( $include_starting_from == 2 ) ) { //
								if ( !$all_pov_prices_zero ) {
									$minimal_options_used = true;
								}
							}
						}
					}
				}
			}
			
			$basis_options = $options;
			if ( !empty($ro_minimal) && !$ro_has_different_prices ) {
				foreach ( $ro_minimal as $po_id => $pov_id ) {
					$basis_options[$po_id] = $pov_id;
				}
			}
			$minimal_options_used = $minimal_options_used && ( $values_for_calc <> $basis_options );
			
			$prices = $this->getProductPriceByParamsArray( array(
				'product_id' => $product_id,
				'quantity' => $quantity,
				'options' => $values_for_calc,
				'use_price_cache' => true,
				'ignore_cart' => true,
				'without_discounts' => true,
				'multiplied_price' => true,
			) );
			
			$defaults = array(
				'price'       =>  $prices['price_old_opt'],
				'special'     =>  $prices['special_opt'],
				'points'      =>  $prices['points'],
				'reward'      =>  $prices['reward'],
				'weight'      =>  $prices['weight'],
				
				'f_price'     =>  $prices['f_price_old_opt'],
				'f_special'   =>  ( ($prices['special']||$prices['special_opt']) ? $prices['f_special_opt'] : '' ),
				'f_tax'       =>  ($prices['config_tax'] ? $prices['f_price_opt_notax'] : $prices['config_tax'] ),
				'tax'         =>  ($prices['config_tax'] ? $prices['price_opt_notax'] : $prices['config_tax']),
				
				'minimal'     => $minimal_options_used,
				
				'minimum'     =>  $prices['minimum'],
				'product_id'  =>  $product_id,
			);
			
			return $defaults;
		}
		return false;
	}
  
	
  

  //$current_quantity < 0 (cart call)
	protected function calculateOptionPrice($product_id, $price, $points, $weight, $p_product_option_data, $get_full_data=false, $quantity=0, $current_quantity=0, $option_data=array(), $option_points=0, $option_weight=0, $stock=true) {
    
		$options = $p_product_option_data['options'];
		$options_types = $p_product_option_data['options_types'];
		$options_values = $p_product_option_data['options_values'];
		
		$price_rewrited = false;
		
		$pso_details = $this->getSubPSO()->getProductSizeOptionDetails($product_id);
		
		$option_price = 0;
    
		foreach ($options as $product_option_id => $option_value) {
		  
			if (!isset($options_types[$product_option_id])) {
				continue;
			}
			
			$calc_multiplier = 1;
			if ( isset($options_types[$product_option_id]['calculate_once']) && $options_types[$product_option_id]['calculate_once'] == 1 ) {
				//$calc_multiplier = 1 / ($current_quantity<0 ? $quantity : ($current_quantity==0 ? 1 : $current_quantity) );
				$calc_multiplier = 1 / max(abs($current_quantity), 1) ;
			}
			
			$options_array = array();
			
			if ( in_array($options_types[$product_option_id]['type'], array('checkbox', 'option_quantity')) && is_array($options_array) ) {
				$options_array = $option_value;
			} elseif ( in_array($options_types[$product_option_id]['type'], $this->options_selects) ) {
				$options_array = array($option_value);
			}
			
			if ( (in_array($options_types[$product_option_id]['type'], $this->options_selects) || $options_types[$product_option_id]['type'] == 'checkbox') && isset($options_values[$product_option_id]) ) {
			  
				$povs = $options_values[$product_option_id];
				
				foreach ($options_array as $product_option_value_id) {
				    $first = !empty($product_option_value_id[0]) ? $product_option_value_id[0] : $product_option_value_id;
					if (array_key_exists($first,$povs) ) {
					  
						$pov = $povs[$product_option_value_id];
						
						$pov['price'] = $this->getSubPSO()->updatePOVPriceBySize($product_id, $product_option_id, $calc_multiplier, $pov['price'], $pso_details);
						$pov['price'] = $this->getSubTMDCustomerGroupPrice()->getChangedPOVPrice($product_option_value_id, $pov['price']);
						
						if ($pov['price'] != 0) {
						
							if ($pov['price_prefix'] == '+') {
							  
								$option_price += $calc_multiplier * $pov['price'];
							  
							} elseif ($pov['price_prefix'] == '-') {
								$option_price -= $calc_multiplier * $pov['price'];
							  
							} elseif ($pov['price_prefix'] == '%' || $pov['price_prefix'] == '+%') {
							  
								$current_price = $price+$option_price;
								$option_price = $current_price*(100+$pov['price'])/100-$price;
								//$option_price = round($current_price*(100+$pov['price'])/100,2)-$price;
							  
											} elseif ($pov['price_prefix'] == '-%') {
								
								$current_price = $price+$option_price;
								$option_price = $current_price*(100-$pov['price'])/100-$price;
								//$option_price = round($current_price*(100-$pov['price'])/100,2)-$price;
												
							} elseif ($pov['price_prefix'] == '=%') {
								
								$current_price = $price+$option_price;
								$option_price = $current_price*($pov['price'])/100-$price;
								//$option_price = round($current_price*($pov['price'])/100,2)-$price;	
							  
							} elseif ($pov['price_prefix'] == '*') {
								$current_price = $price+$option_price;
								$option_price = $current_price*$pov['price']-$price;
								//$option_price = round($current_price*$pov['price'],2)-$price;
							  
							} elseif ($pov['price_prefix'] == '/' && $pov['price']!=0) {
								$current_price = $price+$option_price;
								$option_price = $current_price/$pov['price']-$price;
								//$option_price = round($current_price/$pov['price'],2)-$price;
							  
							} elseif ($pov['price_prefix'] == '=') {
								$current_price = $price+$option_price;
								$option_price = $pov['price']-$price;
								//$option_price = $pov['price']-$current_price;
								$price_rewrited = true;
							}
						}
						
						if ($get_full_data) {
						
							if ( $pov['points'] ) {
								if ($pov['points_prefix'] == '=') {
									$current_points = $points+$option_points;
									$option_points = $pov['points']-$current_points;
								} elseif ($pov['points_prefix'] == '+') {
									$option_points += $calc_multiplier * $pov['points'];
								} elseif ($pov['points_prefix'] == '-') {
									$option_points -= $calc_multiplier * $pov['points'];
								}
							}
										  
							if ($pov['weight_prefix'] == '+') {
								$option_weight += $calc_multiplier * $pov['weight'];
							} elseif ($pov['weight_prefix'] == '-') {
								$option_weight -= $calc_multiplier * $pov['weight'];
							} elseif ($pov['weight_prefix'] == '*') {
								$current_weight = $weight+$option_weight;
								$option_weight = $current_weight*$pov['weight']-$weight;
							  //$option_weight = round($current_weight*$pov['weight'],3)-$weight;
							} elseif ($pov['weight_prefix'] == '=') {
								$current_weight = $weight+$option_weight;
								$option_weight = $pov['weight']-$current_weight;  
							}
							
							if ($pov['subtract'] && (!$pov['quantity'] || ($pov['quantity'] < $quantity))) {
								$stock = false;
							}
							
							$option_data[] = array(
							  'product_option_id'       => $product_option_id,
							  'product_option_value_id' => $product_option_value_id,
							  'option_id'               => $options_types[$product_option_id]['option_id'],
							  'option_value_id'         => $pov['option_value_id'],
							  'name'                    => $options_types[$product_option_id]['name'],
							  'option_value'            => $pov['name'],
							  'value'                   => $pov['name'],
							  'type'                    => $options_types[$product_option_id]['type'],
							  'quantity'                => $pov['quantity'],
							  'subtract'                => $pov['subtract'],
							  //'price'                   => $pov['price'],
							  'price'                   => ($pov['price_prefix']=='+' || $pov['price_prefix']=='-') ? $calc_multiplier*$pov['price'] : $pov['price'],
							  'price_prefix'            => $pov['price_prefix'],
							  'points'                  => $pov['points'],
							  'points_prefix'           => $pov['points_prefix'],
							  'weight'                  => $pov['weight'],
							  'weight_prefix'           => $pov['weight_prefix']
							);
							if ( isset($options_types[$product_option_id]['display']) ) { // comp with other unknown mod
								$option_data[count($option_data)-1]['display'] = $options_types[$product_option_id]['display'];
							}
						}
					}
				}
			  } elseif ( in_array($options_types[$product_option_id]['type'], array('text','textarea','file','date','datetime','time','web2print') ) ) {
				
				if ($get_full_data) {
				
					// for Customer Order Product Upload - myoc_copu.xml - , makes files array
					if ( (is_array($option_value) || is_object($option_value)) && $options_types[$product_option_id]['type'] == 'file') {
						$current_option_values_array = $option_value;
					} else {
						$current_option_values_array = array($option_value);
					}
				  
					foreach ($current_option_values_array as $current_option_value) {
						$option_data[] = array(
							'product_option_id'       => $product_option_id,
							'product_option_value_id' => '',
							'option_id'               => $options_types[$product_option_id]['option_id'],
							'option_value_id'         => '',
							'name'                    => $options_types[$product_option_id]['name'],
							'value'                   => $current_option_value,
							'type'                    => $options_types[$product_option_id]['type'],
							'quantity'                => '',
							'subtract'                => '',
							'price'                   => '',
							'price_prefix'            => '',
							'points'                  => '',
							'points_prefix'           => '',								
							'weight'                  => '',
							'weight_prefix'           => '',
						);
					}
				  
				}
			 
			
			} else {
			  
				$option_data_item = $this->getSubPSO()->getOptionDataItemIfAny($option_value, $product_option_id, $options_types, $pso_details);
				if ( $option_data_item ) {
					$option_price += $option_data_item['price'];
					$option_data[] = $option_data_item;
				}
			  
			}
		  
		}
		
		return array(
			'price_rewrited'  =>$price_rewrited,
			'option_price'    =>$option_price,
			'option_data'     =>$option_data,
			'option_points'   =>$option_points,
			'option_weight'   =>$option_weight,
			'stock'           =>$stock,
		);
	}

  
	// compatibility
	// Custom Price Product - Customer can enter custom price for products flagged as such
	protected function getCustomPrice($product, $options) {
	
		$price = $product['price'];
		if (strtolower($product['sku']) == 'custom' || strtolower($product['location']) == 'custom' || strtolower($product['upc']) == 'custom') {
			if ($options) {
				$pids = array_keys($options);
				
				if ($pids) {
					foreach ($pids as &$pid) {
					  $pid = (int)$pid;
					}
					unset($pid);
					
					$query = $this->db->query("
						SELECT PO.product_option_id, OD.name
						FROM  " . DB_PREFIX . "product_option PO
							, " . DB_PREFIX . "option_description OD
						WHERE PO.product_option_id IN (" . implode(',',$pids) . ")
						  AND PO.option_id = OD.option_id
						  AND OD.language_id = '" . (int)$this->config->get('config_language_id') . "'
					");
					$po_names = array();
					foreach ($query->rows as $row) {
						$po_names[$row['product_option_id']] = $row['name'];
					}
				  
					foreach ($options as $product_option_id => $option_value) {
						if ( isset($po_names[$product_option_id]) && strpos($po_names[$product_option_id], '**') !== false ) {
							$price = (float)$option_value;
							break;
						}
					}
				}
			}
		}
		return $price;
	}
	
	protected function getProductCategoriesDirect($product_id) {
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$query = $this->db->query(" SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' ");
			$categories = array();
			foreach ( $query->rows as $row ) {
				$categories[] = $row['category_id'];
			}
			$this->setQueryCache(__FUNCTION__, $product_id, $categories);
		}
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	
	protected function getProductManufacturer($product_id) {
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$query = $this->db->query(" SELECT manufacturer_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "' ");
			if ( $query->num_rows ) {
				$manufacturer_id = $query->row['manufacturer_id'];
			} else {
				$manufacturer_id = 0;
			}
			$this->setQueryCache(__FUNCTION__, $product_id, $manufacturer_id);
		}	
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	
	public function getProductDiscount( $product_id, $price, $customer_group_id, $discount_quantity, $ro_combs=false) {
		  
		$product_discount_query_data = $this->getProductDiscountQuery((int)$product_id, (int)$customer_group_id, (int)$discount_quantity, $ro_combs);
					
		$product_discount_query = $product_discount_query_data['query'];
		$discount_k = 1;
		if ($product_discount_query && $product_discount_query->num_rows) {
				
			$used_price_prefix = '';
			if ( !empty($product_discount_query->row['price_prefix']) && $product_discount_query->row['price_prefix'] != '=' ) {
					  
				$discount_k = 1;
				if ( $product_discount_query->row['price_prefix'] == '%' ) { // -%
					$discount_k = (100-$product_discount_query->row['price'])/100;
				} elseif ( $product_discount_query->row['price_prefix'] == '_' ) { // =%
					$discount_k = ($product_discount_query->row['price'])/100;
				}
				$product_discount_query->row['price'] = round($price*$discount_k, 2);
				$used_price_prefix = $product_discount_query->row['price_prefix'];
			  
			} else { // =
					  
				$used_price_prefix = '=';
					  
				// Product Currency compatibility
				if ( isset($product_discount_query->row['currency_discount']) ) {
				  $product_discount_query->row['price'] = $this->currency->convert($product_discount_query->row['price'], $product_discount_query->row['currency_discount'], $this->config->get('config_currency'));
				}
			}
				
			return array('price'=>$product_discount_query->row['price'], 'used_price_prefix'=>$used_price_prefix );
				
		}
	}
	
	public function hasProductOwnDiscounts($product_id) {
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$product_has_own_discount = $this->db->query("SELECT product_discount_id FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' LIMIT 1 ")->num_rows;
			$this->setQueryCache(__FUNCTION__, $product_id, $product_has_own_discount);
		}
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	  
	protected function hasProductRODiscounts($product_id) {
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$this->setQueryCache(__FUNCTION__, $product_id, $this->getSubRO()->hasProductRODiscounts($product_id));
		}
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	
	public function getProductDiscountQuery($product_id, $customer_group_id, $discount_quantity, $ro_combs=false) {
		  
		$query_data = array('query'=>false);
		
		if ( $this->hasProductRODiscounts($product_id)  ) { // installed, model loaded (for both)
			$query_data['query'] = $this->getSubRO()->getProductDiscountQuery($ro_combs, $discount_quantity, $customer_group_id);
		}
	  
		if ( empty($query_data['query']) ) { // not set by RO
			
			if ( $this->hasProductOwnDiscounts($product_id) || !$this->versionPRO() ) {
			
				$query_data['query'] = $this->db->query("
					SELECT * FROM " . DB_PREFIX . "product_discount
					WHERE product_id = '" . (int)$product_id . "'
						AND (customer_group_id = '" . (int)$customer_group_id . "' OR customer_group_id = -1 )
						AND ((date_start = '0000-00-00' OR date_start < NOW())
						AND (date_end = '0000-00-00' OR date_end > NOW()))
						".
							($discount_quantity===false ?
									" AND quantity > 1
										ORDER BY quantity ASC, priority ASC, price ASC
									"
								:
									" AND quantity <= '" . (int)$discount_quantity . "'
										AND quantity > 0
										ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1
									"
							)
						."
				");
	
			} else if ($this->versionPRO() && $this->hasGlobalDiscounts() ) { // global discounts should work only if product doesn't have any discount
					
				$categories = $this->getProductCategoriesDirect($product_id);
				$categories[] = -1;
				
				$manufacturer_id = $this->getProductManufacturer($product_id);
				
				$query_data['query'] = $this->db->query("
				  SELECT * FROM " . DB_PREFIX . "liveprice_global_discount
				  WHERE (customer_group_id = '" . (int)$customer_group_id . "' OR customer_group_id = -1 )
					
					AND ((date_start = '0000-00-00' OR date_start < NOW())
					AND (date_end = '0000-00-00' OR date_end > NOW()))
					
					".(
					  $this->versionPRO() && $this->getSetting('discount_multi_filter') ? "
						AND liveprice_global_discount_id IN (
							SELECT D.liveprice_global_discount_id
							FROM ".DB_PREFIX."liveprice_global_discount D
							  LEFT JOIN ".DB_PREFIX."liveprice_global_discount_category DC ON (D.liveprice_global_discount_id = DC.liveprice_global_discount_id)
							WHERE DC.category_id IN (" . implode(',',$categories) . ") 
								  OR DC.category_id IS NULL
						  )
						AND liveprice_global_discount_id IN (
							SELECT D.liveprice_global_discount_id
							FROM ".DB_PREFIX."liveprice_global_discount D
							  LEFT JOIN ".DB_PREFIX."liveprice_global_discount_manufacturer DM ON (D.liveprice_global_discount_id = DM.liveprice_global_discount_id)
							WHERE DM.manufacturer_id = ".(int)$manufacturer_id."
								  OR DM.manufacturer_id IS NULL
						  )
					  " : "
						AND (category_id IN (" . implode(',',$categories) . ") )
						AND (manufacturer_id = '" . (int)$manufacturer_id . "' OR manufacturer_id = -1 )
					  "
					)."    
					
					".(
					  $this->versionPRO() && $this->getSetting('discount_price_min_max') ? "
						AND ( NOT price_start OR price_start <= (SELECT price FROM ".DB_PREFIX."product WHERE product_id = ".$product_id." LIMIT 1 ) )
						AND ( NOT price_end OR price_end >= (SELECT price FROM ".DB_PREFIX."product WHERE product_id = ".$product_id." LIMIT 1 ) )
					  " : ""
					)."
					
					".
					
					  ($discount_quantity===false ?
						  " AND quantity > 1
							ORDER BY quantity ASC, priority ASC, price ASC
						  "
						:
						  " AND quantity <= '" . (int)$discount_quantity . "'
							AND quantity > 0
							ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1
						  "
					  )
					."
				");
			}
		}
		  
		return $query_data;
	}
	
	public function getProductSpecial( $product_id, $price, $customer_group_id, $ro_combs=false) {
		  
		$product_special_query_data = $this->getProductSpecialQuery((int)$product_id, (int)$customer_group_id, $ro_combs);
		  
		$product_special_query = $product_special_query_data['query'];
		$discount_k = 1;
		$special_date_start = '';
		$special_date_end = '';
		if ($product_special_query && $product_special_query->num_rows) {
				
			if ( !empty($product_special_query->row['date_start']) && $product_special_query->row['date_start'] != '0000-00-00' ) {
				$special_date_start = $product_special_query->row['date_start'];
			}
			if ( !empty($product_special_query->row['date_end']) && $product_special_query->row['date_end'] != '0000-00-00' ) {
				$special_date_end = $product_special_query->row['date_end'];
			}
			
			if ( !empty($product_special_query->row['price_prefix']) && $product_special_query->row['price_prefix'] != '=' ) {
				$discount_k = 1;
				if ( $product_special_query->row['price_prefix'] == '%' ) { // -%
					$discount_k = (100-$product_special_query->row['price'])/100;
				} elseif ( $product_special_query->row['price_prefix'] == '_' ) { // =%
					$discount_k = ($product_special_query->row['price'])/100;
				}
				$product_special_query->row['price'] = round($price*$discount_k, 2);
				$used_price_prefix = $product_special_query->row['price_prefix'];
			  
			} else { // =
					  
				$used_price_prefix = '=';
					  
				// Product Currency compatibility
				if ( isset($product_special_query->row['currency_special']) ) {
					$product_special_query->row['price'] = $this->currency->convert($product_special_query->row['price'], $product_special_query->row['currency_special'], $this->config->get('config_currency'));
				}
			}
				  
			$current_price = $product_special_query->row['price'];
			//return array('price'=>$current_price, 'used_price_prefix'=>$used_price_prefix );
		  
			//return $product_special_query->row['price'];
		}
			
		if ( isset($current_price) ) {
			return array('price'=>$current_price, 'used_price_prefix'=>$used_price_prefix, 'special_date_start'=>$special_date_start, 'special_date_end'=>$special_date_end );
		}
	}
	
	public function hasProductOwnSpecials($product_id) {
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$product_has_own_specials = $this->db->query("SELECT product_special_id FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "' LIMIT 1 ")->num_rows;
			$this->setQueryCache(__FUNCTION__, $product_id, $product_has_own_specials);
		}
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	protected function hasProductROSpecials($product_id) {
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$this->setQueryCache(__FUNCTION__, $product_id, $this->getSubRO()->hasProductROSpecials($product_id));
		}
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	
	public function getProductSpecialQuery($product_id, $customer_group_id=false, $ro_combs=false) {
	  
		$query_data = array('query'=>false);
		  
		if ( $this->hasProductROSpecials($product_id) ) { // installed, model loaded (for both)
			$query_data['query'] = $this->getSubRO()->getProductSpecialQuery($ro_combs, $customer_group_id);
		} 
	  
		if ( empty($query_data['query']) ) { // not set by RO
		
			if ( $this->hasProductOwnSpecials($product_id) || !$this->versionPRO() ) {
			
				$query_data['query'] = $this->db->query("
					SELECT * FROM " . DB_PREFIX . "product_special
					WHERE product_id = '" . (int)$product_id . "'
						".
						($customer_group_id!==false ? " AND (customer_group_id = '" . (int)$customer_group_id . "' OR customer_group_id = -1 ) " : "")
						."
						AND ((date_start = '0000-00-00' OR date_start < NOW())
						AND (date_end = '0000-00-00' OR date_end > NOW()))
					ORDER BY priority ASC, price ASC
					LIMIT 1
				");
			
			} else if ( $this->versionPRO() && $this->hasGlobalSpecials() ) { // global specials should work only if product doesn't have any own special
					
				$categories = $this->getProductCategoriesDirect($product_id);
				$categories[] = -1;
				
				$manufacturer_id = $this->getProductManufacturer($product_id);
				
				$query_data['query'] = $this->db->query("
					SELECT * FROM " . DB_PREFIX . "liveprice_global_special
					WHERE (category_id IN (" . implode(',',$categories) . ") )
						".
						  ($customer_group_id!==false ? " AND (customer_group_id = '" . (int)$customer_group_id . "' OR customer_group_id = -1 ) " : "")
						."
						AND (manufacturer_id = '" . (int)$manufacturer_id . "' OR manufacturer_id = -1 )
						AND (filter_id IN ( SELECT filter_id FROM ".DB_PREFIX."product_filter WHERE product_id=".(int)$product_id." ) OR filter_id = -1 )
						AND ((date_start = '0000-00-00' OR date_start < NOW())
						AND (date_end = '0000-00-00' OR date_end > NOW()))
					ORDER BY priority ASC, price ASC
					LIMIT 1
				");
			}
		}
		  
		return $query_data;
	}
	
	protected function arrayKeysToInt($arr) {
		$new_arr = array();
		foreach ( $arr as $key => $val ) {
			$new_arr[(int)$key] = $val;
		}
		return $new_arr;
	}
	  
	protected function arrayDeleteEmpty($arr) {
		$new_arr = array();
		foreach ($arr as $key => $val) {
			if ($val) {
				$new_arr[$key] = $val;
			}
		}
		return $new_arr;
	}
	  
	public function normalizeArrayOfOptions($p_arr) {
		$new_arr = array();
		if ( is_array($p_arr) ) { 
			foreach ( $p_arr as $key => $val ) {
			  if ( is_object($val) ) {
					$new_val = get_object_vars($val);
			  } else {
					$new_val = $val;
			  }
			  if ( $new_val ) {
					$new_arr[(int)$key] = $new_val;
			  }
			}
		}
		return $new_arr;
	}
	  
	protected function getProductSettingDiscountQuantity($product_id) {
	  
		$discount_quantity = $this->getSetting('discount_quantity', 0);
		
		if ( $this->getSetting('discount_quantity_customize') && $this->getSetting('dqc') ) {
			foreach ($this->getSetting('dqc') as $dqc) {
				if ( !empty($dqc['products']) ) {
					if ( in_array($product_id, $dqc['products']) ) {
						$discount_quantity = $dqc['discount_quantity'];
					}
				}
				if ( !empty($dqc['manufacturers']) ) {
					$query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "product WHERE product_id = ".(int)$product_id." ");
					if ( $query->num_rows ) {
						if ( in_array($query->row['manufacturer_id'], $dqc['manufacturers']) ) {
							$discount_quantity = $dqc['discount_quantity'];
						}
					}
				}
				if ( !empty($dqc['categories']) && is_array($dqc['categories']) ) {
					array_walk($dqc['categories'], 'intval');
					$query = $this->db->query("
						SELECT category_id
						FROM " . DB_PREFIX . "product_to_category
						WHERE product_id = ".(int)$product_id."
							AND category_id IN (".implode(',', $dqc['categories']).")
					");
					if ( $query->num_rows ) {
						$discount_quantity = $dqc['discount_quantity'];
					}
				}
			}
		}
	
		if ($discount_quantity == 2 && !$this->getSubRO()->installed()) {
			$discount_quantity = 1;
		}
		  
		return $discount_quantity;
	  
	}

	protected function getOptionDataKey($p_option_dt) {
		
		$key = '';
		
		$key_parts = array('option_id', 'option_value_id', 'product_option_id', 'product_option_value_id');
		foreach ( $key_parts as $key_part ) {
			$key.= '_';
			if ( isset($p_option_dt[$key_part]) ) {
				$key.= $p_option_dt[$key_part];
			}
		}
		return $key;
	}
	
	protected function putOptionDataFields($p_option_data, $p_data) {
		
		if ( $p_data ) {
			foreach ($p_option_data as &$option_dt) {
				$option_key = $this->getOptionDataKey($option_dt);
				
				foreach ($p_data as $field => $field_values) {
					if ( isset($field_values[$option_key]) ) {
						$option_dt[$field] = $field_values[$option_key];
					}
				}
			}
			unset($option_dt);
		}
		return $p_option_data;
	}
	
	protected function getOptionDataFields($p_option_data, $p_fields) {
		$data = array();
		foreach ( $p_option_data as $option_dt ) {
			foreach ( $p_fields as $field ) {
				if ( isset($option_dt[$field]) ) {
					if ( !isset($data[$field]) ) {
						$data[$field] = array();
					}
					$option_key = $this->getOptionDataKey($option_dt);
					$data[$field][$option_key] = $option_dt[$field];
				}
			}
		}
		return $data;
	}
	
	// prepare (read) sets of product options for usage
	protected function prepareDataOfProductOptions($p_product_id, $p_options) {
		
		$cache_key = json_encode(func_get_args());
		if ( !$this->hasQueryCache(__FUNCTION__, $cache_key) ) {
		
			$options_types = array();
			$options_values = array();
			
			$product_option_ids = array();
			$product_option_value_ids = array();
			foreach ($p_options as $product_option_id => $option_value) {
				if (!in_array($product_option_id, $product_option_ids)) $product_option_ids[] = (int)$product_option_id;
			}
			
			if ( count($product_option_ids) != 0 ) {
				
				$options_query = $this->db->query(" SELECT po.*, od.name, o.* 
																						FROM " . DB_PREFIX . "product_option po
																							LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id)
																							LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id)
																						WHERE po.product_option_id IN (" . implode(",", $product_option_ids) . ")
																							AND po.product_id = '" . (int)$p_product_id . "'
																							AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");
				foreach ($options_query->rows as $row) {
					$options_types[$row['product_option_id']] = $row;
				}
			
				foreach ($p_options as $product_option_id => $option_value) {
					
					if (!isset($options_types[$product_option_id])) continue;
					
					if ( in_array($options_types[$product_option_id]['type'], array('checkbox', 'option_quantity')) && is_array($option_value)) {
						foreach ($option_value as $product_option_value_id) {
							if (!in_array((int)$product_option_value_id, $product_option_value_ids)) {
								$product_option_value_ids[] = (int)$product_option_value_id;
							}
						}
					} elseif ( in_array($options_types[$product_option_id]['type'], $this->options_selects) ) {
						if (!in_array((int)$option_value, $product_option_value_ids)) {
							$product_option_value_ids[] = (int)$option_value;
						}
					}
				}
				
				if ( count($product_option_ids) != 0 && count($product_option_value_ids) != 0 ) { // в $product_option_ids могут быть опции не подходящих типов
					 $option_value_query = $this->db->query("
						SELECT  pov.*, ovd.name
						FROM " . DB_PREFIX . "product_option_value pov
							LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id)
							LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id)
						WHERE pov.product_option_value_id IN (" . implode(",", $product_option_value_ids) . ")
							AND pov.product_option_id IN (" . implode(",", $product_option_ids) . ")
							AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'
					");
					foreach ($option_value_query->rows as $row) {
						
						// Product Currency compatibility
						if ( isset($row['currency_option']) ) {
							$row['price'] = $this->currency->convert($row['price'], $row['currency_option'], $this->config->get('config_currency'));
						}
						
						if (!isset($options_values[$row['product_option_id']])) {
							$options_values[$row['product_option_id']] = array();
						}
						$options_values[$row['product_option_id']][$row['product_option_value_id']] = $row;
					}
				}
				
			}
		
			$this->setQueryCache(__FUNCTION__, $cache_key, array(
				'options'=>$p_options,
				'options_types'=>$options_types,
				'options_values'=>$options_values,
				'product_option_ids'=>$product_option_ids,
				'product_option_value_ids'=>$product_option_value_ids,
			) );
		}
		return $this->getQueryCache(__FUNCTION__, $cache_key);
	}
	
	public function getProductQuery($product_id) {
		
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$query = $this->db->query("
				SELECT *
				FROM " . DB_PREFIX . "product p
					LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
				WHERE p.product_id = '" . (int)$product_id . "'
					AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
					AND p.date_available <= NOW()
					AND p.status = '1'
			");
			$this->setQueryCache(__FUNCTION__, $product_id, $query);
		}
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	
	protected function getProductRewardQuery($product_id, $customer_group_id) {
		$cache_key = ''.$product_id.'_'.$customer_group_id;
		if ( !$this->hasQueryCache(__FUNCTION__, $cache_key) ) {
			$query = $this->db->query("
				SELECT points FROM " . DB_PREFIX . "product_reward
				WHERE product_id = '" . (int)$product_id . "'
					AND customer_group_id = '" . (int)$customer_group_id . "'
			");
			$this->setQueryCache(__FUNCTION__, $cache_key, $query);
		}
		return $this->getQueryCache(__FUNCTION__, $cache_key);
	}
	
	protected function getProductDownloadQuery($product_id) {
		if ( !$this->hasQueryCache(__FUNCTION__, $product_id) ) {
			$query = $this->db->query("
				SELECT * FROM " . DB_PREFIX . "product_to_download p2d
					LEFT JOIN " . DB_PREFIX . "download d ON (p2d.download_id = d.download_id)
					LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id)
				WHERE p2d.product_id = '" . (int)$product_id . "'
					AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'
			");
			$this->setQueryCache(__FUNCTION__, $product_id, $query);
		}
		return $this->getQueryCache(__FUNCTION__, $product_id);
	}
	
	protected function getProductRecurringQuery($product_id, $recurring_id, $customer_group_id) {
		$cache_key = ''.$product_id.'_'.$recurring_id.'_'.$customer_group_id;
		if ( !$this->hasQueryCache(__FUNCTION__, $cache_key) ) {
			$query = $this->db->query("
				SELECT *
				FROM `" . DB_PREFIX . "recurring` `p`
					JOIN `" . DB_PREFIX . "product_recurring` `pp` ON `pp`.`recurring_id` = `p`.`recurring_id` AND `pp`.`product_id` = ".(int)$product_id."
					JOIN `" . DB_PREFIX . "recurring_description` `pd` ON `pd`.`recurring_id` = `p`.`recurring_id` AND `pd`.`language_id` = ".(int)$this->config->get('config_language_id')."
				WHERE `pp`.`recurring_id` = " . (int)$recurring_id . "
					AND `status` = 1
					AND `pp`.`customer_group_id` = " . (int)$customer_group_id."
			");
			$this->setQueryCache(__FUNCTION__, $cache_key, $query);
		}
		return $this->getQueryCache(__FUNCTION__, $cache_key);
	}
	
	protected function applyDiscountSpecialToPrices( $product_id, $quantity, $current_quantity, $price, $points, $weight, $ro_price_modificator, $customer_group_id, $product_option_data, $to_total, $calc_discount, $ds_price_data ) {
		
		if ( !$to_total || $ds_price_data['used_price_prefix'] == '=' ) { // basic calc
							
			$price = $ds_price_data['price'];
			if ( $ds_price_data['used_price_prefix'] == '=' && !empty($ro_price_modificator) ) {
				
				$price+= $ro_price_modificator;
			}
			
			// calc options
			$option_price_data = $this->calculateOptionPrice( $product_id, $price, $points, $weight, $product_option_data, true, $quantity, $current_quantity, array(), 0, 0, true );
			
		} else { // % discount/special to total
			
			// calc options
			$option_price_data = $this->calculateOptionPrice( $product_id, $price, $points, $weight, $product_option_data, true, $quantity, $current_quantity, array(), 0, 0, true );
			
			// apply discount/special to options
			if ( $calc_discount ) { // discount
				$ds_option_price_data = $this->getProductDiscount( $product_id, $option_price_data['option_price'], $customer_group_id, $quantity, isset($ro_combs)?$ro_combs:false);	
			} else { // special
				$ds_option_price_data = $this->getProductSpecial( $product_id, $option_price_data['option_price'], $customer_group_id, isset($ro_combs)?$ro_combs:false);
			}
			
			$option_price_data['option_price'] = $ds_option_price_data['price'];

			$price = $ds_price_data['price'];
      
		}
		
		return array( 'option_price_data' => $option_price_data, 'price' => $price );
	}
	
	protected function getCartProducts($use_cart_cache=false, $use_ro_data_cache=false) {
    
		if ( $use_cart_cache && $this->hasCacheSimple('cart_products') ) {
			return $this->getCacheSimple('cart_products');
		} else {
		  
			$cart_products = array();
			
			$cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
			foreach ($cart_query->rows as $cart_product) {
				$cart_product['cart_quantity'] = $cart_product['quantity'];
				$cart_product['option'] = (array)json_decode($cart_product['option'], true);
				
				//get ro data always
				$cart_product['ro_price_data_cart'] = $this->getSubRO()->getROCombsByPOIds($cart_product['product_id'], $cart_product['option'], true, -1, $use_ro_data_cache);
				
				$cart_products[] = $cart_product;
			}
			
			if ( $use_cart_cache ) {
				$this->setCacheSimple('cart_products', $cart_products);
			}
			return $cart_products; 
		}
	}
  
	protected function getProductCartQuantity($product_id, $options, $ro_combs, $settings_discount_quantity, $use_cart_cache, $use_ro_data_cache) {
	  
		$quantity = 0;
		
		foreach ($this->getCartProducts($use_cart_cache, $use_ro_data_cache) as $cart_product) {
		  
			$cart_product_id = $cart_product['product_id'];
			$cart_quantity = $cart_product['cart_quantity'];
			
			if ($cart_product_id == $product_id) {
			
				// Options
				if (!empty($cart_product['option'])) {
					$cart_options = $cart_product['option'];
				} else {
					$cart_options = array();
				}
				$cart_options = $this->arrayKeysToInt($cart_options);
				
				if ( $settings_discount_quantity==1 ) { // by options
				  
					if ($options == $cart_options) {
						$quantity = $quantity + $cart_quantity;
					}
				  
				} elseif ( $settings_discount_quantity==2 ) { // by related options combination
				  
					if ( $ro_combs ) {
					  
						if ( $ro_combs == $cart_product['ro_price_data_cart']) {
							$quantity = $quantity + $cart_quantity;
						}
					  
					} elseif ($options == $cart_options) {
						$quantity = $quantity + $cart_quantity;
					}
				  
				} else { // by product
					$quantity = $quantity + $cart_quantity;
				}
			} 
		}
		return $quantity;
	  
	}
  
	protected function getPriceValueKeysForTemplateOfPrices() {
		return array(  // without taxes
			'product_price',            // product price
			'price_old',                // product price with discount, but without special
			'price_old_opt',            // product price with discount, but without special, and with options
			'special',                  // product special price
			'special_opt',              // product special price with options
			'price',                    // product price with discount and special (special ignore discount)
			'price_opt',                // product price with discount and special (special ignore discount) with options
			'option_price',             // option price modificator
			'option_price_special',     // option price modificator for specials
			'price_opt_notax',          // product price with discount and special (special ignore discount)
		  
		);
	}
  
  protected function getTemplateOfPrices() {
		return array_fill_keys($this->getPriceValueKeysForTemplateOfPrices(), null) + array(  // without taxes
		  
			'special_date_end' => '',
			
			 // with taxes and formatted
			'f_product_price' => 0,            // product price
			'f_price_old' => 0,               // product price with discount, but without special
			'f_price_old_opt' => 0,            // product price with discount, but without special, and with options
			'f_special' => 0,                  // product special price
			'f_special_opt' => 0,              // product special price
			'f_price' => 0,                    // product price with discount and special (special ignore discount)
			'f_price_opt' => 0,                // product price with discount and special (special ignore discount)
			'f_option_price' => 0,             // option price modificator
			
			
			// without taxes and formatted
			'f_product_price_notax' => 0,            // product price
			'f_price_old_notax' => 0,                // product price with discount, but without special
			'f_price_old_opt_notax' => 0,            // product price with discount, but without special
			'f_special_notax' => 0,                  // product special price
			'f_special_opt_notax' => 0,              // product special price
			'f_price_notax' => 0,                    // product price with discount and special (special ignore discount)
			'f_price_opt_notax' => 0,                // product price with discount and special (special ignore discount)
			'f_option_price_notax' => 0,             // option price modificator
			//, 'f_discounts_notax' => array()
			
			'config_tax' => $this->config->get('config_tax'),
			'points' => 0,
			'reward' => 0,
			'weight' => 0,
			'minimum' => 0,
			'option_data' => array(),
			'tax_class_id'=> '',
			'weight_class_id' => '',
		);
	}
  
	public function getProductPriceByParamsArray($params) {
		
		$product_id 				= isset($params['product_id']) ? $params['product_id'] : 0 ;
		$quantity 					= isset($params['quantity']) ? $params['quantity'] : 1 ;
		$options 					= isset($params['options']) ? $params['options'] : array() ;
		$recurring_id 				= isset($params['recurring_id']) ? $params['recurring_id'] : 0 ;
		$multiplied_price 			= isset($params['multiplied_price']) ? $params['multiplied_price'] : false ;
		$use_cart_cache 			= isset($params['use_cart_cache']) ? $params['use_cart_cache'] : false ;
		$use_price_cache 			= isset($params['use_price_cache']) ? $params['use_price_cache'] : false ;
		$use_ro_data_cache			= isset($params['use_ro_data_cache']) ? $params['use_ro_data_cache'] : false ;
		$without_discounts 			= isset($params['without_discounts']) ? $params['without_discounts'] : false ;
		$ignore_cart 				= isset($params['ignore_cart']) ? $params['ignore_cart'] : false ;
		$discount_quantity_addition	= isset($params['qpo_discount_quantity_addition']) ? $params['qpo_discount_quantity_addition'] : 0 ; // comp for old controller
		$discount_quantity_addition	= isset($params['discount_quantity_addition']) ? $params['discount_quantity_addition'] : 0 ;
		
		return $this->getProductPrice($product_id, $quantity, $options, $recurring_id, $multiplied_price, $use_cart_cache, $use_price_cache, $without_discounts, $ignore_cart, $discount_quantity_addition, $use_ro_data_cache);
		
	}
	
	// PARAMS:
  // $product_id,
  // $current_quantity ( use 0 for cart )
  // $options = array( $product_option_id => $product_option_value_id )
  // $recurring_id
  // RESULTS:
  // &$prices=array(), &$product_data=array(), &$option_data=array()
  // if $current_quantity < 0 - cart call with current cart quantity
  public function getProductPrice($p_product_id, $current_quantity=1, $options=array(), $recurring_id=0, $multiplied_price=false, $use_cart_cache=false, $use_price_cache=false, $without_discounts=false, $ignore_cart=false, $discount_quantity_addition=0, $use_ro_data_cache=false) {
    
		$product_id = (int)$p_product_id;
		$current_quantity = (int)$current_quantity;
		
		// <0 - cart call
		if ($current_quantity==0) {
			$current_quantity = 1;
		}
		
		$customer_group_id = (int)$this->config->get('config_customer_group_id'); // config_customer_group_id can be temporarily overrided for some calls, so we have to use it in caches
    
		$cache_price_key = json_encode( array($customer_group_id, $product_id, $current_quantity, $options, $recurring_id, $without_discounts) );
		if ( $use_price_cache && $this->hasCache('prices', $cache_price_key) ) {
			$prices = $this->getCache('prices', $cache_price_key);
		} else {
		  
			$settings_discount_quantity = $this->getProductSettingDiscountQuantity($product_id);
			
			// get price taking all combinations of RO into account (even out-of-stock)
			$ro_combs = $this->getSubRO()->getROCombsByPOIds($product_id, $options, true, true, $use_ro_data_cache);
			//$ro_combs = $this->getSubRO()->getROCombsByPOIds($product_id, $options, true, -1, $use_ro_data_cache);
			
			// $options = $this->arrayKeysToInt( $this->arrayDeleteEmpty($options) );
			$options = $this->normalizeArrayOfOptions( $options );
				  
			// to save extra fields of options (values)
			//$option_data_fields = $this->getOptionDataFields($option_data, array('opt_image', 'model')); // save extra fields
			
			//$product_data = array();
			$option_data = array();
			$prices = $this->getTemplateOfPrices();
			
			$quantity = MAX($current_quantity,0); // for cart call, total quantity (for disconts), doesn't include current cart row quantity (to not include it twice)
			
			if ( $current_quantity<0 || ( !$this->getSetting('ignore_cart') && empty($ignore_cart) ) ) {
				$quantity+= $this->getProductCartQuantity($product_id, $options, $ro_combs, $settings_discount_quantity, $use_cart_cache, $use_ro_data_cache);
			}
				  
			// taking other current cobinations of options into account maybe needed (only if discounts are product based)
			if ( $discount_quantity_addition && !$settings_discount_quantity ) {  // for some mods like qpo
				$quantity+= $discount_quantity_addition; 
			}
			
			// $quantity  - full quantity for discount ($current_quantity + cart quantity (sometimes, depends on settings) )
			// $current_quantity  - quantity for current product calc
			
			$quantity = max($quantity, 1);
			$real_quantity = max(1, abs($current_quantity));
			
			$stock = true;
			
			$product_query = $this->getProductQuery($product_id);
			
			if ($product_query->num_rows) {
			  
				// << for RO
				$ro_custom_fields = $this->getSubRO()->getCustomFields($product_query->row, $ro_combs);
				if ( $ro_custom_fields ) {
					$product_query->row['weight'] = $ro_custom_fields['weight'];
				}
				// >> for RO
				
				$product_query->row['price'] = $this->getSubTMDCustomerGroupPrice()->getChangedProductPrice($product_id, $product_query->row['price']);
				
				// Product Currency compatibility
				if ( isset($product_query->row['currency_product']) ) {
					$product_query->row['price'] = $this->currency->convert($product_query->row['price'],  $product_query->row['currency_product'], $this->config->get('config_currency'));
				  
				// comp with "Мультивалютные товары"  
				} elseif ( !empty($product_query->row['currency_id']) && $product_query->row['currency_id'] != $this->config->get('config_currency') ) {
					$product_currency_id = $product_query->row['currency_id'];
				}
				
				
				$product_query->row['price'] = $this->getCustomPrice($product_query->row, $options);
				
				$option_price = 0;
				$option_points = 0;
				$option_weight = 0;
				
				$product_option_data = $this->prepareDataOfProductOptions($product_id, $options);
				$options_types = $product_option_data['options_types'];
				$product_option_ids = $product_option_data['product_option_ids'];
						
				$product_query->row['price'] = $this->liveopencart_ext_liveprice->getSubPSO()->updateProductPriceInBeginning($product_option_ids, $options, $options_types, $product_query->row['price']);
				
				// << for RO
				$ro_price_modificator = 0;
				if ($ro_combs) {
					$ro_price_data = $this->getSubRO()->calcProductPriceWithRO($product_query->row['price'], $ro_combs,0,0,0,$quantity);
					$product_query->row['price'] = $ro_price_data['price'];
					$ro_price_modificator = $ro_price_data['price_modificator'];
					
					if ( $this->getSetting('ropro_discounts_addition') && isset($ro_price_data['discount_addition']) ) {
						$ro_discount_addition = $ro_price_data['discount_addition'];
					}
					if ($this->getSetting('ropro_specials_addition') && isset($ro_price_data['special_addition']) ) {
						$ro_special_addition = $ro_price_data['special_addition']; // to +/- product special by RO special if the setting is enabled
					}
				}
				// >> for RO
		
				$price = $product_query->row['price'];
				$price_wo_options = $price; // fix the price to exclude the influence of ro_price_modificator
				$prices['product_price'] = $price;
				
				if ( empty($ro_special_addition) ) {
					// standard way
					$special_price_data = $this->getProductSpecial($product_id, $price, $customer_group_id, isset($ro_combs)?$ro_combs:false);
				} else {
					// exclude $ro_price_modificator for calculation of special if there is RO addition to specials (enabled by the specific setting of Live Price )
					$special_price_data = $this->getProductSpecial($product_id, $price-$ro_price_modificator, $customer_group_id, isset($ro_combs)?$ro_combs:false);
				}
				
				$percent_special_to_total = $this->getSetting('percent_special_to_total');
				$discount_like_special = false;
				
				if ( (empty($special_price_data) && empty($ro_special_addition)) || $quantity == 1 ) { // calc discount only if no special is set
				//if ( empty($special_price_data) && empty($ro_special_addition) ) { // calc discount only if no special is set
					if ( empty($ro_discount_addition) ) {
						// standard way
						$discount_price_data = $this->getProductDiscount($product_id, $price, $customer_group_id, $quantity, isset($ro_combs)?$ro_combs:false);
					} else {
						// exclude $ro_price_modificator for calculation of discount if there is RO addition to discounts (enabled by the specific setting of Live Price )
						$discount_price_data = $this->getProductDiscount($product_id, $price-$ro_price_modificator, $customer_group_id, $quantity, isset($ro_combs)?$ro_combs:false);
					}
					
					if ( !empty($discount_price_data) ) {
						
						if ( $this->getSetting('discount_like_special') && (empty($special_price_data) && empty($ro_special_addition)) ) {
						//if ( $this->getSetting('discount_like_special') ) {
							$percent_special_to_total = $this->getSetting('percent_discount_to_total');
							$special_price_data = $discount_price_data;
							$discount_price_data = false;
							$discount_like_special = true;
						}
						
						if ( !empty($discount_price_data) ) {
							if ( empty($ro_discount_addition) ) {
								// standard way
								$discount_data = $this->applyDiscountSpecialToPrices( $product_id, $quantity, $current_quantity, $price, $product_query->row['points'], $product_query->row['weight'], $ro_price_modificator, $customer_group_id, $product_option_data, $this->getSetting('percent_discount_to_total'), true, $discount_price_data );
							} else {
								// because there is the specific addition for the discount:
								// - use basic price without affecting by RO additions for the calculation of the product discount
								// - do not use $ro_price_modificator
								$discount_data = $this->applyDiscountSpecialToPrices( $product_id, $quantity, $current_quantity, $price-$ro_price_modificator, $product_query->row['points'], $product_query->row['weight'], 0, $customer_group_id, $product_option_data, $this->getSetting('percent_discount_to_total'), true, $discount_price_data );
								$discount_data['price']+= $ro_discount_addition; // basic discount is calculated, let's add the addition
							}
							$price 							= $discount_data['price'];
							$option_price_data 	= $discount_data['option_price_data'];
						}
					} else {
						// no basic discount
					
						if ( !empty($ro_discount_addition) ) { // no matter exists basic discount or not, this addition should be applyed (and $ro_price_modificator excluded)
							$price+= -$ro_price_modificator +$ro_discount_addition;
						}
					}
				}
				
				// save prices without special
				if ( empty($option_price_data) ) {
					$option_price_data = $this->calculateOptionPrice( $product_id, $price, $product_query->row['points'], $product_query->row['weight'], $product_option_data, true, $quantity, $current_quantity, $option_data, $option_points, $option_weight, $stock );
				}
				$price_for_options = $price; // on this step it should not affect the price (on this step it is needed only to fix prices w/o special)
				if ( !empty($option_price_data['price_rewrited']) && !empty($ro_price_modificator) ) {
					$price_for_options+= $ro_price_modificator;
				}
				
				$prices['price_old'] 			= $price_wo_options;
				$prices['price_old_opt']  = $price_for_options + $option_price_data['option_price'];
				
				// apply special
				if ( !empty($special_price_data) ) {
					
					if ( empty($ro_special_addition) ) {
						// standard way
						$special_data = $this->applyDiscountSpecialToPrices( $product_id, $quantity, $current_quantity, $price, $product_query->row['points'], $product_query->row['weight'], $ro_price_modificator, $customer_group_id, $product_option_data, $percent_special_to_total, $discount_like_special, $special_price_data );
					} else {
						// because there is the specific addition for the special:
						// - use basic price without affecting by RO additions for the calculation of the product special
						// - do not use $ro_price_modificator 
						
						$special_data = $this->applyDiscountSpecialToPrices( $product_id, $quantity, $current_quantity, $price-$ro_price_modificator, $product_query->row['points'], $product_query->row['weight'], 0, $customer_group_id, $product_option_data, $percent_special_to_total, $discount_like_special, $special_price_data );
						$special_data['price']+= $ro_special_addition; // basic special calculated, let's add the addition
					}
					
					$price 							= $special_data['price'];
					$option_price_data 	= $special_data['option_price_data'];
					$prices['special_date_start'] = !empty($special_price_data['special_date_start']) ? $special_price_data['special_date_start'] : '' ;
					$prices['special_date_end'] = !empty($special_price_data['special_date_end']) ? $special_price_data['special_date_end'] : '' ;
					
				} else { // no basic special
					
					if ( !empty($ro_special_addition) ) { // not matter exists basic special or not, this addition should be applyed (and $ro_price_modificator excluded)
						$price+= -$ro_price_modificator +$ro_special_addition;
					}
				}
				
				$price_wo_options = $price;
				if ( !empty($option_price_data['price_rewrited']) && !empty($ro_price_modificator) ) {
					$price+= $ro_price_modificator;
				}
				
				$option_price   = $option_price_data['option_price'];
				$option_data    = $option_price_data['option_data'];
				$option_points  = $option_price_data['option_points'];
				$option_weight  = $option_price_data['option_weight'];
				$stock          = $option_price_data['stock'];
						
				if ( !empty($special_price_data) || !empty($ro_special_addition) ) {
					$prices['option_price_special'] = $option_price;
					$prices['special'] 				= $price_wo_options;
					$prices['special_opt']          = $price + $option_price;
				}
						
				$prices['price']        = $price;
				$prices['option_price'] = $option_price;
				$prices['price_opt']    = $price + $option_price;
				
				$prices['option_weight'] = $option_weight;
						
				// Reward Points
				$product_reward_query = $this->getProductRewardQuery($product_id, $customer_group_id);
				
				if ($product_reward_query->num_rows) {	
					$reward = $product_reward_query->row['points'];
				} else {
					$reward = 0;
				}
				
				// Downloads		
				$download_data = array();     		
				
				$download_query = $this->getProductDownloadQuery($product_id);
			  
				foreach ($download_query->rows as $download) {
					$download_data[] = array(
						'download_id' => $download['download_id'],
						'name'        => $download['name'],
						'filename'    => $download['filename'],
						'mask'        => $download['mask'] //,
						//'remaining'   => $download['remaining']
					);
				}
				
				// Stock
				if (!$product_query->row['quantity'] || ($product_query->row['quantity'] < $quantity)) {
					$stock = false;
				}
				
				$recurring_query = $this->getProductRecurringQuery($product_id, $recurring_id, $customer_group_id);
		  
				if ($recurring_query->num_rows) {
					$recurring = array(
						'recurring_id'    => $recurring_id,
						'name'            => $recurring_query->row['name'],
						'frequency'       => $recurring_query->row['frequency'],
						'price'           => $recurring_query->row['price'],
						'cycle'           => $recurring_query->row['cycle'],
						'duration'        => $recurring_query->row['duration'],
						'trial'           => $recurring_query->row['trial_status'],
						'trial_frequency' => $recurring_query->row['trial_frequency'],
						'trial_price'     => $recurring_query->row['trial_price'],
						'trial_cycle'     => $recurring_query->row['trial_cycle'],
						'trial_duration'  => $recurring_query->row['trial_duration']
					);
				} else {
					$recurring = false;
				}
				
				$prices['reward'] = $reward * $quantity;
				$prices['weight'] = ($product_query->row['weight'] + $option_weight) * $quantity;
				$prices['minimum'] = $product_query->row['minimum'];
				
				
				$price_multiplier = 1;
				if ($multiplied_price && $this->getSetting('multiplied_price')) {
					$price_multiplier = MAX(1, $current_quantity);
					// multiplier should be applied only to formatted prices and to points
				}
				
				if ( !empty($product_currency_id) && $product_currency_id != $this->config->get('config_currency') ) {
					foreach ($this->getPriceValueKeysForTemplateOfPrices() as $price_val_key) {
						$prices[$price_val_key] = $this->convertFromCurrency($prices[$price_val_key], $product_currency_id);
					}
				}
				
				$prices['points']                   = ( ($product_query->row['points'] || $option_points!=0) ? ($product_query->row['points'] + $option_points) * $price_multiplier : 0) ;
						
				$prices['tax_class_id'] 			= $product_query->row['tax_class_id'];
				$prices['weight_class_id'] 			= $product_query->row['weight_class_id'];
				
				$prices['price_opt_notax']          = $price_multiplier*$prices['price_opt'];
				
				
				
				$prices['f_product_price_notax']    = $this->format($prices['product_price']);
				$prices['f_price_old_notax']        = $this->format($prices['price_old']);
				$prices['f_price_old_opt_notax']    = $this->format($prices['price_old_opt']);
				$prices['f_special_notax']          = $this->format($prices['special']);
				$prices['f_special_opt_notax']      = $this->format($prices['special_opt']);
				$prices['f_option_price_notax']     = $this->format($prices['option_price']);
				
				$prices['f_price_notax']            = $this->format($price_multiplier*$prices['price']);
				$prices['f_price_opt_notax']        = $this->format($prices['price_opt_notax']);
				$prices['f_product_price']          = $this->format($price_multiplier*$this->tax->calculate($prices['product_price'], $prices['tax_class_id'], $this->config->get('config_tax')));
				$prices['f_price_old']              = $this->format($price_multiplier*$this->tax->calculate($prices['price_old'], $prices['tax_class_id'], $this->config->get('config_tax')));
				$prices['f_price_old_opt']          = $this->format($price_multiplier*$this->tax->calculate($prices['price_old_opt'], $prices['tax_class_id'], $this->config->get('config_tax')));
				$prices['f_special']                = $this->format($price_multiplier*$this->tax->calculate($prices['special'], $prices['tax_class_id'], $this->config->get('config_tax')));
				$prices['f_special_opt']            = $this->format($price_multiplier*$this->tax->calculate($prices['special_opt'], $prices['tax_class_id'], $this->config->get('config_tax')));
				$prices['f_price']                  = $this->format($price_multiplier*$this->tax->calculate($prices['price'], $prices['tax_class_id'], $this->config->get('config_tax')));
				$prices['f_price_opt']              = $this->format($price_multiplier*$this->tax->calculate($prices['price_opt'], $prices['tax_class_id'], $this->config->get('config_tax')));
				$prices['f_option_price']           = $this->format($price_multiplier*$this->tax->calculate($prices['option_price'], $prices['tax_class_id'], $this->config->get('config_tax')));
				
				
				$prices['total'] = $this->generatePriceArray($prices['price_old_opt'], $prices['special_opt'], $prices['tax_class_id'], $this->config->get('config_tax'), $price_multiplier);
				$prices['item'] = $this->generatePriceArray($prices['price_old_opt'], $prices['special_opt'], $prices['tax_class_id'], $this->config->get('config_tax'));
				
				
				
				//$prices['price_opt_notax_item'] 	= $prices['price_opt'];
				//$prices['f_price_opt_notax_item']	= $this->format($prices['price_opt_notax_item']);
				//
				//$prices['price_opt_tax_item'] 		= $this->tax->calculate($prices['price_opt'], $prices['tax_class_id'], true);
				//$prices['f_price_opt_tax_item']		= $this->format($prices['price_opt_tax_item']);
				//
				//$prices['price_old_opt_item']  		= $prices['price_old_opt'];
				//$prices['f_price_old_opt_item']     = $this->format($this->tax->calculate($prices['price_old_opt_item'], $prices['tax_class_id'], $this->config->get('config_tax')));
				//
				//$prices['special_opt_item']  		= $prices['special_opt'];
				//$prices['f_special_opt_item']     	= $this->format($this->tax->calculate($prices['special_opt'], $prices['tax_class_id'], $this->config->get('config_tax')));
				
				$prices['option_data'] = $option_data;
				
				$this->setCache('prices', $cache_price_key, $prices);
			}
		  
			if ( !$without_discounts ) {
			
				// required for html generation, placed here for better related options compatibility
				$ro_discounts = $this->getSubRO()->getRODiscounts($customer_group_id, $ro_combs);
		
				if ( !empty($ro_discounts) ) {
					$discounts = $ro_discounts;
				} else {
					$this->load->model('catalog/product');
					$discounts = $this->model_catalog_product->getProductDiscounts($product_id);
				}
				
				$prices['discounts'] = array(); 
				foreach ($discounts as $discount) {
					
					if ( $discount['quantity'] > 1 ) {
						
						if ( $this->getSetting('percent_discount_to_total') && !empty($discount['price_prefix']) && $discount['price_prefix'] != '=' ) {
							$prices['discounts'][] = array(
								'quantity' => $discount['quantity'],
								'price'    => ''.$discount['price_prefix'].' '.(float)$discount['price']
							);
						} else {
							
							$discount_prices = $this->getProductPriceByParamsArray(array(
								'product_id'=>$product_id,
								'quantity'=>$discount['quantity'],
								'options'=>$options,
								'without_discounts'=>true,
								'ignore_cart'=>true,
							) );
							
							if ( $this->getSetting('discount_like_special') ) {
								$discount_price = $discount_prices['price_opt'];
							} else {	
								$discount_price = $discount_prices['price_old_opt']; // not affected by specials
							}
							
							$prices['discounts'][] = array(
								'quantity' 		=> $discount['quantity'],
								'price'    		=> $this->format($this->tax->calculate($discount_price, $prices['tax_class_id'], $this->config->get('config_tax'))),
								'prices'		=> $this->generatePriceArray($discount_price, 0, $prices['tax_class_id'], $this->config->get('config_tax')),
							);
						}
					}
				}
			}
		}

		return $prices;
	}
	
	public function generatePriceArray($price, $special, $tax_class_id, $config_tax, $multiplier=1) {
		$data = ['_tax'=>[], '_notax'=>[]];
		
		$fields = ['price','special','final'];
		
		$data['_notax']['price_val'] 		= $price;
		$data['_notax']['special_val'] 		= $special;
		$data['_notax']['price_final_val'] 	= ($special ? $special : $price);
		
		foreach ( $data['_notax'] as $key => $val ) {
			$data['_tax'][$key] = $this->tax->calculate($val, $tax_class_id, true);
		}
		
		if ( $multiplier != 1 ) {
			array_walk($data['_notax'], function(&$val) use ($multiplier){
				$val = $val * $multiplier;
			});
			array_walk($data['_tax'], function(&$val) use ($multiplier){
				$val = $val * $multiplier;
			});
		}
		
		// formatting
		$keys = array_keys($data['_notax']);
		foreach ( $keys as $key ) {
			$new_key 					= substr($key, 0, strpos($key, '_val'));
			$data['_notax'][$new_key] 	= $this->format($data['_notax'][$key]);
			$data['_tax'][$new_key] 	= $this->format($data['_tax'][$key]);
		}
		
		if ( $config_tax ) {
			$data['tax'] 		= $data['_notax']['price_final'];
			$data['tax_val'] 	= $data['_notax']['price_final_val'];
			foreach ($data['_tax'] as $key => $val) {
				$data[$key] = $val;
			}
		} else {
			$data['tax'] 		= false;
			$data['tax_val'] 	= false;
			foreach ($data['_notax'] as $key => $val) {
				$data[$key] = $val;
			}
		}
		
		return $data;
	}
  
	public function convertFromCurrency($price, $currency_id) {
		$currency_code = $this->getCurrencyCode($currency_id);
		if ( $currency_code == $this->config->get('config_currency') ) {
			return $price;
		} else {
			return $this->currency->convert($price, $currency_code, $this->config->get('config_currency'));
		}
	}
	
	protected function getCurrencyCode($currency_id) {
		$cache_key = __FUNCTION__.$currency_id;
		 if ( !$this->hasCacheSimple($cache_key) ) {
			$query = $this->db->query("SELECT `code` FROM ".DB_PREFIX."currency WHERE currency_id = ".(int)$currency_id." ");
			$code = $query->num_rows ? $query->row['code'] : '';
			$this->setCacheSimple($cache_key, $code);
		}
		return $this->getCacheSimple($cache_key);
	  
	}
  
  
}