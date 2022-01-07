//  Parent-child Options
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru

var pcop = {
	
	initialized : false,
	parent_option_cnt : 0,
	texts : {},
	settings: {},
	all_product_options : {},	// created by specific function of PCOP
	basic_product_options : {}, // created by standard product controller
	temp_id_cnt : 0,
	timers: {
		update_marks: 0,
	},
	option_tab_id_prefix: 'tab-option',
	option_tab_href_prefix: '#tab-option',

	colors: ['#8FEF9F', '#607196', '#babfd1', '#ffc759', '#ff7b9c', '#2F9FEF'], //, '#e8e9ed'
	//colors: ['red', 'orange', 'yellow', 'green', 'blue', 'indigo', 'violet'],

	init : function( product_option_cnt ) {
		
		let product_options = pcop.getAllOptions();
		
		for ( let product_option_num=0; product_option_num<product_option_cnt; product_option_num++ ) {
			if ( typeof(pcop.basic_product_options[product_option_num]) != 'undefined' && typeof(pcop.basic_product_options[product_option_num].pcop) != 'undefined' ) {
                pcop.showOptionSettings(product_option_num, pcop.basic_product_options[product_option_num].pcop, product_options);
			}
		}
		
		$('#option').on('click', 'a[href^="'+pcop.option_tab_href_prefix+'"]', function(){
			var tab_num = $(this).attr('href').substr( pcop.option_tab_href_prefix.length );
			
			pcop.updateParentSettingsForOption(tab_num);
		});
		
		$(document).on('change.pcop.liveopencart', '[data-pcop="parent-option"], [data-pcop="parent-option-value"]', function(){
			pcop.updateMarksDelayed();
		});
		
		$(document).on('click.pcop.liveopencart', '#option a[href^="'+pcop.option_tab_href_prefix+'"], button', function(){
			pcop.updateMarksDelayed();
		});
		
		
		
		$('#option').on('mouseover', 'a[href^="#tab-option"]', function(){
			let option_temp_id = $($(this).attr('href')).find('input[data-pcop="option-temp-id"]').val();
			pcop.displayChildsHightlights(option_temp_id);
			pcop.displayParentsHightlights(option_temp_id);
		});
		$('#option').on('mouseout', 'a[href^="#tab-option"]', function(){
			pcop.hideChildHightlights();
			pcop.hideParentHightlights();
		});
		
		//$('#option').on('mouseover', '[data-pcop="mark"]', function(){
		//	pcop.displayParentHightlight($(this).attr('data-pcop-parent-temp-id'));
		//});
		//$('#option').on('mouseout', '[data-pcop="mark"]', function(){
		//	pcop.hideParentHightlights();
		//});
		
		pcop.updateMarksDelayed();
	},
	
	each : function(collection, fn){
		for ( let i_item in collection ) {
			if ( !collection.hasOwnProperty(i_item) ) continue;
			if ( fn(collection[i_item], i_item) === false ) {
				return;
			}
		}
	},
	
	displayChildsHightlights: function(option_temp_id) {
		pcop.hideChildHightlights();
		$('select[data-pcop="parent-option"]').each(function(){
			if ( $(this).val() == option_temp_id ) {
				let child_option_anchor_id = $(this).closest('[id^="'+pcop.option_tab_id_prefix+'"]').attr('id');
				$('#option a[href="#'+child_option_anchor_id+'"]').addClass('pcop-highlight-child');
			}
		});
	},
	
	displayParentsHightlights: function(option_temp_id){
		pcop.hideParentHightlights();
		
		$('select[data-pcop="parent-option"][data-pcop-option-temp-id="'+option_temp_id+'"]').each(function(){
			let parent_option_temp_id = $(this).val();
			if ( parent_option_temp_id ) {
				let parent_option_anchor_id = $('input[data-pcop="option-temp-id"][value="'+parent_option_temp_id+'"]').closest('[id^="'+pcop.option_tab_id_prefix+'"]').attr('id');
				$('#option a[href="#'+parent_option_anchor_id+'"]').addClass('pcop-highlight-parent');
			}
		});
		
		//let parent_option_anchor_id = $('input[data-pcop="option-temp-id"][value="'+parent_temp_id+'"]').closest('[id^="'+pcop.option_tab_id_prefix+'"]').attr('id');
		//$('#option a[href="#'+parent_option_anchor_id+'"]').addClass('pcop-highlight-parent');
	},
	
	hideParentHightlights: function(){
		$('#option a[href^="#tab-option"]').removeClass('pcop-highlight-parent');
	},
	hideChildHightlights: function(){
		$('#option a[href^="#tab-option"]').removeClass('pcop-highlight-child');
	},
	
	getNewTempId : function() {
		pcop.temp_id_cnt++;
		return pcop.temp_id_cnt;
	},
	getMaxExistingTempId : function(){
		var max_temp_id = 0;
		$('#tab-option input[type="hidden"][name^="product_option["][name$="[product_option_value_temp_id]"]').each(function(){
			max_temp_id = Math.max(max_temp_id, (parseInt($(this).val()) || 0) );
		});
		return max_temp_id;
	},
	
	getAllOptions : function() {
		
		var product_options = [];
		//var pcop_temp_id_cnt = pcop.getMaxExistingTempId()+1;
		var option_tab_id_beginning = 'tab-option';
		$('#tab-option [id^="'+option_tab_id_beginning+'"]').each(function(){
			var $option_container = $(this);
			var tab_num = parseInt( $option_container.attr('id').substr( option_tab_id_beginning.length ) );
			if ( !isNaN(tab_num) ) {
				
				var product_option = {};
				
				var $po_id_input = $option_container.find('input[name="product_option['+tab_num+'][product_option_id]"]');
				
				product_option.product_option_id 			= $po_id_input.val();
				product_option.pcop_po_id 					= $option_container.find('input[name="product_option['+tab_num+'][pcop_po_id]"]').val();
				product_option.product_option_temp_id 		= $option_container.find('input[name="product_option['+tab_num+'][product_option_temp_id]"]').val();
				product_option.name 						= $option_container.find('input[name="product_option['+tab_num+'][name]"]').val();
				product_option.option_id 					= $option_container.find('input[name="product_option['+tab_num+'][option_id]"]').val();
				product_option.type 						= $option_container.find('input[name="product_option['+tab_num+'][type]"]').val();
				product_option.container_id					= $option_container.attr('id');
				
				// the module uses temp ids, add them id needed
				if ( !product_option.product_option_temp_id ) {
					product_option.product_option_temp_id = pcop.getNewTempId();
					var html_temp_id = '<input type="hidden" name="product_option['+tab_num+'][product_option_temp_id]" value="'+product_option.product_option_temp_id+'" data-pcop="option-temp-id">';
					$po_id_input.before(html_temp_id);
				}
				
				
				product_option.values = pcop.getAllOptionValuesFromContainer($option_container, tab_num);
				
				
				product_options.push(product_option);
			}
			
		});
		
		return product_options;
	},
	
	getPOVDetailsByRowElement: function($option_value_row, tab_num, row_num) {
		//let $inputs = $option_value_row.find('input, select');
					
		let value = {};
		let pov_id_input = $option_value_row[0].querySelector('input[name="product_option['+tab_num+'][product_option_value]['+row_num+'][product_option_value_id]"]');
		let $pov_select = $($option_value_row[0].querySelector('select[name="product_option['+tab_num+'][product_option_value]['+row_num+'][option_value_id]"]'));
		
		let temp_input = $option_value_row[0].querySelector('input[name="product_option['+tab_num+'][product_option_value]['+row_num+'][product_option_value_temp_id]"]');
		value.product_option_value_temp_id = temp_input ? temp_input.value : '';
		
		//value.product_option_value_id = $inputs.filter('input[name="product_option['+tab_num+'][product_option_value]['+row_num+'][product_option_value_temp_id]"]').val();
		let pcop_pov_id_input = $option_value_row[0].querySelector('input[name="product_option['+tab_num+'][product_option_value]['+row_num+'][pcop_pov_id]"]'); // for mass update
		value.pcop_pov_id = pcop_pov_id_input ? pcop_pov_id_input.value : '';
		
		value.product_option_value_id = pov_id_input.value;
		value.option_value_id = $pov_select.val();
		let select_option_elem = $pov_select.length ? $pov_select[0].querySelector('option[value="'+value.option_value_id+'"]') : '';
		value.name = select_option_elem ? select_option_elem.innerHTML : '';
		return value;
	},
	
	getAllOptionValuesFromContainer : function($option_container, tab_num) {
		
		let option_value_row_id_beginning = 'option-value-row';
		let $option_value_container = $option_container.find('#option-value'+tab_num);
		
		if ( $option_value_container.length ) {
			let values = [];
			$option_value_container.find('tbody:first').children('[id^="'+option_value_row_id_beginning+'"]').each(function(){
			//$option_value_container.find('[id^="'+option_value_row_id_beginning+'"]').each(function(){
				
				let $option_value_row = $(this);
				let row_num = parseInt( $option_value_row.attr('id').substr( option_value_row_id_beginning.length ) );
				if ( !isNaN(row_num) ) {
					
					let $pov_id_input = $option_value_row.find('input[name="product_option['+tab_num+'][product_option_value]['+row_num+'][product_option_value_id]"]');
					
					value = pcop.getPOVDetailsByRowElement($option_value_row, tab_num, row_num);
					
					// the module uses temp ids, add them id needed
					if ( !value.product_option_value_temp_id ) {
						value.product_option_value_temp_id = pcop.getNewTempId();
						
						// faster than jQuery
						let elem_temp_id = document.createElement('input');
						elem_temp_id.setAttribute('type', 'hidden');
						elem_temp_id.setAttribute('name', 'product_option['+tab_num+'][product_option_value]['+row_num+'][product_option_value_temp_id]');
						elem_temp_id.setAttribute('value', value.product_option_value_temp_id);
						
						$pov_id_input.parent()[0].appendChild(elem_temp_id);
						
					}
					
					values.push(value);
				}
				
			});
			
			return values;
		}
	},
	
	getParentSettingsForOption(product_option_block_num) {
		var parents_settings = [];
		
		var $parents_container = $('#pcop_parent_options_'+product_option_block_num);
		$parents_container.find('input[data-pcop-num]').each(function(){
			var pcop_num = $(this).attr('data-pcop-num');
			var pcop_name_prefix = 'product_option['+product_option_block_num+'][pcop]['+pcop_num+']';
			var parent_settings = {};
			
			parent_settings.pcop_id = $parents_container.find('input[name="'+pcop_name_prefix+'[pcop_id]"]').val();
			parent_settings.pcop_or = $parents_container.find(':checkbox[name="'+pcop_name_prefix+'[pcop_or]"]:checked').val();
			parent_settings.parent_product_option_temp_id = $parents_container.find('select[name="'+pcop_name_prefix+'[parent_product_option_temp_id]"]').val();
			
			var current_values = [];
			
			$parents_container.find(':checkbox[name^="'+pcop_name_prefix+'"][name$="[values][]"]:checked').each(function(){
				current_values.push( $(this).val() );
			});
			
			parent_settings.current_values = current_values;
			
			parents_settings.push(parent_settings);
		});
		
		return parents_settings;
	},
	
	updateParentSettingsForOption : function(product_option_block_num) {
		var parents_settings = pcop.getParentSettingsForOption(product_option_block_num);
		$('#pcop_parent_options_'+product_option_block_num).find('tbody').html('');
		for ( var i_parents_settings in parents_settings ) {
			if ( !parents_settings.hasOwnProperty(i_parents_settings) ) continue;
			var parent_settings = parents_settings[i_parents_settings];
			pcop.addParentOption(product_option_block_num, parent_settings);
		}
	},
	
	// add one more parent option rule
	addParentOption : function(product_option_block_num, settings, p_product_options) {
		
		let product_options = p_product_options ? p_product_options : pcop.getAllOptions();
		let $product_option_container = $('#tab-option'+product_option_block_num);
		let pcop_table = $('#pcop_parent_options_'+product_option_block_num);
		let current_product_option_temp_id = $product_option_container.find('input[name="product_option['+product_option_block_num+'][product_option_temp_id]"]').val();
		
		let html = '';
		html += '<tr>';
		html += '<td>';
		html += '<div class="col-sm-4">';
		html += '<input type="hidden" data-pcop-num="'+pcop.parent_option_cnt+'">';
		html += '<input type="hidden" name="product_option['+product_option_block_num+'][pcop]['+pcop.parent_option_cnt+'][pcop_id]" value="'+(settings ? settings.pcop_id : '')+'">';
		html += '<select name="product_option['+product_option_block_num+'][pcop]['+pcop.parent_option_cnt+'][parent_product_option_temp_id]" id="parent_option_'+pcop.parent_option_cnt+'" class="form-control" data-pcop="parent-option" data-pcop-option-temp-id="'+current_product_option_temp_id+'" onchange="pcop.showParentOptionValues('+pcop.parent_option_cnt+')">';
		html += '<option value="">-</option>';
		
		
		
		if (product_options) {
			for (var i_product_options in product_options) {
				if ( !product_options.hasOwnProperty(i_product_options) ) continue;
				var product_option = product_options[i_product_options];
				
				if ( current_product_option_temp_id && current_product_option_temp_id == product_option.product_option_temp_id ) {
					continue;
				}
				
				if ( $.inArray(product_option.type, ['select','radio','image','checkbox','block','color','prodoptcolsizeimg_color','prodoptcolsizeimg_size']) != -1 ) {
					html += '<option value="'+product_option.product_option_temp_id+'"';
					if ( settings && typeof(settings.parent_product_option_id) != 'undefined' && product_option.product_option_id==settings.parent_product_option_id) {
						html += 'selected'; // for init load
					} else if ( settings && typeof(settings.parent_product_option_temp_id) != 'undefined' && product_option.product_option_temp_id==settings.parent_product_option_temp_id) {
						html += 'selected'; // for update
					} else if ( settings && typeof(settings.parent_product_option_id) != 'undefined' && product_option.pcop_po_id==settings.parent_product_option_id) {
						html += 'selected'; // for massupd load
					}
					html += '>'+product_option.name+'</option>';
				}
			}
		}
		
		html += '</select>';
		html += '</div>';
		html += '<div class="col-sm-4">';
		html += '<div class="well well-sm" style="height: 150px; overflow: auto; margin-bottom:0px;" id="parent_option_values_'+pcop.parent_option_cnt+'">';
		html += '';
		html += '';
		html += '</div>';
		html += '</div>';
		
		html += '<label class="col-sm-4">';
		html += '<input type="checkbox" data-pcop="or" name="product_option['+product_option_block_num+'][pcop]['+pcop.parent_option_cnt+'][pcop_or]" value="1" '+(settings && settings.pcop_or && settings.pcop_or!='0' ? 'checked' : '')+'>';
		html += pcop.texts.pcop_entry_or;
		html += '</label>';
		
		html += '</td>';
		
		html += '<td>';
		html += '<button type="button" onclick="$(this).closest(\'tr\').remove();pcop.checkTextNoParentOptions('+product_option_block_num+');" data-toggle="tooltip" class="btn btn-danger"';
		html += 'title="'+pcop.texts.pcop_entry_remove_parent_option+'"><i class="fa fa-minus-circle"></i></button>';
		html += '</td>';
		html += '</tr>';
		
		pcop_table.find('tbody').append(html);
		
		pcop.showParentOptionValues(pcop.parent_option_cnt, settings, product_options );
		
		pcop.parent_option_cnt++;
		pcop.checkTextNoParentOptions(product_option_block_num);
		
	},
	
	// refresh list of values for parent option
	// pcop_parent_option_cnt - number of parent option block
	// settings - values that should be selected
	// set_parent_option_id - set parent option id to this value
	showParentOptionValues : function(parent_option_num, settings, p_product_options) {
	
		var product_options = p_product_options ? p_product_options : pcop.getAllOptions();
	
		var parent_option_select = $('#parent_option_'+parent_option_num);
		var parent_product_option_temp_id = parent_option_select.val();
		
		var block_number = pcop.getProductOptionBlockNumberFromName( $('#parent_option_'+parent_option_num).attr('name') );

		if ( block_number === false ) {
			return;
		}
		var html = '';

		if (parent_product_option_temp_id) {
			for (var i_product_options in product_options) {
				if ( !product_options.hasOwnProperty(i_product_options) ) continue;
				
				var product_option = product_options[i_product_options];
				
				if (product_option.product_option_temp_id == parent_product_option_temp_id && product_option.values) {
					for (var i_values in product_option.values) {
						if ( !product_option.values.hasOwnProperty(i_values) ) continue;
					
						var pcop_pov = product_option.values[i_values];
					
						html += '<div class="checkbox"><label>';
						html += '<input type="checkbox" data-pcop="parent-option-value" name="product_option['+block_number+'][pcop]['+parent_option_num+'][values][]" ';
						
						if ( settings && typeof(settings.values) != 'undefined' ) { // pcop settings are set
							if ( settings.parent_product_option_temp_id && pcop_pov.product_option_value_temp_id ) { // return from unsuccessful attempt to save
								if ( $.inArray(pcop_pov.product_option_value_temp_id, settings.values) != -1 || $.inArray(pcop_pov.product_option_value_temp_id.toString(), settings.values) != -1 ) {
									html += ' checked ';
								}
							} else { // from saved data
								if ( pcop_pov.product_option_value_id && $.inArray(pcop_pov.product_option_value_id, settings.values) != -1 ) {
									html += ' checked ';
								} else if ( pcop_pov.pcop_pov_id && $.inArray(pcop_pov.pcop_pov_id, settings.values) != -1 ) { // massupd
									html += ' checked ';
								}
							}
						} else if ( settings && typeof(settings.current_values) != 'undefined' ) { // from replaced content (for update)
							if ( $.inArray(pcop_pov.product_option_value_temp_id, settings.current_values) != -1 ) {
								html += ' checked ';
							}
						}
						html += ' value="'+pcop_pov.product_option_value_temp_id+'">';
						html += pcop_pov.name;
						html += '</label></div>';
					}
				}
			}
		}
		
		$('#parent_option_values_'+parent_option_num).html(html);
		
	},
	
	
	getNewRandomColor: function(){
		let letters = '0123456789ABCDEF';
		let color = '#';
		for (let i = 0; i < 3; i++) {
		  color += letters[1+Math.floor(Math.random() * 15)]+'F';
		}
		pcop.colors.push(color);
		return color;
	},
	
	updateMarksDelayed: function() {
		clearTimeout(pcop.timers.update_marks);
		pcop.timers.update_marks = setTimeout(function(){
			pcop.updateMarks();
		}, 100);
	},
	
	updateMarks: function(){
		
		if ( pcop.settings.disable_highlight ) return;
		
		pcop.hideChildHightlights();
		pcop.hideParentHightlights();
		
		let used_parents_temp_ids = [];
		$('select[data-pcop="parent-option"]').each(function(){
			if ( $(this).val() ) {
				used_parents_temp_ids.push($(this).val());
			}
		});
		
		
		let options = [];
		let used_colors = 0;
		pcop.each(pcop.getAllOptions(), function(option){
			if ( $.inArray(option.product_option_temp_id, used_parents_temp_ids) != -1 ) {
				if ( pcop.colors[used_colors] ) {
					option.color = pcop.colors[used_colors];
				} else {
					option.color = pcop.getNewRandomColor();
				}
				used_colors++;
			}
			option.parents_temp_ids = [];
			$('#'+option.container_id+' select[data-pcop="parent-option"]').each(function(){
				if ( $(this).val() ) {
					option.parents_temp_ids.push($(this).val());
				}
			});
			options.push(option);
		});
		
		$('#option').find('[data-pcop="marks-container"]').remove();
		pcop.each(options, function(option){ 
			let html = '';
			html+= '<div class="pcop-marks-container" data-pcop="marks-container" ';
			if ( option.color ) { // parent mark displaing the option color
				html+= ' style="background-color: '+option.color+'" ';
			}
			html+= '>';
			pcop.each(options, function(parent){ // use the specific order of options
				if ( $.inArray(parent.product_option_temp_id, option.parents_temp_ids) != -1 ) {
					html+= '<span class="pcop-mark" data-pcop="mark" style="background-color: '+parent.color+'" title="'+parent.name+'" data-pcop-parent-temp-id="'+parent.product_option_temp_id+'"></span>';
				}
			}); 
			html+= '</div>';
			$('#option a[href="#'+option.container_id+'"]').append(html);
		});
		
		// add marks update for removing options
		$('#option a[href^="'+pcop.option_tab_href_prefix+'"] [onclick*="remove"]:not([onclick*="updateMarks"])').each(function(){
			$(this).attr('onclick', $(this).attr('onclick')+';pcop.updateMarksDelayed();');
		});
		
	},

	// show parent options settings for product option
	showOptionSettings : function(product_option_block_num, pcop_data, p_product_options) {
		
		var html = '';
		
		html += '<div class="form-group">';
		html += '<label class="col-sm-2 control-label">'+pcop.texts.pcop_entry_settings+'</label>';
		html += '<div class="col-sm-10">';
		html += '<table id="pcop_parent_options_'+product_option_block_num+'" class="table table-striped table-bordered table-hover">';
		html += '<tbody></tbody>';
		html += '<tfoot><td width="100%"><div id="text_no_parent_options_'+product_option_block_num+'">'+pcop.texts.pcop_entry_no_parent_options+'</font></td>';
		html += '<td>';
		html += '<button type="button" onclick="pcop.addParentOption('+product_option_block_num+')" data-toggle="tooltip" class="btn btn-primary"';
		html += 'title="'+pcop.texts.pcop_entry_add_parent_option+'"><i class="fa fa-plus-circle"></i></button>';
		html += '</td></tfoot>';
		html += '</div>';
		html += '</div>';
    
        
		// show after "required" block
		$('#tab-option'+product_option_block_num+' div.form-group:first').after(html);
		
		//let _colors = ['red', 'orange', 'yellow', 'green', 'blue', 'indigo', 'violet'];
		
		if (pcop_data) {
			let product_options = p_product_options || pcop.getAllOptions();
			for (let i in pcop_data) {
				if ( !pcop_data.hasOwnProperty(i) ) continue;
                pcop.addParentOption(product_option_block_num, pcop_data[i], product_options);
			}
		}
	
	},
	
	// get product option block number from name product_option[X]...
	getProductOptionBlockNumberFromName : function(name) {
		var str = name.substring(15);
		str = str.substring(0, str.indexOf(']'));
		return str;
	},
	
	// get product option block number by product option id
	getProductOptionBlockNumberByProductOptionId : function(product_option_id) {
		var name = $('#tab-option input[type="hidden"][name^="product_option["][name*="[product_option_id]"][value="'+product_option_id+'"]').attr('name');
		if ( name ) {
			return pcop.getProductOptionBlockNumberFromName( name );
		} else {
			return false;
		}
	},
	
	
	// show text if there's no parent options, hide is there's some parent options
	checkTextNoParentOptions : function(product_option_block_num) {
		
		var has_parent_options = $('#pcop_parent_options_'+product_option_block_num+' tbody tr').length;
		$('#text_no_parent_options_'+product_option_block_num).toggle(!has_parent_options);
		
	},
}