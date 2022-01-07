<?php
//  Parent-child Options
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru

// Heading
$_['module_name']           = 'Parent-child Options';
$_['heading_title']         = 'LIVEOPENCART: '.$_['module_name'];
$_['text_edit']             = 'Edit '.$_['module_name'].' Module';

// Text
$_['text_module']           = 'Modules';
$_['text_success']          = 'Settings are modified!';
$_['text_content_top']      = 'Content Top';
$_['text_content_bottom']   = 'Content Bottom';
$_['text_column_left']      = 'Column Left';
$_['text_column_right']     = 'Column Right';
$_['text_m_all_options']    = 'All available options';
$_['text_m_support']        = "Developer: <a href='https://liveopencart.com' target='_blank'>liveopencart.com</a> | Support, questions and suggestions: <a href=\"mailto:support@liveopencart.com\">support@liveopencart.com</a>";




// for the product edit page
$_['pcop_entry_settings']               = 'Parent options';
$_['pcop_entry_no_parent_options']      = 'Currently, there\'s no parent options.';
$_['pcop_entry_add_parent_option']      = 'Add parent option';
$_['pcop_entry_or']                     = 'OR';
$_['pcop_entry_remove_parent_option']   = 'Remove parent option';


// Entry
$_['entry_settings']                  = 'Settings';
$_['entry_disable_highlight']         = 'Disable highlight';
$_['entry_disable_highlight_help']    = 'Disable highlight (different colors) for parent-child options on the product edit page';


$_['error_xlsx_lib_is_not_found']     = '%s library is not found (it is necessary for import/export features only).';
$_['error_php_excel_is_necessary_for_xls']         = ' (PHPExcel is necessary for importing XLS) ';
//$_['entry_PHPExcel_not_found']        = '<a href="https://liveopencart.com/PHPExcel" target="_blank">PHPExcel</a> library is not found (it is not included to the module package, it should be installed additionally).<br> File not found: ';
$_['entry_export']                    = 'Export';
$_['entry_export_description']        = 'Export file format: XLSX.<br>First row for fields names, next rows for data.';
$_['entry_export_get_file']           = 'Export file';
$_['entry_export_check_all']          = 'Check all';
$_['entry_export_fields']             = 'Export fields:';
$_['entry_import']                    = 'Import';
$_['entry_import_ok']                 = 'Import completed';
$_['entry_import_description']        = '
Import file format: XLSX. Import uses only the first sheet for getting data.
<br>The first row should contain field names (header): product_id, option_id, parent_option_id, parent_option_values_ids, pcop_or.
<br>Next rows should contain data relevant to field names in the first table (ID\'s in the column \'parent_option_values_ids\' should be separated by ",").
';
$_['button_install_xlsx_lib']    	  = 'Click to install %s automatically';
$_['success_install_xlsx_lib']    	  = '%s is installed. Please reload the page.';
$_['button_upload']		              = 'Import file';
$_['button_upload_help']              = 'import starts immediately, when the file is selected';
$_['entry_server_response']           = 'Server answer:';
$_['entry_import_result']             = 'Processed products / options';
$_['entry_import_delete_yes']         = 'Delete the existing data (parent-child options) before importing';
$_['entry_import_delete_no']          = 'Append new data to the existing data (parent-child options)';
$_['entry_use_po_ids']          			= 'Use <b>product_option_id</b> and <b>product_option_value_id</b> instead of <b>option_id</b> and <b>option_value_id</b>';
$_['entry_use_po_ids_help']      			= 'Can be useful if the same option is used twice or more times per product
(it will not work if product_option_id or product_option_value_id are changed by any reason after exporting and before importing)';


$_['entry_m_version']                 = 'Parent-child Options, version';

$_['text_update_alert']               = '(a new version is available)';

$_['text_extension_page']             = '
<a href="https://isenselabs.com/products/view/parent-child-options-enhanced-opencart-product-options?pa=41075&#10;" target="_blank" title="Parent-child Options on isenselabs.com">Parent-child Options on isenselabs.com</a>
| <a href="https://www.opencart.com/index.php?route=marketplace/extension/info&amp;extension_id=31568" target="_blank" title="Parent-child Options on opencart.com">Parent-child Options on opencart.com</a>
';

$_['entry_about']               			= 'About';
$_['module_description']    					= '
The module is designed to show/hide child options (option groups) depending on selected values of their parent options.';

$_['text_conversation'] 							= 'We are open for conversation. If you need to modify or integrate our modules, to add new functionality or develop new modules, email as to <b><a href="mailto:support@liveopencart.com">support@liveopencart.com</a></b>.';

$_['entry_we_recommend'] 							= 'We also recommend:';
$_['text_we_recommend'] 							= '

';
$_['module_copyright'] 								= '"'.$_['module_name'].'". is a commercial extension. Resell or transfer it to other users is NOT ALLOWED.
<br>By purchasing this module, you get it for use on one site. 
If you want to use the module on multiple sites, you should purchase a separate copy for each site.
<br>Thank you for purchasing the module.
';

$_['error_modificaton']               = 'Warning: '.$_['module_name'].' modification (OCMOD) is not applied!';
$_['error_permission']                = 'Warning: You do not have permission to modify module!';
?>