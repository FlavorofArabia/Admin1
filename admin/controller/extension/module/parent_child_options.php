<?php
//  Parent-child Options
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru

class ControllerExtensionModuleParentChildOptions extends liveopencart\lib\v0020\ControllerAdminExtension {
	
	protected $extension_code='parent_child_options'; // for paths and urls
	
	protected $xlsx_lib;
	
	
	public function index() {
    
		$pcop_lang = $this->load->language('extension/module/parent_child_options');
		
		$links = $this->getLinks();

		$this->document->setTitle($this->language->get('module_name'));
		
		$this->load->model('setting/setting');
		$this->load->model('extension/module/parent_child_options');

		
		$data['redirect'] = $links['redirect'];
		
		$data['ocmod_is_applied']	= $this->model_extension_module_parent_child_options->ocmodIsApplied();
		
		$this->saveSettingsIfAny($data);
		
		$data['settings'] = $this->getCurrentPageSettings();
		
		if ( $this->getXLXSLib()->getAvailability() ) {
			$data['import_export_is_possible'] = true;
		} else {
			$data['xlsx_lib_error'] = true;
			$data['xlsx_lib_name'] = $this->getXLXSLib()->getName();
			$data['lib_install_available'] = $this->user->hasPermission('modify', 'extension/module/product_option_image_pro');
		}
		
		
		$data['user_token'] = $this->session->data['user_token'];
    
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
    
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} 
		
		$data['breadcrumbs'] 	= $links['breadcrumbs'];
		$data['action'] 		= $links['action'];
		$data['cancel'] 		= $links['cancel'];
		
		$data['action_export'] 	= $this->getLinkWithToken( $this->getRouteExtension('', 'export'), '&type='.$this->extension_type);

    
		$data['modules'] = array();
		if (isset($this->request->post['parent_child_options'])) {
			$data['modules'] = $this->request->post['parent_child_options'];
		} elseif ($this->config->get('parent_child_options')) {
			$data['modules'] = $this->config->get('parent_child_options');
		}
		
		if ( $data['ocmod_is_applied'] ) {
			$this->model_extension_module_parent_child_options->checkTables();
		}
		
		$data['module_version'] = $this->model_extension_module_parent_child_options->getCurrentVersion();
		$data['module_info'] = sprintf($this->language->get('module_info'), $data['module_version']);


		$data['config_admin_language'] = $this->config->get('config_admin_language');

		
		$data['extension_code'] = $this->model_extension_module_parent_child_options->getExtensionCode();
    
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
				
		$this->response->setOutput($this->load->view('extension/module/parent_child_options', $data));
    
	}
	
	protected function getXLXSLib($force_using_php_excel=false) {
		
		if ( $force_using_php_excel && (!$this->xlsx_lib || $this->xlsx_lib->getName() != 'PHPExcel' ) ) {
			$this->xlsx_lib = $this->getNewLibInstance('vendors\php_excel', $this->registry);
		}
		
		if ( !$this->xlsx_lib ) {
			$box_spout = $this->getNewLibInstance('vendors\box_spout', $this->registry);
			
			if ( $box_spout->getPossibility() && !$force_using_php_excel ) {
				$this->xlsx_lib = $box_spout;
			} else{
				$this->xlsx_lib = $this->getNewLibInstance('vendors\php_excel', $this->registry);
			}
		}
		return $this->xlsx_lib;
	}
	
	public function installXLSXLib() {
		
		$json = array();
		
		$this->loadLanguage();
		
		if ( !$this->user->hasPermission('modify', 'extension/'.$this->extension_type.'/'.$this->extension_code) ) {
			
			$json['error'] = $this->language->get('error_permission');
			
		} else {
			
			$result = $this->getXLXSLib()->install();
			
			if ( $result ) {
				$json['error'] = $result;
			}
			
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}
  
	
	public function export() {
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['export']) ) {
			
			$this->load->model('setting/setting');
			
			$this->load->model('extension/module/parent_child_options');
			$use_po_ids = isset($this->request->post['use_po_ids']) ? $this->request->post['use_po_ids'] : 0;
			$data = $this->model_extension_module_parent_child_options->getExportData($use_po_ids);
			
			if ( $data ) {
				
				$first_data_row = reset($data); 
				array_unshift($data, array_keys($first_data_row));
        
			}
      
			$this->getXLXSLib()->exportSheetsDataToBrowser(array($data), 'parent_child_options_export.xlsx');
      
			exit;
			
		}
	}
  
	public function import() {
		
		$this->load->language('extension/module/parent_child_options');
		
		$json = array();
		
		if (!empty($this->request->files['file']['name']) && $this->request->files['file']['tmp_name'] ) {
			
			$real_file_name = $this->request->files['file']['tmp_name'];
			
			$force_php_excel = strtolower(substr($real_file_name, -4)) == '.xls';
			
			if ( $this->getXLXSLib($force_php_excel)->getAvailability() ) {
				$data = $this->getXLXSLib()->getSheetDataFromFile($real_file_name, 0);
			} else{
				$json['error'] = sprintf($this->language->get('error_xlsx_lib_is_not_found'), $this->getXLXSLib()->getName());
				if ( $force_php_excel ) {
					$json['error'].= ' '.$this->language->get('error_php_excel_is_necessary_for_xls');
				}
			}
			
			
			if (count($data) > 1) {
				
				$head = array_flip($data[0]);
				$use_po_ids = false;
				
				if (!isset($head['product_id'])) {
					$json['error'] = "column 'product_id' not found";
				}
				
				if (isset($head['product_option_id'])) {
					$use_po_ids = true;
				} elseif (!isset($head['option_id'])) {
					$json['error'] = "column 'option_id' not found";
				}
				
				if (!isset($head['parent_option_id'])) {
					$json['error'] = "column 'parent_option_id' not found";
				}
				
				if (!isset($head['parent_option_values_ids'])) {
					$json['error'] = "column 'parent_option_values_ids' not found";
				}
				
				if (!isset($json['error'])) {
					
					$pcop = array();
					
					for ($i=1;$i<count($data);$i++) {
						
						$row = $data[$i];
						
						$pcop_row = array();
						foreach ($head as $key => $val) {
							$pcop_row[$key] = $row[$val];
						}
            
						$pcop[] = $pcop_row;
            
					}
					
					$this->load->model('extension/module/parent_child_options');
					$delete_before_import = isset($this->request->post['pcop_delete_before_import']) ? (int)$this->request->post['pcop_delete_before_import'] : 0;
					$result = $this->model_extension_module_parent_child_options->importData($pcop, $delete_before_import, $use_po_ids);
					
					$json['products'] = count($result['products']);
					$json['options'] = count($result['options']);
					$json['warnings'] = $result['warnings'];
					
					$json['success'] = $this->language->get('entry_import_ok');
					
				}
				
			} else {
				$json['error'] = "empty table";
			}
			
		} else {
			$json['error'] = "file is not uploaded";
		}
		
		$this->response->setOutput(json_encode($json));
	}
	
	
	
	
	public function install() {
		$this->load->model('extension/module/parent_child_options');
		$this->model_extension_module_parent_child_options->install();
		
		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_parent_child_options', array('module_parent_child_options_status'=>1)); // status = enabled
	}
  
	public function uninstall() {
		$this->load->model('extension/module/parent_child_options');
		$this->model_extension_module_parent_child_options->uninstall();
	}
  
	protected function validate() {
		if ( !$this->user->hasPermission('modify', 'extension/module/parent_child_options') ) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
  
}