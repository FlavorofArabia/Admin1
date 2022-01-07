<?php
//  Parent-child Options
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru
namespace liveopencart;
class parent_child_options_common {
	protected $registry;
	
	use lib\v0020\traits\theme;
	use lib\v0020\traits\cache;
	
	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($key) {
		return $this->registry->get($key);
	}
	
	public function installed() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'module' AND `code` = 'parent_child_options'");
		return $query->num_rows;
	}

	public function getThemeName() {
		if ($this->config->get('config_theme') == 'theme_default') {
			return $this->config->get('theme_default_directory');
		} else {
			return substr($this->config->get('config_theme'), 0, 6) == 'theme_' ? substr($this->config->get('config_theme'), 6) : $this->config->get('config_theme') ;
		}
	}
	
	public function getSettings() {
		if ( !$this->hasCacheSimple('settings') ) {
			$settings = $this->config->get('module_parent_child_options_settings');
			if ( !is_array($settings) ) {
				$settings = array();
			}
			$this->setCacheSimple('settings', $settings);
		}
		return $this->getCacheSimple('settings');
	}

	
	public function getResourceLinkPathWithVersion($path) {
		
		return $path.'?v='.filemtime(DIR_APPLICATION.$path);
	}
	
	protected function getResourceLocalFilePath($basic_path) {
		if ( $this->inAdminSection() ) { // admin section
			return DIR_CATALOG.$basic_path;
		} else { // customer section
			return DIR_APPLICATION.$basic_path;
		}
	}
	
	protected function inAdminSection() {
		return defined('DIR_CATALOG') && defined('HTTP_CATALOG');
	}
	
	// to link /catalog/ resources correctly for both admin and customer section
	public function getCatalogResourceLinkPathWithVersion($basic_path) {
		
		$file_path = $this->getResourceLocalFilePath($basic_path);
		if ( $this->inAdminSection() ) {
			$script_path = HTTP_CATALOG.'catalog/'.$basic_path;
			$remove_prefixes = array('http:', 'https:');
			foreach ( $remove_prefixes as $remove_prefix ) {
				if ( strpos($script_path, $remove_prefix) === 0 ) {
					$script_path = substr($script_path, strlen($remove_prefix));
				}
			}
		} else {
			$script_path = 'catalog/'.$basic_path;
		}
		
		$modified = filemtime( $file_path );
		
		
		
		return $script_path.'?v='.$modified; 
		
		//return $path.'?v='.filemtime(DIR_APPLICATION.$path);
	}
	
	private function existCatalogResource($path) {
		return file_exists( $this->getResourceLocalFilePath($path));
	}
	
	public function getProductPageScriptPath() {
		
		$basic_path = 'view/javascript/liveopencart/parent_child_options/pcop_front.js';
		
		return $this->getCatalogResourceLinkPathWithVersion($basic_path);
		
	}
	
	public function getProductPageThemeScriptPath() {
		
		$basic_path = 'view/theme/extension_liveopencart/parent_child_options/theme/'.$this->getThemeName().'/product_page.js';
		if ( $this->existCatalogResource($basic_path) ) {
			return $this->getCatalogResourceLinkPathWithVersion($basic_path);
		}
	}
	
	private function tablesExist() {
	
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'pcop"');
		if ( $query->num_rows ) {
			$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'pcop_value"');
			if ( $query->num_rows ) {
				return true;
			}
		}
	
		return false;
	}
	
	public function getProductPageScripts() {
		$scripts = array();
		if ( $this->installed() ) {
			$scripts[] =$this->getProductPageScriptPath();
			$theme_script = $this->getProductPageThemeScriptPath();
			if ( $theme_script ) {
				$scripts[] = $theme_script;
			}
		}
		return $scripts;
	}
	
	public function addProductPageScripts() {
		if ( $this->installed() ) {
			foreach ( $this->getProductPageScripts() as $script ) {
				$this->document->addScript($script);
			}
		}
	}
	
	public function updatePOVTempIds($pcop_temp_ids, $product_option_value, $product_option_value_id ) {
    
		if ( !empty($product_option_value) ) { // == Parent-child Options
			if ( !empty($product_option_value_id) ) {
				$pcop_product_option_value_id = $product_option_value_id;
			} else {
				$pcop_product_option_value_id = $this->db->getLastId();
				$this->reinsertProductOptionValue($pcop_product_option_value_id);
			}
			if ( isset($product_option_value['product_option_value_temp_id']) ) {
				$pcop_temp_ids['pov'][$product_option_value['product_option_value_temp_id']] = $pcop_product_option_value_id;
			} elseif ( isset($product_option_value['product_option_value_id']) ) {
				$pcop_temp_ids['pov'][$product_option_value['product_option_value_id']] = $pcop_product_option_value_id;
			}
		} 
		
		return $pcop_temp_ids;
	}
  
	public function reinsertProductOption($product_option_id) { 
        
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option WHERE product_option_id = ".(int)$product_option_id." ");
		if ( $query->num_rows ) {
			
			$sql_set = "";
			foreach ($query->row as $key => $value) {
				$sql_set .= ", `".$key."` = '".$this->db->escape($value)."' ";
			}
			$sql_set = substr($sql_set, 1);
			$this->db->query("DELETE FROM ".DB_PREFIX."product_option WHERE product_option_id = ".(int)$product_option_id." ");
			$this->db->query("INSERT INTO ".DB_PREFIX."product_option SET ".$sql_set);
		}
	
	}
  
	public function reinsertProductOptionValue($product_option_value_id, $data_to_update=array()) { // reinsert_product_option_value
        
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = ".(int)$product_option_value_id." ");
		if ( $query->num_rows ) {
			
			$sql_set = "";
			foreach ($query->row as $key => $value) {
				if ( isset($data_to_update[$key]) ) {
					$value = $data_to_update[$key];
				}
				$sql_set .= ", `".$key."` = '".$this->db->escape($value)."' ";
			}
			
			$sql_set = substr($sql_set, 1);
			$this->db->query("DELETE FROM ".DB_PREFIX."product_option_value WHERE product_option_value_id = ".$product_option_value_id." ");
			$this->db->query("INSERT INTO ".DB_PREFIX."product_option_value SET ".$sql_set);
				
		}
	
	}
	
	// product_option_id & product_option_value_id, old name getPCOPForProductOption
	public function getProductOptionFrontParents($product_option_id) {
	
		if (!$this->tablesExist()) return false;
		
		$pcop = array();
		
		$language_id = (int)$this->config->get('config_language_id');
		
		// PCOP.product_option_id - product_option_id, PCOP.parent_option_id - option_id of parent
		$query_pcop = $this->db->query("SELECT PCOP.pcop_id, PCOP.pcop_or, PCOP.product_id, PCOP.parent_product_option_id
																		FROM  `".DB_PREFIX."pcop` PCOP
																		WHERE PCOP.product_option_id = '" . (int)$product_option_id . "'
																		");
		
		foreach ($query_pcop->rows as $row_pcop) {
			
			$query_pcop_values = $this->db->query(" SELECT PCOPV.parent_product_option_value_id
																							FROM  `".DB_PREFIX."pcop_value` PCOPV
																							WHERE PCOPV.pcop_id = '" . (int)$row_pcop['pcop_id'] . "'
																						");
			$row_pcop['values'] = array();
			foreach ($query_pcop_values->rows as $row_pcop_value) {
				$row_pcop['values'][] = $row_pcop_value['parent_product_option_value_id'];
				//$row_pcop['values'][] = $row_pcop_value['product_option_value_id'];
			}
		
			$pcop[] = $row_pcop;
		}

		return $pcop;
	}
	
	public function makeHiddenProductOptionsNotRequiredForCart($product_options) {
		 if (isset($this->request->post['options_pcop_not_required']) && $this->request->post['options_pcop_not_required']) {
          $pcop_not_required =  explode(',', $this->request->post['options_pcop_not_required']);
          
          foreach ($product_options as &$product_option) {
            if ( in_array($product_option['product_option_id'], $pcop_not_required) ) {
              $product_option['required'] = false;
							unset($this->request->post['copu_product_id'][$product_option['product_option_id']]); // custom file upload
            }
          }
          unset($product_option);
        }
		return $product_options;
	}


   

}