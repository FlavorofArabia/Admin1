//  Parent-child Options
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru

function initNewPcopFront(p_pcop_data, p_pcop_theme_name, $p_custom_option_container) {

	let pcop_front = {
	
		data : {},
		option_prefix : 'option',
		theme_name : '',
		$custom_option_container: false,
		$stored_option_container: false,
		$stored_not_required_options: false,
		timer_delayed_first_option_change_call : 0,
		html_default_values : [],
		initialized : false,
		original_methods: {},
		events: {},
		works: {
			checkVisibility: false,
		},
		timers: {
			checkVisibility: false,
		},
		
		each : function(collection, fn){
			for ( var i_item in collection ) {
				if ( !collection.hasOwnProperty(i_item) ) continue;
				if ( fn(collection[i_item], i_item) === false ) {
					return;
				}
			}
		},
		
		bind: function(event_key, fn) {
			if ( typeof(pcop_front.events[event_key]) == 'undefined' ) {
				pcop_front.events[event_key] = [];
			}
			pcop_front.events[event_key].push(fn);
		},
		
		trigger: function(event_key){
			let args = Array.from(arguments);
			
			args.splice(0, 1);
			if ( typeof(pcop_front.events[event_key]) != 'undefined' ) {
				pcop_front.each(pcop_front.events[event_key], function(fn){
					fn.apply(pcop_front, args);
				});
			}
		},
		
		replaceOriginalMethod : function(method_name, fn) {
			pcop_front.original_methods[method_name] = pcop_front[method_name];
			pcop_front[method_name] = fn;
		},	
		
		init : function(p_pcop_data, p_pcop_theme_name, $p_custom_option_container) {
			
			if ( pcop_front.initialized ) {
				return;
			}
			
			if ( p_pcop_data ) {
				pcop_front.data = p_pcop_data;
			}
			if ( p_pcop_theme_name ) {
				pcop_front.theme_name = p_pcop_theme_name;
			}
			if ( $p_custom_option_container ) {
				pcop_front.$custom_option_container = $p_custom_option_container;
			}
			
			// joocart (theme name should be set on this stage to make getOptionElement working properly )
			if ( !pcop_front.getOptionElement('select, input').filter('[name^="'+pcop_front.option_prefix+'["]').length && pcop_front.getOptionElement('select, input').filter('[name^="option_oc["]').length ) {
				pcop_front.option_prefix = 'option_oc';
			}
			
			pcop_front.getOptionElement('[id-option_color]').click(function(){ // comp with color-option
				setTimeout(function(){
					pcop_front.checkVisibility();
				}, 1);
			});
			
			pcop_front.updateDefaultValues();
		
			if ( (pcop_front.data && Object.keys(pcop_front.data).length) || !pcop_front.theme_name ) { // !pcop_theme_name - admin section
				pcop_front.getOptionContainer().on('change', pcop_front.getSelectorOfOptions(), function(){
					pcop_front.checkVisibility();
				});
			}
			
			if (pcop_front.data && Object.keys(pcop_front.data).length) {
				pcop_front.checkVisibility();
				$(document).ready(function(){ // additionally for ready (sometimes options can be changed by theme script after page loading)
					pcop_front.checkVisibility();
					
					setTimeout(function(){
						let selected_parent_options = pcop_front.getSelectedParentOptions();
						if ( Object.keys(selected_parent_options).length ) {
							pcop_front.checkVisibility();
						}
					}, 1);
				});
			}
			
			//// check for a value selected by default
			//$().ready(function(){
			//	setTimeout(function(){
			//		let selected_parent_options = pcop_front.getSelectedParentOptions();
			//		if ( Object.keys(selected_parent_options).length ) {
			//			pcop_front.checkVisibility();
			//		}
			//	}, 1);
			//});
			
			pcop_front.getOptionContainer().data('liveopencart_pcop_front', pcop_front);
			
			pcop_front.initialized = true;

		},
		
		getOptionElement : function(selector){
			return pcop_front.getOptionContainer().find(selector);
		},
		
		// in some places (where all selecting elemens should have names) for better performance it is better to filter from the previously prepared list of elements
		getOptionElementNamedCached : function(selector){
			pcop_front.updateOptionElementsNamedCached(false);
			return pcop_front.$option_elements_named.filter(selector);
		},
		
		updateOptionElementsNamedCached : function(forced_update){
			if ( forced_update || !pcop_front.$option_elements_named ) {
				pcop_front.$option_elements_named = pcop_front.getOptionElement('[name]');
			}
		},
		
		getSelectedOptions : function() {
			let options = {};
			pcop_front.getOptionElement(pcop_front.getSelectorOfOptions()).each(function(){
				let po_id = pcop_front.getProductOptionIdFromName( $(this).attr('name') );
				if ( $(this).is('select') ) {
					if ( $(this).val() ) {
						if ( !options[po_id] ) {
							options[po_id] = [];
						}
						options[po_id].push($(this).val());
					}
				} else if ( $(this).is(':checked') ) {
					if ( !options[po_id] ) {
						options[po_id] = [];
					}
					options[po_id].push($(this).val());
				}
			});
			return options;
		},
		
		getSelectedParentOptions : function() {
			let options = pcop_front.getSelectedOptions();
			let all_parent_options = pcop_front.getParentOptions();
			let selected_parent_options = {};
			pcop_front.each(options, function(pov_ids, po_id){
				if ( $.inArray(po_id, all_parent_options) != -1 ) {
					selected_parent_options[po_id] = pov_ids;
				}
			});
			return selected_parent_options;
		},
		
		getSelectorOfOptions : function() {
			return 'input:checkbox[name^="'+pcop_front.option_prefix+'"], input:radio[name^="'+pcop_front.option_prefix+'"], select[name^="'+pcop_front.option_prefix+'"]';
		},
		
		getParentOptions : function(){
			let parent_options = [];
			pcop_front.each(pcop_front.data, function(dt){
				pcop_front.each(dt, function(dt_parent){
					if ( $.inArray(dt_parent.parent_product_option_id, parent_options) == -1 ) {
						parent_options.push(dt_parent.parent_product_option_id);
					}
				});
			});
			return parent_options;
		},
		
		// option[XXX]
		// pcop_front.getProductOptionIdFromName
		getProductOptionIdFromName : function (name) {
			
			var prefix_quantity_per_option = 'quantity_per_option';
			var str = '';
			if ( name.substr(0, prefix_quantity_per_option.length) == prefix_quantity_per_option ) {
				str = name.substr( prefix_quantity_per_option.length + 1 );	
			} else {
				str = name.substr( pcop_front.option_prefix.length + 1 );
			}
			var bracket_pos = str.indexOf(']');
			if (bracket_pos != -1) {
				return str.substr(0,bracket_pos);
			}
		},
		
		// pcop_front.parentValueIsSelected
		parentValueIsSelected : function (parent_product_option_id, parent_option_values) {
		
			let result = false;
			
			let basic_name = ''+pcop_front.option_prefix+'['+parent_product_option_id+']';
			let $parent_option_selected_elements = pcop_front.getOptionElementNamedCached('[name^="'+basic_name+'"]').filter('[name="'+basic_name+'[]"]:checkbox:checked, [name="'+basic_name+'"]:radio:checked, [name^="'+basic_name+'"]select, [name^="'+basic_name+'"]input[type=hidden]');  
			
			//pcop_front.getOptionElement(':checkbox[name="'+pcop_front.option_prefix+'['+parent_product_option_id+'][]"]:checked, :radio[name="'+pcop_front.option_prefix+'['+parent_product_option_id+']"]:checked, select[name^="'+pcop_front.option_prefix+'['+parent_product_option_id+']"], input[type=hidden][name^="'+pcop_front.option_prefix+'['+parent_product_option_id+']"]')
			$parent_option_selected_elements.each(function(){
				if ( $(this).val() && $.inArray($(this).val(), parent_option_values) != -1 ) {
					result = true;
					return false; // stop the loop
				}
												
			});
												
			return result;
		},
		
		// pcop_front.delayedFirstOptionChangeTrigger
		delayedFirstOptionChangeTrigger : function (start_event) {
			
			clearTimeout(pcop_front.delayedFirstOptionChangeTrigger_timer);
			if (start_event) {
				pcop_front.getOptionElement('[name^="'+pcop_front.option_prefix+'"]:first').change();
			} else {
				pcop_front.delayedFirstOptionChangeTrigger_timer = setTimeout(function(){
					pcop_front.delayedFirstOptionChangeTrigger(true);
				}, 200);
			}
			
		},
		
		getCodePcopInputNotRequired : function() {
			return '<input type="hidden" name="options_pcop_not_required" value="">';
		},
		
		getElementPcopInputNotRequired : function() {
			
			if ( !pcop_front.$stored_not_required_options || !pcop_front.$stored_not_required_options.length ) {
				if ( pcop_front.getOptionContainer().find('#product').length ) {
					pcop_front.getOptionContainer().find('#product').append( pcop_front.getCodePcopInputNotRequired() );
					pcop_front.$stored_not_required_options = pcop_front.getOptionContainer().find('#product').find('[name="options_pcop_not_required"]');
				} else {
					pcop_front.getOptionContainer().append( pcop_front.getCodePcopInputNotRequired() );
					pcop_front.$stored_not_required_options = pcop_front.getOptionContainer().find('[name="options_pcop_not_required"]');
				}
			}
			
			return pcop_front.$stored_not_required_options;
		},
		
		getStoredNotRequiredPOIds : function() {
			return pcop_front.getElementPcopInputNotRequired().val().split(',');
		},
		
		setStoredNotRequiredPOIds : function(pov_ids) {
			pcop_front.getElementPcopInputNotRequired().val( pov_ids.toString() );
			
			if ( pcop_front.getElementPcopInputNotRequired().val() == "NaN" ) {
				pcop_front.getElementPcopInputNotRequired().attr('value', pov_ids.toString());
			}
		},
		
		unselectValuesOfHiddenOptions : function($option_elements, option_name){
			let values_changed = false;
			if ( $option_elements.filter('select[name^="'+option_name+'"]').val() ) {
				$option_elements.filter('select[name^="'+option_name+'"]').val('');
				$option_elements.filter('select[name^="'+option_name+'"]').prop('value', '');// needed sometimes
				values_changed = true;
			}
			if ( $option_elements.filter(':checkbox[name^="'+option_name+'"]:checked').length ) {
				$option_elements.filter(':checkbox[name^="'+option_name+'"]:checked').prop('checked', false);
				values_changed = true;
			}
			if ( $option_elements.filter(':radio[name^="'+option_name+'"]:checked').length ) {
				$option_elements.filter(':radio[name^="'+option_name+'"]:checked').prop('checked', false);
				values_changed = true;
			}
			return values_changed;
		},
		
		
		getOptionElementContainer: function($option_element) {
			return $option_element.closest('div.form-group, table.form-group');
		},
		
		// pcop_front.changeOptionVisibility
		changeOptionVisibility : function (product_option_id, option_toggle) {
			
			let option_name = pcop_front.option_prefix+'['+product_option_id+']';
			let $option_elements = pcop_front.getOptionElementNamedCached('[name^="'+option_name+'"]');
			let $option_container = pcop_front.getOptionElementContainer($option_elements);
			let option_was_visible = $option_container.is(':visible');
		
			$option_container.toggle(option_toggle);
			if ( $option_container.next().is('br') ) {
				$option_container.next().toggle(option_toggle);
			}
			
			pcop_front.getOptionElementNamedCached('[name^="quantity_per_option['+product_option_id+']"]').closest('div.form-group').toggle(option_toggle);
			
			var values_changed = false;
			
			if ( option_toggle ) { // visible
				
				if ( !option_was_visible ) { // became  visible
				
					// default values (specific for text fields)
					let text_element = $(':text[name="'+option_name+'"]');
					if ( text_element.length && !text_element.val() && pcop_front.html_default_values && pcop_front.html_default_values[product_option_id] ) {
						text_element.val(pcop_front.html_default_values[product_option_id]);
						// direct evant call for text field
						pcop_front.delayedFirstOptionChangeTrigger();
						//$('input[type="text"][name="'+option_name+'"]').change();
					}
					
					if ( typeof(qpo_resetQuantities) == 'function' ) {
						qpo_resetQuantities(2, product_option_id); //reset to defaults
					}
					
				}
				
			} else { // hidden
				
				values_changed = pcop_front.unselectValuesOfHiddenOptions($option_elements, option_name);
				
				var $elem_other_option = pcop_front.getOptionContainer().find('input:not(:radio):not(:checkbox)[name^="'+option_name+'"]');
				if ( $elem_other_option.length && $elem_other_option.val() ) {
				
					var val_before = $elem_other_option.val();
	
					if ( $elem_other_option.is('[type="hidden"]') && pcop_front.getOptionContainer().find('input[name="option-quantity['+val_before+'][]"]').length ) {
						// comp with a kind of 'option with quantity' module
						pcop_front.getOptionContainer().find('input[name="option-quantity['+val_before+'][]"]').val(''); 
					} else {
						$elem_other_option.val('');
						if ( $elem_other_option.val() ) {
							// to fix a bug with not working "val()"
							$elem_other_option.prop('value', '');
						}
						
						// Product Image Option DropDown compatibility
						if ( $elem_other_option.closest('.select').length && $.fn.ddslick && $elem_other_option.closest('.select').data('ddslick') ) {
							var poidd_onSelected = $elem_other_option.closest('.select').data('ddslick').settings.onSelected;
							$elem_other_option.closest('.select').data('ddslick').settings.onSelected = ''; // to not call event 
							$elem_other_option.closest('.select').ddslick('select', {index: '0' });
							$elem_other_option.closest('.select').data('ddslick').settings.onSelected = poidd_onSelected;
						}
						
						// direct event call for text field
						if ( val_before ) {
							pcop_front.delayedFirstOptionChangeTrigger();
							//$('input[type="text"][name="'+option_name+'"]').change();
						}
					}
					
				}
				
				let $textarea_element = pcop_front.getOptionElementNamedCached('textarea[name^="'+option_name+'"]');
				if ( $textarea_element.length && $textarea_element.val() ) {
					$textarea_element.val('');
				}
				
				if ( typeof(qpo_resetQuantities) == 'function' ) {
					qpo_resetQuantities(-1, product_option_id); //reset to empty
				}
				//pcop_front.getOptionElement('[name^="quantity_per_option['+product_option_id+']"]').val('');
				
			}
			
			// << hided is not required
			
			let current_opts = pcop_front.getStoredNotRequiredPOIds();
			
			var new_opts = [];
			pcop_front.each(current_opts, function(current_opt){
			//for (var i in current_opts) {
				//if ( !current_opts.hasOwnProperty(i) ) continue;
				if ( current_opt != product_option_id) {
					new_opts.push(current_opt);
				}
			});
			
			if (!option_toggle ) {
				new_opts.push(product_option_id);
			}
			
			pcop_front.setStoredNotRequiredPOIds(new_opts);
			
			// >> hided is not required
			
			if ( !option_was_visible && option_toggle ) { // became visible
				
				let $option_elem = pcop_front.getOptionElement('[name^="'+option_name+'"]');
				
				// use html defaults
				if ( pcop_front.html_default_values && typeof(pcop_front.html_default_values[product_option_id]) != 'undefined' && pcop_front.html_default_values[product_option_id] ) {
					
					if ( $option_elem.length ) {
						if ( $option_elem.is('select') || $option_elem.is('textarea') ) {
							$option_elem.val(pcop_front.html_default_values[product_option_id]);
						} else if ( $option_elem.is('radio') ) {
							$option_elem.find('[value="'+pcop_front.html_default_values[product_option_id]+'"]').prop('checked', true);
						} else if ( $option_elem.is('checkbox') ) {
							if ( pcop_front.html_default_values[product_option_id].length ) { // should be array
								$option_elem.each(function(){
									if ( $.inArray($(this).val(), pcop_front.html_default_values[product_option_id]) ) {
										$(this).prop('checked', true);
									}
								});
							}
						}
					}
				}
				
				// improved options default selected values compatibility
				if ( typeof(improvedoptions_set_defaults) == 'function' ) {
					improvedoptions_set_defaults(false, product_option_id);
				}
				$option_elem.trigger('io_onOptionShow', product_option_id);
			}
			
			return values_changed;
		},
		
		// pcop_front.checkVisibility
		checkVisibility : function () {
			
			clearTimeout(pcop_front.timers.checkVisibility);
			if ( pcop_front.works.checkVisibility ) {
				pcop_front.timers.checkVisibility = setTimeout(function(){
					pcop_front.checkVisibility();
				}, 50);
			}
			
			pcop_front.works.checkVisibility = true;
			
			let product_option_ids_visible = false;
			
			if (pcop_front.data && Object.keys(pcop_front.data).length) {
				
				product_option_ids_visible = [];
				
				pcop_front.updateOptionElementsNamedCached();
				
				var product_options_ids = [];
				pcop_front.getOptionElement('input[name^="'+pcop_front.option_prefix+'["], textarea[name^="'+pcop_front.option_prefix+'["], select[name^="'+pcop_front.option_prefix+'["], input[type="hidden"][name^="copu_product_id"], select[name^="quantity_per_option["], input[name^="quantity_per_option["]').each(function(){
					var product_option_id = pcop_front.getProductOptionIdFromName($(this).attr('name'));
					if ( $.inArray(product_option_id, product_options_ids) == -1 ) {
						product_options_ids.push(product_option_id);
					}
				});
				
				pcop_front.each(product_options_ids, function(product_option_id){
				
					if (pcop_front.data[product_option_id]) {
						
						var pcop_rules = pcop_front.data[product_option_id];
						
						if (pcop_rules && pcop_rules.length) {
							
							var option_toggle = true;
							var option_or_toggle = false;
							var parents_or_cnt = 0;
							
							pcop_front.each(pcop_rules, function(pcop_rule){
								
								var parent_product_option_id = pcop_rule.parent_product_option_id;
								var parent_option_values = pcop_rule.values;
								
								var parent_result = pcop_front.parentValueIsSelected(parent_product_option_id, parent_option_values);
								if ( pcop_rule.pcop_or && pcop_rule.pcop_or == '1' ) { 
									option_or_toggle = option_or_toggle || parent_result;
									parents_or_cnt++;
								} else {
									option_toggle = option_toggle && parent_result;
								}
								
							});
							
							// all standard parents rules results should be TRUE and one of _OR_ parents results should be true
							option_toggle = option_toggle && (option_or_toggle || !parents_or_cnt);
							if ( option_toggle ) {
								product_option_ids_visible.push(product_option_id);
							}
							
							if ( pcop_front.changeOptionVisibility(product_option_id, option_toggle) ) {
								// if the option became hidden and it had a selected value before, launch the procedure from the beginning again
								pcop_front.getOptionElement('[name^="'+pcop_front.option_prefix+'['+product_option_id+']"]:first').change();
								//pcop_front.checkVisibility(); 
								return;
							}
							
						}
					}
				});
				
			}
			
			pcop_front.trigger('checkVisibility_after', product_option_ids_visible);
			
			pcop_front.works.checkVisibility = false;
			
			//return product_option_ids_visible;
		},
		
		// pcop_front.getOptionContainer
		getOptionContainer : function () {
			if ( !pcop_front.$stored_option_container ) {
				if ( !pcop_front.theme_name ) { // admin section 
					pcop_front.$stored_option_container = $('#option');
				} else if ( pcop_front.$custom_option_container ) {
					pcop_front.$stored_option_container = pcop_front.$custom_option_container;
				} else {
					pcop_front.$stored_option_container = pcop_front.getOptionContainerDefault();
				}
			}
			return pcop_front.$stored_option_container;
		},
		
		getOptionContainerDefault: function() {
			return $('#product, section[id="content"], .boss-product, #content').first();
		},
		
		// pcop_update_data
		updateData : function (product_options) {
			pcop_front.data = {};
			if ( product_options && product_options != {} && product_options != [] ) {
				for (var i_product_options in product_options) {
					if ( !product_options.hasOwnProperty(i_product_options) ) continue;
					var product_option = product_options[i_product_options];
					if ( product_option.pcop_front ) {
						pcop_front.data[product_option.product_option_id] =  product_option.pcop_front;
					}
				}
			}
		},
		
		// pcop_update_default_values
		updateDefaultValues : function () {
			pcop_front.html_default_values = [];
			pcop_front.getOptionElement('[name^="option["]').each(function(){
				var option_elem = $(this);
				var product_option_id = pcop_front.getProductOptionIdFromName(option_elem.attr('name'));
				if ( option_elem.is('select,textarea,:text') ) {
					if ( $(this).val() ) {
						pcop_front.html_default_values[product_option_id] = $(this).val();
					}
				} else if ( option_elem.is(':radio:checked') ) {
					pcop_front.html_default_values[product_option_id] = $(this).val();
				} else if ( option_elem.is(':checkbox:checked') ) {
					if ( typeof(pcop_front.html_default_values[product_option_id]) == 'undefined' ) {
						pcop_front.html_default_values[product_option_id] = [];
					}
					pcop_front.html_default_values[product_option_id].push(option_elem.val());
				}
			});
		},
	};
	
	if ( typeof(replaceOriginalPcopMethods) == 'function' ) {
		replaceOriginalPcopMethods(pcop_front);
	}
	pcop_front.init(p_pcop_data, p_pcop_theme_name, $p_custom_option_container);
	return pcop_front;
	
}