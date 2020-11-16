var ulp_new_layer_id = 0;
var ulp_active_layer = "";
var ulp_wordfence_whitelist_attempts = 0;
var ulp_updating_layer_details = false;
var ulp_saving = false;
function ulp_cookies_reset(_button) {
	if (ulp_saving) return false;
	var button_object = _button;
	jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(button_object).addClass("ulp-button-disabled");
	ulp_saving = true;
	var post_data = {"action" : "ulp-cookies-reset"};
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: post_data,
		success	: function(return_data) {
			jQuery(button_object).find("i").attr("class", "fas fa-times");
			jQuery(button_object).removeClass("ulp-button-disabled");
			try {
				var data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					ulp_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					ulp_global_message_show("danger", data.message);
				} else {
					ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
				}
			} catch(error) {
				ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
			}
			ulp_saving = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(button_object).find("i").attr("class", "fas fa-times");
			jQuery(button_object).removeClass("ulp-button-disabled");
			ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
			ulp_saving = false;
		}
	});
	return false;
}

function ulp_save_campaign(_button) {
	if (ulp_saving) return false;
	var button_object = _button;
	jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(button_object).addClass("ulp-button-disabled");
	jQuery(".ulp-campaign-form").find(".ulp-message").slideUp(350);
	
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: jQuery(".ulp-campaign-form").serialize(),
		success	: function(return_data) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("ulp-button-disabled");
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery("#ulp-campaign-id").val(data.id);
					ulp_global_message_show('success', data.message);
				} else if (data.status == "ERROR") {
					jQuery(".ulp-campaign-form").find(".ulp-message").html(data.message);
					jQuery(".ulp-campaign-form").find(".ulp-message").slideDown(350);
				} else {
					jQuery(".ulp-campaign-form").find(".ulp-message").html("Something went wrong. We got unexpected server response.");
					jQuery(".ulp-campaign-form").find(".ulp-message").slideDown(350);
				}
			} catch(error) {
				jQuery(".ulp-campaign-form").find(".ulp-message").html("Something went wrong. We got unexpected server response.");
				jQuery(".ulp-campaign-form").find(".ulp-message").slideDown(350);
			}
			ulp_saving = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("ulp-button-disabled");
			jQuery(".ulp-campaign-form").find(".ulp-message").html("Something went wrong. We got unexpected server response.");
			jQuery(".ulp-campaign-form").find(".ulp-message").slideDown(350);
			ulp_saving = false;
		}
		
	});
	return false;
}
function ulp_save_settings(_button) {
	if (ulp_saving) return false;
	var button_object = _button;
	jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(button_object).addClass("ulp-button-disabled");
	jQuery(".ulp-popup-form").find(".ulp-message").slideUp(350);
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: jQuery(".ulp-popup-form").serialize(),
		success	: function(return_data) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("ulp-button-disabled");
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					ulp_global_message_show('success', data.message);
				} else if (data.status == "ERROR") {
					jQuery(".ulp-popup-form").find(".ulp-message").html(data.message);
					jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
				} else {
					jQuery(".ulp-popup-form").find(".ulp-message").html("Something went wrong. We got unexpected server response.");
					jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
				}
			} catch(error) {
				jQuery(".ulp-popup-form").find(".ulp-message").html("Something went wrong. We got unexpected server response.");
				jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
			}
			ulp_saving = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("ulp-button-disabled");
			jQuery(".ulp-popup-form").find(".ulp-message").html("Something went wrong. We got unexpected server response.");
			jQuery(".ulp-popup-form").find(".ulp-message").slideDown(350);
			ulp_saving = false;
		}
	});
	return false;
}

function ulp_save_popup(_button) {
	if (ulp_saving) return false;
	var button_object = _button;
	jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(button_object).addClass("ulp-button-disabled");
	jQuery(".ulp-popup-form").find("#ulp-message").slideUp(350);
	ulp_saving = true;

	ulp_neo_hide_layer_details();
	var layers = "";
	jQuery("#ulp-layers-list li").each(function(){
		var layer_id = jQuery(this).attr("id");
		layer_id = layer_id.replace("ulp-layer-", "");
		if (layers != "") layers = layers + ",";
		layers = layers + layer_id;
	});
	jQuery("#ulp_layers").val(layers);
	var postdata;
	if (ulp_post_method && ulp_post_method == "string") {
		postdata = {"ulp_postdata":ulp_encode64(jQuery(".ulp-popup-form").serialize()), "action":"ulp_save_popup"};
	} else postdata = jQuery(".ulp-popup-form").serialize();

	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: postdata,
		success	: function(return_data) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("ulp-button-disabled");
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery("#ulp-popup-id").val(data.id);
					ulp_global_message_show('success', data.message);
				} else if (data.status == "ERROR") {
					jQuery(".ulp-popup-form").find("#ulp-message").html(data.message);
					jQuery(".ulp-popup-form").find("#ulp-message").slideDown(350);
				} else {
					jQuery(".ulp-popup-form").find("#ulp-message").html("Something went wrong. We got unexpected server response.");
					jQuery(".ulp-popup-form").find("#ulp-message").slideDown(350);
				}
			} catch(error) {
				jQuery(".ulp-popup-form").find("#ulp-message").html("Something went wrong. We got unexpected server response.");
				jQuery(".ulp-popup-form").find("#ulp-message").slideDown(350);
			}
			ulp_saving = false;
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("ulp-button-disabled");
			var response_text = XMLHttpRequest.responseText;
			if (response_text.indexOf("Wordfence") >= 0) {
				if (ulp_wordfence_whitelist_attempts > 0) jQuery(".ulp-popup-form").find("#ulp-message").html("Seems applying changes not finished yet or we couldn't automatically whitelist your IP-address. You can do it manually on <a href='admin.php?page=WordfenceSecOpt' target='_blank'>Wordfence Options</a> page or disable XSS rule on <a href='admin.php?page=WordfenceWAF' target='_blank'>Wordfence Web Application Firewall</a> page.");
				else jQuery(".ulp-popup-form").find("#ulp-message").html("Seems we have false positive from Wordfence Web Application Firewall while trying to save the popup. To avoid this problem in the future, please whitelist your IP-address or disable XSS rule on <a href='admin.php?page=WordfenceWAF' target='_blank'>Wordfence Web Application Firewall</a> page.<br /><a href=\'#\' id=\'ulp-wordfence-whitelist-ip-button\' class=\'button-secondary ulp-button ulp-message-button\' onclick=\'return ulp_wordfence_whitelist_ip();\'>Whitelist My IP-address and Save Popup</a>");
				jQuery(".ulp-popup-form").find("#ulp-message").slideDown(350);
			} else {
				jQuery(".ulp-popup-form").find("#ulp-message").html('Something went wrong. We got unexpected server response.');
				jQuery(".ulp-popup-form").find("#ulp-message").slideDown(250);
			}
			ulp_saving = false;
		}
	});
	return false;
}
var ulp_global_message_timer;
function ulp_global_message_show(_type, _message) {
	clearTimeout(ulp_global_message_timer);
	jQuery("#ulp-global-message").fadeOut(300, function() {
		jQuery("#ulp-global-message").attr("class", "");
		jQuery("#ulp-global-message").addClass("ulp-global-message-"+_type).html(_message);
		jQuery("#ulp-global-message").fadeIn(300);
		ulp_global_message_timer = setTimeout(function(){jQuery("#ulp-global-message").fadeOut(300);}, 5000);
	});
}
function ulp_wordfence_whitelist_ip() {
	var data = {action: "ulp_wordfence_whitelist_ip"};
	jQuery("#ulp-wordfence-whitelist-ip-button").attr("disabled", "disabled");
	jQuery("#ulp-wordfence-whitelist-ip-button").html("Whitelisting your IP-address...");
	jQuery.post(ulp_ajax_handler, data, function(data) {
		if (data == "OK") {
			var ulp_wordfence_wait_counter = 30;
			var ulp_interval;
			jQuery(".ulp-popup-form").find("#ulp-message").html("Applying changes. It takes some time. Please wait <span id=\'ulp-wordfence-timer-value\'>"+ulp_wordfence_wait_counter+"</span> seconds.");
			var ulp_wordfence_timer_function = function () {
				ulp_wordfence_wait_counter--;
				if (ulp_wordfence_wait_counter > 0) {
					jQuery("#ulp-wordfence-timer-value").html(ulp_wordfence_wait_counter);
				} else {
					clearInterval(ulp_interval);
					ulp_wordfence_whitelist_attempts++;
					jQuery(".ulp-popup-form").find("#ulp-message").slideUp(350);
					ulp_save_popup();
				}
			}
			ulp_interval = setInterval(function() {ulp_wordfence_timer_function()}, 1000);
		} else {
			jQuery(".ulp-popup-form").find("#ulp-message").html("Unfortunately, we couldn't whitelist your IP automatically, so you have to do it manually on <a href='admin.php?page=WordfenceSecOpt' target='_blank'>Wordfence Options</a> page.");
		}
	});
	return false;
}
function ulp_utf8encode(string) {
	string = string.replace(/\x0d\x0a/g, "\x0a");
	var output = "";
	for (var n = 0; n < string.length; n++) {
		var c = string.charCodeAt(n);
		if (c < 128) {
			output += String.fromCharCode(c);
		} else if ((c > 127) && (c < 2048)) {
			output += String.fromCharCode((c >> 6) | 192);
			output += String.fromCharCode((c & 63) | 128);
		} else {
			output += String.fromCharCode((c >> 12) | 224);
			output += String.fromCharCode(((c >> 6) & 63) | 128);
			output += String.fromCharCode((c & 63) | 128);
		}
	}
	return output;
}
function ulp_encode64(input) {
	var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var output = "";
	var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
	var i = 0;
	input = ulp_utf8encode(input);
	while (i < input.length) {
		chr1 = input.charCodeAt(i++);
		chr2 = input.charCodeAt(i++);
		chr3 = input.charCodeAt(i++);
		enc1 = chr1 >> 2;
		enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
		enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
		enc4 = chr3 & 63;
		if (isNaN(chr2)) {
			enc3 = enc4 = 64;
		} else if (isNaN(chr3)) {
			enc4 = 64;
		}
		output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
	}
	return output;
}
function ulp_utf8decode(input) {
	var string = "";
	var i = 0;
	var c = c1 = c2 = 0;
	while ( i < input.length ) {
		c = input.charCodeAt(i);
		if (c < 128) {
			string += String.fromCharCode(c);
			i++;
		} else if ((c > 191) && (c < 224)) {
			c2 = input.charCodeAt(i+1);
			string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
			i += 2;
		} else {
			c2 = input.charCodeAt(i+1);
			c3 = input.charCodeAt(i+2);
			string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}
	}
	return string;
}
function ulp_decode64(input) {
	var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var output = "";
	var chr1, chr2, chr3;
	var enc1, enc2, enc3, enc4;
	var i = 0;
	input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
	while (i < input.length) {
		enc1 = keyString.indexOf(input.charAt(i++));
		enc2 = keyString.indexOf(input.charAt(i++));
		enc3 = keyString.indexOf(input.charAt(i++));
		enc4 = keyString.indexOf(input.charAt(i++));
		chr1 = (enc1 << 2) | (enc2 >> 4);
		chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
		chr3 = ((enc3 & 3) << 6) | enc4;
		output = output + String.fromCharCode(chr1);
		if (enc3 != 64) {
			output = output + String.fromCharCode(chr2);
		}
		if (enc4 != 64) {
			output = output + String.fromCharCode(chr3);
		}
	}
	output = ulp_utf8decode(output);
	return output;
}
function ulp_2hex(c) {
	var hex = c.toString(16);
	return hex.length == 1 ? "0" + hex : hex;
}
function ulp_rgb2hex(r, g, b) {
	return "#" + ulp_2hex(r) + ulp_2hex(g) + ulp_2hex(b);
}
function ulp_hex2rgb(hex) {
	var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
	hex = hex.replace(shorthandRegex, function(m, r, g, b) {
		return r + r + g + g + b + b;
	});
	var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	return result ? {
		r: parseInt(result[1], 16),
		g: parseInt(result[2], 16),
		b: parseInt(result[3], 16)
	} : false;
}
function ulp_inarray(needle, haystack) {
	var length = haystack.length;
	for(var i = 0; i < length; i++) {
		if(haystack[i] == needle) return true;
	}
	return false;
}
function ulp_self_close() {
	return false;
}
function ulp_seticon(object, prefix) {
	var icon = jQuery(object).children().attr("class");
	jQuery("#"+prefix).val(icon);
	jQuery("#"+prefix+"-image i").removeClass();
	jQuery("#"+prefix+"-image i").addClass(icon);
	jQuery("#"+prefix+"-set .ulp-icon-active").removeClass("ulp-icon-active");
	jQuery(object).addClass("ulp-icon-active");
	jQuery("#"+prefix+"-set").slideUp(300);
	ulp_build_preview();
}
function ulp_customfields_addfield(field_type) {
	jQuery("#ulp-customfields-loading").fadeIn(350);
	jQuery("#ulp-customfields-message").slideUp(350);
	jQuery("#ulp-customfields-selector").toggle(200);
	jQuery("#ulp-customfields-selector").attr("disabled", "disabled");
	jQuery.post(ulp_ajax_handler, 
		"action=ulp-customfields-addfield&ulp_type="+field_type,
		function(return_data) {
			//alert(return_data);
			jQuery("#ulp-customfields-loading").fadeOut(350);
			jQuery("#ulp-customfields-selector").removeAttr("disabled");
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				var status = data.status;
				if (status == "OK") {
					jQuery("#ulp-customfields").append(data.html);
					jQuery("#ulp-customfields-field-"+data.id).slideDown(350);
					ulp_customfields_minmaxdate_options_set();
				} else if (status == "ERROR") {
					jQuery("#ulp-customfields-message").html(data.message);
					jQuery("#ulp-customfields-message").slideDown(350);
				} else {
					jQuery("#ulp-customfields-message").html("Service is not available.");
					jQuery("#ulp-customfields-message").slideDown(350);
				}
			} catch(error) {
				jQuery("#ulp-customfields-message").html("Service is not available.");
				jQuery("#ulp-customfields-message").slideDown(350);
			}
		}
	);
	return false;
}
function ulp_delete_custom_field(field_id) {
	jQuery("#ulp-customfields-field-"+field_id).slideUp(350, function() {
		jQuery("#ulp-customfields-field-"+field_id).remove();
		ulp_customfields_minmaxdate_options_set();
		ulp_build_preview();
	});
	return false;
}
function ulp_escape_html(text) {
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
function ulp_input_options_focus(object, post) {
	var base_id = jQuery(object).attr("id");
	var action = jQuery(object).attr("data-action");
	if (jQuery("#"+base_id+"-items").hasClass("ulp-visible")) {
	} else {
		jQuery("#"+base_id+"-items").find(".ulp-options-list-spinner").fadeIn(300);
		jQuery("#"+base_id+"-items").find(".ulp-options-list-data").html("");
		jQuery("#"+base_id+"-items").removeClass("ulp-vertical-scroll");
		jQuery("#"+base_id+"-items").fadeIn(300);
		jQuery("#"+base_id+"-items").addClass("ulp-visible");
		jQuery.post(ulp_ajax_handler, post, function(return_data) {
			jQuery("#"+base_id+"-items").find(".ulp-options-list-spinner").hide();
			try {
				var data = jQuery.parseJSON(return_data);
				var status = data.status;
				if (status == "OK") {
					jQuery("#"+base_id+"-items").find(".ulp-options-list-data").html(data.html);
					if (data.items > 4) jQuery("#"+base_id+"-items").addClass("ulp-vertical-scroll");
				} else {
					jQuery("#"+base_id+"-items").find(".ulp-options-list-data").html("<div style='text-align: center;'><strong>Internal error! Can not connect to server.</strong></div>");
				}
			} catch(error) {
				jQuery("#"+base_id+"-items").find(".ulp-options-list-data").html("<div style='text-align: center;'><strong>Internal error! Can not connect to server.</strong></div>");
			}
		});
	}
}
function ulp_input_options_blur(object) {
	var base_id = jQuery(object).attr("id");
	jQuery("#"+base_id+"-items").removeClass("ulp-visible");
	jQuery("#"+base_id+"-items").fadeOut(300);
}
function ulp_input_options_selected(object) {
	var item_id = jQuery(object).attr("data-id");
	var item_title = jQuery(object).attr("data-title");
	var base_id = jQuery(object).parentsUntil(".ulp-options-list").parent().attr("id");
	base_id = base_id.replace("-items", "");
	jQuery("#"+base_id).val(item_title);
	jQuery("#"+base_id+"-id").val(item_id);
	return false;
}
function ulp_neo_toggle_layers() {
	if (jQuery("#ulp-toggle-layers-icon").hasClass("fa-minus-square")) {
		jQuery("#ulp-toggle-layers-icon").removeClass("fa-minus-square");
		jQuery("#ulp-toggle-layers-icon").addClass("fa-plus-square");
		jQuery("#ulp-layers-list").slideUp(200);
	} else {
		jQuery("#ulp-toggle-layers-icon").removeClass("fa-plus-square");
		jQuery("#ulp-toggle-layers-icon").addClass("fa-minus-square");
		jQuery("#ulp-layers-list").slideDown(200);
	}
	return false;
}
function ulp_neo_toggle_constructor_settings() {
	jQuery("#ulp-layers-constructor-settings").slideToggle(200);
	return false;
}
function ulp_neo_hide_layer_details() {
	jQuery("body").animate({"left" : "0px"});
	jQuery("#ulp-layer-details").animate({"right" : "-440px"});
	return false;
}
function ulp_neo_add_layer(params) {
	ulp_new_layer_id++;
	var layer_id = "new-"+ulp_new_layer_id;
	jQuery.each(ulp_default_layer_options, function(key, value) {
		jQuery("#ulp-layers").append('<input type="hidden" id="ulp_layer_'+layer_id+'_'+key+'" name="ulp_layer_'+layer_id+'_'+key+'">');
		if (params.hasOwnProperty(key)) value = params[key];
		jQuery("#ulp_layer_"+layer_id+"_"+key).val(value);
	});
	jQuery("#ulp-layers-list").append('<li id="ulp-layer-'+layer_id+'"><i class="fas fa-arrows-alt-v ulp-sortable-icon"></i><a href="#" class="ulp-layer-action-icon ulp-layer-action-delete" title="Delete the layer"><i class="fas fa-times"></i></a><a href="#" class="ulp-layer-action-icon ulp-layer-action-copy" title="Duplicate the layer"><i class="far fa-copy"></i></a><label></label><span></span></li>');
	if (params.hasOwnProperty("title")) jQuery("#ulp-layer-"+layer_id+" label").html(params.title);
	else jQuery("#ulp-layer-"+layer_id+" label").html(ulp_default_layer_options.title);
	if (params.hasOwnProperty("content")) {
		if (params.content == "") jQuery("#ulp-layer-"+layer_id+" span").html("No content...");
		else jQuery("#ulp-layer-"+layer_id+" span").html(ulp_escape_html(params.content));
	} else jQuery("#ulp-layer-"+layer_id+" span").html("No content...");
	jQuery("#ulp-layer-"+layer_id+" .ulp-layer-action-delete").click(function(event){
		event.stopPropagation();
		ulp_neo_delete_layer(this);
		return false;
	});
	jQuery("#ulp-layer-"+layer_id+" .ulp-layer-action-copy").click(function(event){
		event.stopPropagation();
		ulp_neo_copy_layer(this);
		return false;
	});
	jQuery("#ulp-layer-"+layer_id).click(function(){
		ulp_neo_edit_layer(layer_id, true);
	});
	ulp_neo_edit_layer(layer_id, true);
	ulp_build_preview();
	return false;
}
function ulp_neo_edit_layer(layer_id, open_details) {
	if (ulp_updating_layer_details) return false;
	ulp_updating_layer_details = true;
	jQuery(".ulp-layers-list-item-selected").removeClass("ulp-layers-list-item-selected");
	jQuery("#ulp-layer-"+layer_id).addClass("ulp-layers-list-item-selected");
	ulp_neo_select_preview_layer(layer_id);
	jQuery.each(ulp_default_layer_options, function(key, value) {
		if (key == "scrollbar" || key == "confirmation_layer" || key == "inline_disable" || key == "background_gradient" || key == "box_shadow" || key == "box_shadow_inset") {
			if (jQuery("[name=\'ulp_layer_"+layer_id+"_"+key+"\']").val() == "on") {
				jQuery("[data-id=\'ulp_layer_"+key+"\']").removeClass("fa-square");
				jQuery("[data-id=\'ulp_layer_"+key+"\']").addClass("fa-check-square");
				if (key == "background_gradient") jQuery(".ulp-background-gradient-only").show();
				if (key == "box_shadow") jQuery(".ulp-box-shadow-only").show();
			} else {
				jQuery("[data-id=\'ulp_layer_"+key+"\']").removeClass("fa-check-square");
				jQuery("[data-id=\'ulp_layer_"+key+"\']").addClass("fa-square");
				if (key == "background_gradient") jQuery(".ulp-background-gradient-only").hide();
				if (key == "box_shadow") jQuery(".ulp-box-shadow-only").hide();
			}
		}
		jQuery("[name=\'ulp_layer_"+key+"\']").val(jQuery("[name=\'ulp_layer_"+layer_id+"_"+key+"\']").val());
		if (jQuery("[name=\'ulp_layer_"+key+"\']").hasClass("ulp-color")) {
			jQuery("[name=\'ulp_layer_"+key+"\']").parent().parent().find(".wp-color-result").css("background-color", "");
			jQuery("[name=\'ulp_layer_"+key+"\']").wpColorPicker('color', jQuery("[name=\'ulp_layer_"+layer_id+"_"+key+"\']").val());
		}
	});
	ulp_active_layer = layer_id;
	if (open_details) {
		jQuery("body").animate({"left" : "-440px"});
		jQuery("#ulp-layer-details").animate({"right" : "0px"});
	}
	ulp_updating_layer_details = false;
	return false;
}
function ulp_neo_select_preview_layer(layer_id) {
	var width, height, top, left;
	jQuery(".ulp-layer-position").remove();
	jQuery(".ulp-layer-size").remove();
	jQuery(".ulp-preview-layer-selected").removeClass("ulp-preview-layer-selected");
	jQuery("#ulp-preview-layer-"+layer_id).append("<div class=\'ulp-layer-position\'></div>");
	jQuery("#ulp-preview-layer-"+layer_id).append("<div class=\'ulp-layer-size\'></div>");
	jQuery("#ulp-preview-layer-"+layer_id).addClass("ulp-preview-layer-selected");
	top = jQuery("#ulp_layer_"+layer_id+"_top").val();
	left = jQuery("#ulp_layer_"+layer_id+"_left").val();
	jQuery("#ulp-preview-layer-"+layer_id).find(".ulp-layer-position").html("("+left+", "+top+")");
	width = jQuery("#ulp_layer_"+layer_id+"_width").val();
	height = jQuery("#ulp_layer_"+layer_id+"_height").val();
	if (!isFinite(width) || isNaN(parseFloat(width))) width = "auto";
	if (!isFinite(height) || isNaN(parseFloat(height))) height = "auto";
	jQuery("#ulp-preview-layer-"+layer_id).find(".ulp-layer-size").html(""+width+" x "+height+"");
	return false;
}
function ulp_neo_sync_layer_details() {
	if (ulp_active_layer != "") {
		jQuery.each(ulp_default_layer_options, function(key, value) {
			jQuery("[name=\'ulp_layer_"+ulp_active_layer+"_"+key+"\']").val(jQuery("[name=\'ulp_layer_"+key+"\']").val());
		});
	}
}
function ulp_neo_delete_layer(object) {
	var answer = confirm("Do you really want to delete this layer?")
	if (answer) {
		var layer = jQuery(object).parent();
		var layer_id = jQuery(layer).attr("id");
		layer_id = layer_id.replace("ulp-layer-", "");
		jQuery(layer).slideUp(300, function(){
			jQuery(layer).remove();
			if (ulp_active_layer == layer_id) {
				ulp_active_layer = "";
				ulp_neo_hide_layer_details();
			}
			jQuery.each(ulp_default_layer_options, function(key, value) {
				jQuery("[name=\'ulp_layer_"+layer_id+"_"+key+"\']").remove();
			});
			ulp_build_preview();
		});
	}
}
function ulp_neo_copy_layer(object) {
	var answer = confirm("Do you really want to duplicate this layer?")
	if (answer) {
		var layer = jQuery(object).parent();
		var layer_id = jQuery(layer).attr("id");
		layer_id = layer_id.replace("ulp-layer-", "");
		ulp_new_layer_id++;
		var new_layer_id = "new-"+ulp_new_layer_id;
		jQuery.each(ulp_default_layer_options, function(key, value) {
			jQuery("#ulp-layers").append('<input type="hidden" id="ulp_layer_'+new_layer_id+'_'+key+'" name="ulp_layer_'+new_layer_id+'_'+key+'">');
			jQuery("#ulp_layer_"+new_layer_id+"_"+key).val(jQuery("#ulp_layer_"+layer_id+"_"+key).val());
		});
		jQuery("#ulp-layers-list").append('<li id="ulp-layer-'+new_layer_id+'"><i class="fas fa-arrows-alt-v ulp-sortable-icon"></i><a href="#" class="ulp-layer-action-icon ulp-layer-action-delete" title="Delete the layer"><i class="fas fa-times"></i></a><a href="#" class="ulp-layer-action-icon ulp-layer-action-copy" title="Duplicate the layer"><i class="far fa-copy"></i></a><label></label><span></span></li>');
		jQuery("#ulp-layer-"+new_layer_id+" label").html(jQuery("#ulp-layer-"+layer_id+" label").html());
		jQuery("#ulp-layer-"+new_layer_id+" span").html(jQuery("#ulp-layer-"+layer_id+" span").html());
		jQuery("#ulp-layer-"+new_layer_id+" .ulp-layer-action-delete").click(function(event){
			event.stopPropagation();
			ulp_neo_delete_layer(this);
			return false;
		});
		jQuery("#ulp-layer-"+new_layer_id+" .ulp-layer-action-copy").click(function(event){
			event.stopPropagation();
			ulp_neo_copy_layer(this);
			return false;
		});
		jQuery("#ulp-layer-"+new_layer_id).click(function(){
			ulp_neo_edit_layer(new_layer_id, true);
		});
		ulp_build_preview();
	}
}
function ulp_helper_close() {
	jQuery("#ulp-helper-overlay").fadeOut(300);
	jQuery(".ulp-helper-window").fadeOut(300);
	return false;
}
function ulp_helper2_close() {
	jQuery("#ulp-helper2-overlay").fadeOut(300);
	jQuery(".ulp-helper2-window").fadeOut(300);
	return false;
}
function ulp_helper3_close() {
	jQuery("#ulp-helper3-overlay").fadeOut(300);
	jQuery(".ulp-helper3-window").fadeOut(300);
	return false;
}
function ulp_helper_add_layer() {
	jQuery("#ulp-helper-overlay").fadeIn(300);
	if (typeof ulpext_helper_add_layer == 'function') {
		ulpext_helper_add_layer();
	}
	jQuery(".ulp-helper-add-layer-item").show();
	jQuery(".ulp-helper-add-layer-item").each(function(){
		var item = this;
		var unique = jQuery(this).attr("data-unique");
		if (unique) {
			jQuery("#ulp-layers-list li").each(function(){
				var layer_id = jQuery(this).attr("id").replace("ulp-layer-", "");
				var content = jQuery("#ulp_layer_"+layer_id+"_content").val();
				if (content.indexOf(unique) > -1) {
					jQuery(item).hide();
					return false;
				}
			});
		}
	});
	jQuery("#ulp-helper-add-layer-window").fadeIn(300);
	return false;
}
function ulp_helper_add_layer_process(content_type) {
	if (typeof ulpext_helper_add_layer_process == 'function') {
		var result = ulpext_helper_add_layer_process(content_type);
		if (result) return false;
	}
	switch(content_type) {
		case 'rectangle':
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Rectangle","content":"","width":"200","height":"100","background_color":"#808080"});
			break;
		case 'field-name':
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Name Field","content":"{subscription-name}","width":"250","height":"40"});
			break;
		case 'field-email':
			ulp_helper_close();
			ulp_neo_add_layer({"title":"E-mail Field","content":"{subscription-email}","width":"250","height":"40"});
			break;
		case 'field-phone':
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Phone Field","content":"{subscription-phone}","width":"250","height":"40"});
			break;
		case 'field-message':
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Message Field","content":"{subscription-message}","width":"250","height":"120"});
			break;
		case 'submit-button':
			ulp_helper_close();
			ulp_neo_add_layer({"title":"Submit Button","content":"{subscription-submit}","width":"250","height":"50","content_align":"center","font_color":"#FFF","font_size":"15"});
			break;
		default:
			var window = jQuery("#ulp-helper-window-"+content_type);
			if (window.length > 0) {
				jQuery(window).find("input, textarea").val("");
				jQuery(window).find('input[type="checkbox"]').prop("checked", false);
				jQuery("#ulp-helper2-overlay").fadeIn(300);
				jQuery(window).fadeIn(300);
				var wh = jQuery(window).height();
				if (wh > 100) {
					wh = parseInt(wh/2, 10)*2 + 2;
					jQuery(window).height(wh);
				}
			}
			break;
	}
	return false;
}
function ulp_helper_create_label() {
	var content, title;
	var label = jQuery("#ulp-helper2-label-label").val();
	var url = jQuery("#ulp-helper2-label-url").val();
	var inherited = "";
	if (jQuery("#ulp-helper2-label-inherited").is(":checked")) inherited = ' class="ulp-inherited"';
	label = label.trim();
	url = url.trim();
	if (label.length == 0) {
		jQuery("#ulp-helper3-message").html("Please enter text label.");
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	if (url.length == 0) {
		title = "Text Label";
		content = label;
	} else {
		title = "Link";
		content = "<a"+inherited+" href=\""+ulp_escape_html(url)+"\">"+ulp_escape_html(label)+"</a>";
	}
	ulp_helper2_close();
	ulp_helper_close();
	ulp_neo_add_layer({"title":title,"content":content});
	return false;
}
function ulp_helper_create_youtube() {
	var content, id;
	var code = jQuery("#ulp-helper2-youtube-code").val();
	var error = "";
	code = code.trim();
	if (code.length == 0) {
		error = "Please enter YouTube embed code.";
	} else {
		var rx = /^.*(?:(?:youtu\.be\/|v\/|vi\/|u\/\w\/|embed\/)|(?:(?:watch)?\?v(?:i)?=|\&v(?:i)?=))([^#\&\?]*).*/;
		id = code.match(rx);
		if (!id || !(1 in id)) error = "Can not parse YouTube URL or embed code.";
	}
	if (error.length > 0) {
		jQuery("#ulp-helper3-message").html(error);
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	content = "<iframe width=\"100%\" height=\"100%\" style=\"width:100%;height:100%;\" src=\"https://www.youtube.com/embed/"+id[1]+"?rel=0\" frameborder=\"0\" allowfullscreen></iframe>";
	ulp_helper2_close();
	ulp_helper_close();
	ulp_neo_add_layer({"title":"YouTube Video","content":content,"width":"720","height":"405"});
	return false;
}
function ulp_helper_create_vimeo() {
	var content, id;
	var code = jQuery("#ulp-helper2-vimeo-code").val();
	var error = "";
	code = code.trim();
	if (code.length == 0) {
		error = "Please enter YouTube embed code.";
	} else {
		var rx = /^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/;
		id = code.match(rx);
		if (!id || !(5 in id)) error = "Can not parse Vimeo URL or embed code.";
	}
	if (error.length > 0) {
		jQuery("#ulp-helper3-message").html(error);
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	content = "<iframe width=\"100%\" height=\"100%\" src=\"https://player.vimeo.com/video/"+id[5]+"?color=ffffff&title=0&byline=0&portrait=0&badge=0\" frameborder=\"0\" allowfullscreen></iframe>";
	ulp_helper2_close();
	ulp_helper_close();
	ulp_neo_add_layer({"title":"Vimeo Video","content":content,"width":"720","height":"405"});
	return false;
}
function ulp_helper_create_html() {
	var code = jQuery("#ulp-helper2-html-code").val();
	code = code.trim();
	ulp_helper2_close();
	ulp_helper_close();
	ulp_neo_add_layer({"title":"Custom HTML","content":code});
	return false;
}
function ulp_helper_create_linkedbutton() {
	var content;
	var label = jQuery("#ulp-helper2-linked-button-label").val();
	var url = jQuery("#ulp-helper2-linked-button-url").val();
	var color = jQuery("#ulp-helper2-linked-button-color").val();
	label = label.trim();
	url = url.trim();
	if (label.length == 0) {
		jQuery("#ulp-helper3-message").html("Please enter button label.");
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	if (color.length == 0) {
		jQuery("#ulp-helper3-message").html("Please select button color.");
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	if (url.length == 0) url = "#";
	content = "<a href=\""+ulp_escape_html(url)+"\" class=\"ulp-link-button ulp-link-button-"+color+"\">"+ulp_escape_html(label)+"</a>";
	ulp_helper2_close();
	ulp_helper_close();
	ulp_neo_add_layer({"title":"Linked Button","content":content,"width":"250","height":"40","font_color":"#FFF","content_align":"center"});
	return false;
}
function ulp_helper_seticon(object, prefix) {
	var icon = jQuery(object).children().attr("class");
	jQuery("#"+prefix).val(icon);
	jQuery("#"+prefix+"-set .ulp-helper-icon-active").removeClass("ulp-helper-icon-active");
	jQuery(object).addClass("ulp-helper-icon-active");
	return false;
}
function ulp_helper_create_icon() {
	var content;
	var title = jQuery("#ulp-helper2-icon-title").val();
	var url = jQuery("#ulp-helper2-icon-url").val();
	var icon = jQuery("#ulp-helper2-icon-icon").val();
	title = title.trim();
	url = url.trim();
	if (icon.length == 0) {
		jQuery("#ulp-helper3-message").html("Please select icon.");
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	if (url.length == 0) {
		content = "<i class=\""+icon+"\"></i>";
	} else {
		if (title.length == 0) content = "<a href=\""+ulp_escape_html(url)+"\"><i class=\""+icon+"\"></i></a>";
		else content = "<a href=\""+ulp_escape_html(url)+"\" title=\""+ulp_escape_html(title)+"\"><i class=\""+icon+"\"></i></a>";
	}
	if (title.length == 0) title = icon;
	ulp_helper2_close();
	ulp_helper_close();
	ulp_neo_add_layer({"title":"Icon: "+title,"content":content,"font_size":"28","content_align":"center"});
	return false;
}
function ulp_helper_create_image() {
	var content;
	var title = jQuery("#ulp-helper2-image-title").val();
	var image_url = jQuery("#ulp-helper2-image-url").val();
	var url = jQuery("#ulp-helper2-image-url2").val();
	title = title.trim();
	url = url.trim();
	image_url = image_url.trim();
	if (image_url.length == 0) {
		jQuery("#ulp-helper3-message").html("Please specify image URL.");
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	if (jQuery("#ulp-helper2-image-type-img").is(":checked")) {
		if (url.length == 0) {
			content = "<img src=\""+ulp_escape_html(image_url)+"\" alt=\""+ulp_escape_html(title)+"\" />";
		} else {
			content = "<a href=\""+ulp_escape_html(url)+"\" title=\""+ulp_escape_html(title)+"\"><img src=\""+ulp_escape_html(image_url)+"\" alt=\""+ulp_escape_html(title)+"\" /></a>";
		}
		ulp_helper2_close();
		ulp_helper_close();
		ulp_neo_add_layer({"title":"Image","content":content,"width":"350"});
	} else {
		if (url.length != 0) {
			content = "<a class=\"ulp-inherited\" href=\""+ulp_escape_html(url)+"\" title=\""+ulp_escape_html(title)+"\">&nbsp;</a>";
		} else content = "";
		ulp_helper2_close();
		ulp_helper_close();
		ulp_neo_add_layer({"title":"Background Image","content":content,"width":"350","height":"350","background_image":image_url,"background_image_repeat":"no-repeat","background_image_size":"cover"});
	}
	return false;
}
var ulp_media_frame;
function ulp_helper_media_library_image(dest_id) {
	ulp_media_frame = wp.media({
		title: 'Select Image',
		library: {
			type: 'image'
		},
		multiple: false
	});
	ulp_media_frame.on("select", function() {
		var attachment = ulp_media_frame.state().get("selection").first();
		jQuery("#"+dest_id).val(attachment.attributes.url);
	});
	ulp_media_frame.open();
	return false;
}
function ulp_helper_change_close_type() {
	jQuery(".ulp-helper2-close-types").slideUp(300);
	var type = jQuery("#ulp-helper2-close-type").val();
	if (type == "image") jQuery("#ulp-helper2-close-type-image").slideDown(300);
	else if (type == "icon") jQuery("#ulp-helper2-close-type-icon").slideDown(300);
	return false;
}
function ulp_helper_create_close() {
	var content, onclick;
	if (jQuery("#ulp-helper2-close-action-forever").is(":checked")) onclick = "return ulp_close_forever();";
	else onclick = "return ulp_self_close();";
	var type = jQuery("#ulp-helper2-close-type").val();
	if (type == "image") {
		var image_url = jQuery("#ulp-helper2-close-image").val();
		image_url = image_url.trim();
		if (image_url.length == 0) {
			jQuery("#ulp-helper3-message").html("Please specify image URL.");
			jQuery("#ulp-helper3-overlay").fadeIn(300);
			jQuery("#ulp-helper-window-message").fadeIn(300);
			return false;
		}
		content = "<a href=\"#\" onclick=\""+onclick+"\"><img src=\""+ulp_escape_html(image_url)+"\" alt=\"\" /></a>";
		ulp_helper2_close();
		ulp_helper_close();
		ulp_neo_add_layer({"title":"Close Icon","content":content,"width":"60"});
	} else if (type == "icon") {
		var icon = jQuery("#ulp-helper2-close-icon").val();
		if (icon.length == 0) {
			jQuery("#ulp-helper3-message").html("Please select icon.");
			jQuery("#ulp-helper3-overlay").fadeIn(300);
			jQuery("#ulp-helper-window-message").fadeIn(300);
			return false;
		}
		content = "<a href=\"#\" onclick=\""+onclick+"\"><i class=\""+icon+"\"></i></a>";
		ulp_helper2_close();
		ulp_helper_close();
		ulp_neo_add_layer({"title":"Close Icon","content":content,"font_size":"28","inline_disable":"on"});
	} else {
		content = "<a href=\"#\" onclick=\""+onclick+"\">×</a>";
		ulp_helper2_close();
		ulp_helper_close();
		ulp_neo_add_layer({"title":"Close Icon","content":content,"font_size":"32","inline_disable":"on"});
	}
	return false;
}
function ulp_helper_setcolor(object, prefix) {
	var color = jQuery(object).attr("data-color");
	jQuery("#"+prefix).val(color);
	jQuery("#"+prefix+"-set .ulp-helper2-color-item-selected").removeClass("ulp-helper2-color-item-selected");
	jQuery(object).addClass("ulp-helper2-color-item-selected");
	return false;
}
function ulp_helper_change_bullet_type() {
	jQuery(".ulp-helper2-bullet-types").slideUp(300);
	var type = jQuery("#ulp-helper2-bullet-type").val();
	if (type == "icon") jQuery("#ulp-helper2-bullet-type-icon").slideDown(300);
	return false;
}
function ulp_helper_create_bullet() {
	var content, li_class;
	var text = jQuery("#ulp-helper2-bullet-items").val();
	text = text.trim();
	var type = jQuery("#ulp-helper2-bullet-type").val();
	var color = jQuery("#ulp-helper2-bullet-color").val();
	if (text.length == 0) {
		jQuery("#ulp-helper3-message").html("Please specify at least one item.");
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	if (color.length == 0) {
		jQuery("#ulp-helper3-message").html("Please select bullet color.");
		jQuery("#ulp-helper3-overlay").fadeIn(300);
		jQuery("#ulp-helper-window-message").fadeIn(300);
		return false;
	}
	if (type == "icon") {
		var icon = jQuery("#ulp-helper2-bullet-icon").val();
		if (icon.length == 0) {
			jQuery("#ulp-helper3-message").html("Please select bullet icon.");
			jQuery("#ulp-helper3-overlay").fadeIn(300);
			jQuery("#ulp-helper-window-message").fadeIn(300);
			return false;
		}
		li_class = icon;
	} else {
		li_class = "ulp-ul-li";
	}
	var items = text.split("\n");
	content = "<ul class=\"ulp-ul ulp-ul-"+color+"\">";
	for (var i=0; i<items.length; i++) {
		items[i] = items[i].trim();
		if (items[i].length > 0) content += "<li class=\""+li_class+"\">"+ulp_escape_html(items[i])+"</li>";
	}
	content += "</ul>";
	ulp_helper2_close();
	ulp_helper_close();
	ulp_neo_add_layer({"title":"Bulleted List","content":content});
	return false;
}
function ulp_toggle_loader_settings() {
	if (jQuery("#ulp_no_preload").is(":checked")) {
		jQuery(".ulp-row-loader-settings").fadeIn(300);
	} else {
		jQuery(".ulp-row-loader-settings").fadeOut(300);
		jQuery("#ulp_preload_event_popups").prop("checked", false);
	}
}
function ulp_toggle_verifier(object) {
	if (!object) object = jQuery(".ulp-email-verifier");
	jQuery(object).each(function(){
		var options_id = jQuery(this).attr("data-id");
		if (jQuery(this).is(":checked")) {
			jQuery("#"+options_id).slideDown(300);
		} else {
			jQuery("#"+options_id).slideUp(300);
		}
	});
	var verifier_found = false;
	jQuery(".ulp-email-verifier").each(function(){
		if (jQuery(this).is(":checked")) verifier_found = true;
	});
	if (verifier_found == true) {
		jQuery('#ulp_email_validation').prop('checked', false);
		jQuery('#ulp_email_validation').prop('readonly', true);
		
	} else {
		jQuery('#ulp_email_validation').prop('readonly', false);
	}
}
function ulp_set_spinner(object, spinner) {
	jQuery("#ulp_ajax_spinner").val(spinner);
	jQuery(".ulp-spinner-item").removeClass("ulp-spinner-item-selected");
	jQuery(object).addClass("ulp-spinner-item-selected");
}
function ulp_reset_settings(_button) {
	ulp_modal_open({
		message:		"Please confirm that you want to delete the item.",
		ok_label:		"Delete",
		ok_function:	function(e) {
			ulp_modal_close();
			if (ulp_saving) return false;
			var button_object = _button;
			jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
			jQuery(button_object).addClass("ulp-button-disabled");
			ulp_saving = true;
			var post_data = {action: "ulp-settings-reset", settings: "off", meta: "off"};
			if (jQuery("#ulp-reset-settings").is(":checked")) post_data.settings = "on";
			if (jQuery("#ulp-reset-meta").is(":checked")) post_data.meta = "on";
			jQuery.ajax({
				type	: "POST",
				url		: ulp_ajax_handler, 
				data	: post_data,
				success	: function(return_data) {
					jQuery(button_object).find("i").attr("class", "fas fa-times");
					jQuery(button_object).removeClass("ulp-button-disabled");
					try {
						var data = jQuery.parseJSON(return_data);
						if (data.status == "OK") {
							if (data.settings == 'on' || data.meta == 'on')	location.href = '?page=ulp-settings';
						} else if (data.status == "ERROR") {
							ulp_global_message_show("danger", data.message);
						} else {
							ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
						}
					} catch(error) {
						ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
					}
					ulp_saving = false;
				},
				error	: function(XMLHttpRequest, textStatus, errorThrown) {
					jQuery(button_object).find("i").attr("class", "fas fa-times");
					jQuery(button_object).removeClass("ulp-button-disabled");
					ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
					ulp_saving = false;
				}
			});
		}
	});
	return false;
}			

/* Advanced Targeting - 2017-04-10 - begin */
var ulp_targets_loading = false;
function ulp_tragets_ready() {
	jQuery(window).resize(function() {
		ulp_targets_resize();
	});
	jQuery(".ulp-targets-overlay").click(function(){ulp_targets_window_close();});
	jQuery(".ulp-targets-intro-overlay").click(function(){ulp_targets_intro_step_close();});
	jQuery(".ulp-targets-list").sortable({
		connectWith: ".ulp-targets-list",
		items: ".ulp-targets-list-item",
		forcePlaceholderSize: true,
		dropOnEmpty: true,
		placeholder: "ulp-targets-list-item-placeholder",
		start: function(event, ui) {
			jQuery(".ulp-targets-list-item-animate").removeClass("ulp-targets-list-item-animate");
		},
		over: function(event, ui) {
			ulp_targets_switch_noitems();
		},
		out: function(event, ui) {
			ulp_targets_switch_noitems();
		},
		stop: function(event, ui) {
			ulp_targets_save_list();
		}
	});
	
	jQuery(".ulp-targets-list-item").disableSelection();
}
function ulp_targets_switch_noitems() {
	if (jQuery("#ulp-targets-list-passive .ulp-targets-list-item-placeholder").length > 0 || jQuery("#ulp-targets-list-passive .ulp-targets-list-item:not(.ui-sortable-helper)").length > 0) {
		jQuery("#ulp-targets-list-passive .ulp-targets-noitems-message").hide();
	} else {
		jQuery("#ulp-targets-list-passive .ulp-targets-noitems-message").show();
	}
	if (jQuery("#ulp-targets-list-active .ulp-targets-list-item-placeholder").length > 0 || jQuery("#ulp-targets-list-active .ulp-targets-list-item:not(.ui-sortable-helper)").length > 0) {
		jQuery("#ulp-targets-list-active .ulp-targets-noitems-message").hide();
	} else {
		jQuery("#ulp-targets-list-active .ulp-targets-noitems-message").show();
	}
}
function ulp_targets_resize() {
	var viewport = {
		width: Math.max(320, jQuery(window).width()),
		height: Math.max(320, jQuery(window).height())
	};
	var window_height = 2*parseInt(0.9*viewport.height/2, 10);
	jQuery(".ulp-targets-window").height(window_height);
	jQuery(".ulp-targets-window-content").outerHeight(window_height-38);
	jQuery("#ulp-targets-window-content").css({"min-height": window_height-58-56});
	
}
function ulp_targets_window_close() {
	jQuery(".ulp-targets-overlay").fadeOut(200);
	jQuery(".ulp-targets-window").fadeOut(200);
	return false;
}
function ulp_targets_delete(popup_event, target_id) {
	ulp_modal_open({
		message:		"Please confirm that you want to delete the item.",
		ok_label:		"Delete",
		ok_function:	function(e) {
			jQuery("#ulp-targets-list-item-"+target_id).fadeOut(300, function() {
				jQuery("#ulp-targets-list-item-"+target_id).remove();
				ulp_targets_switch_noitems();
				ulp_targets_deleted.push(target_id);
				ulp_targets_save_list();
			});
			ulp_modal_close();
		}
	});
	return false;
}
function ulp_targets_window_open(popup_event, target_id) {
	ulp_targets_loading = true;
	jQuery("#ulp-targets-window-content").addClass("ulp-targets-window-content-loading");
	jQuery("#ulp-targets-window-content").html("");
	jQuery("#ulp-targets-save").hide();
	if (typeof popup_event == 'undefined') popup_event = "onload";
	if (typeof target_id == 'undefined') target_id = 0;
	var post_data = {"action" : "ulp_targets_load", "ulp-event" : popup_event, "ulp-id" : target_id};
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: post_data,
		success	: function(return_data) {
			jQuery("#ulp-targets-window-content").removeClass("ulp-targets-window-content-loading");
			ulp_targets_loading = false;
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				var status = data.status;
				if (status == "OK") {
					jQuery("#ulp-targets-window-content").html(data.html);
					jQuery("#ulp-targets-save").show();
					jQuery("#ulp-targets-window-content-posts").scroll(function(e) {
						var content_height = jQuery(this).prop('scrollHeight');
						var position = jQuery(this).scrollTop();
						var height = jQuery(this).height();
						if (content_height - height - position < 10) {
							ulp_targets_posts_load(false);
						}
						//alert(content_height+":"+position+":"+height);
					});
					jQuery(".ulp-targets-input-datetime").datetimepicker({
						yearStart:		2018,
						yearEnd:		2100,
						format: 		"Y-m-d H:i",
						formatDate: 	"Y-m-d",
						formatTime: 	"H:i",
						roundTime:		"floor"
					});
				} else if (status == "ERROR") {
					alert(data.message);
				} else {
					jQuery("#ulp-targets-window-content").html("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
				}
			} catch(error) {
				jQuery("#ulp-targets-window-content").html("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
			}
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			ulp_targets_loading = false;
			jQuery("#ulp-targets-window-content").removeClass("ulp-targets-window-content-loading");
			jQuery("#ulp-targets-window-content").html("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
		}
	});
	
	ulp_targets_resize();
	jQuery(".ulp-targets-overlay").fadeIn(200);
	jQuery(".ulp-targets-window").fadeIn(200);
	return false;
}
function ulp_targets_post_type_selected(_object, _step) {
	if (ulp_targets_loading) return false;
	var post_type_id = jQuery(_object).attr("id");
	post_type_id = post_type_id.replace("ulp-targets-post-type-", "");
	if (post_type_id == jQuery("#ulp-post-type").val()) return false;
	jQuery(".ulp-targets-post-type").removeClass("ulp-targets-input-item-selected");
	jQuery(_object).addClass("ulp-targets-input-item-selected");
	jQuery(".ulp-targets-post-type i").attr("class", "far fa-circle");
	jQuery(_object).find("i").attr("class", "far fa-dot-circle");
	jQuery("#ulp-post-type").val(post_type_id);
	if (post_type_id == 'sitewide' || post_type_id == 'homepage') {
		jQuery("#ulp-targets-window-content-taxonomies").slideUp(300, function(){
			jQuery("#ulp-targets-window-content-loading").slideUp(300);
			jQuery("#ulp-targets-window-content-taxonomies").html();
		});
	} else {
		jQuery("#ulp-targets-window-content-taxonomies").slideUp(300, function(){
			jQuery("#ulp-targets-window-content-loading").slideDown(300);
		});
		ulp_targets_loading = true;
		jQuery("#ulp-targets-post-types").addClass("ulp-targets-disabled");
		var post_data = {"action" : "ulp_targets_get_taxonomies", "ulp-step" : _step, "ulp-post-type" : post_type_id, "ulp-event" : jQuery("#ulp-targets-event").val()};
		jQuery.ajax({
			type	: "POST",
			url		: ulp_ajax_handler, 
			data	: post_data,
			success	: function(return_data) {
				jQuery("#ulp-targets-window-content-loading").hide();
				jQuery("#ulp-targets-post-types").removeClass("ulp-targets-disabled");
				ulp_targets_loading = false;
				var data;
				try {
					var data = jQuery.parseJSON(return_data);
					var status = data.status;
					if (status == "OK") {
						jQuery("#ulp-targets-window-content-taxonomies").html(data.html);
						jQuery("#ulp-targets-window-content-taxonomies").fadeIn(300);
						jQuery("#ulp-targets-window-content-posts").scroll(function(e) {
							var content_height = jQuery(this).prop('scrollHeight');
							var position = jQuery(this).scrollTop();
							var height = jQuery(this).height();
							if (content_height - height - position < 10) {
								ulp_targets_posts_load(false);
							}
						});
					} else if (status == "ERROR") {
						alert(data.message);
					} else {
						jQuery("#ulp-targets-window-content-taxonomies").html("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
						jQuery("#ulp-targets-window-content-taxonomies").fadeIn(300);
					}
				} catch(error) {
					jQuery("#ulp-targets-window-content-taxonomies").html("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
					jQuery("#ulp-targets-window-content-taxonomies").fadeIn(300);
				}
			},
			error	: function(XMLHttpRequest, textStatus, errorThrown) {
				ulp_targets_loading = false;
				jQuery("#ulp-targets-window-content-loading").hide();
				jQuery("#ulp-targets-post-types").removeClass("ulp-targets-disabled");
				jQuery("#ulp-targets-window-content-taxonomies").html("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
				jQuery("#ulp-targets-window-content-taxonomies").fadeIn(300);
			}
		});
	}
	return false;
}
function ulp_targets_taxonomy_selected(_object, _taxonomy) {
	if (ulp_targets_loading) return false;
	var load_posts = true;
	if (!jQuery(_object).is(":checked") && jQuery(_object).val() == "all") {
		load_posts = false;
	}
	if (load_posts) {
		ulp_targets_posts_load(true);
	}
	return false;
}
function ulp_targets_posts_load(_new) {
	if (ulp_targets_loading) return false;
	var offset = jQuery("#ulp-targets-next-offset").val();
	if (!_new && offset == -1) return false;
	if (_new) {
		jQuery("#ulp-targets-window-content-posts").fadeOut(300, function(){
			jQuery("#ulp-targets-window-content-posts").html('<div class="ulp-targets-window-content-loading" id="ulp-targets-window-posts-loading"></div>');
			jQuery("#ulp-targets-window-content-posts").fadeIn(300);
		});
	} else {
		jQuery("#ulp-targets-window-content-posts").append('<div class="ulp-targets-window-content-loading" id="ulp-targets-window-posts-loading"></div>');
	}
	ulp_targets_loading = true;
	jQuery(".ulp-targets-taxonomies").addClass("ulp-targets-disabled-all");
	jQuery("#ulp-targets-post-types").addClass("ulp-targets-disabled-all");
	if (_new) jQuery("#ulp-targets-next-offset").val(0);
	var post_data = {"action" : "ulp_targets_get_posts", "ulp-post-type" : jQuery("#ulp-post-type").val(), 'ulp-offset' : jQuery("#ulp-targets-next-offset").val(), "ulp-posts-all" : (jQuery("#ulp-posts-all").is(":checked") ? "on" : "off")};
	jQuery(".ulp-targets-taxonomies").find("input").each(function(){
		var name = jQuery(this).attr("name");
		if (name.indexOf("[]") > 0 && jQuery(this).is(":checked")) {
			name = name.replace("[]", "");
			if (post_data.hasOwnProperty(name)) post_data[name].push(jQuery(this).val());
			else post_data[name] = new Array(jQuery(this).val());
		}
	});
	if (jQuery("#ulp-id").length) post_data['ulp-id'] = jQuery("#ulp-id").val();
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: post_data,
		success	: function(return_data) {
			jQuery("#ulp-targets-window-posts-loading").remove();
			jQuery(".ulp-targets-disabled-all").removeClass("ulp-targets-disabled-all");
			if (_new) jQuery("#ulp-targets-window-content-posts").html("");
			ulp_targets_loading = false;
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				var status = data.status;
				if (status == "OK") {
					jQuery("#ulp-targets-next-offset").val(data.next_offset);
					jQuery("#ulp-targets-window-content-posts").append(data.html);
					jQuery("#ulp-targets-window-content-posts").fadeIn(300);
				} else if (status == "ERROR") {
					alert(data.message);
				} else {
					jQuery("#ulp-targets-window-content-posts").append("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
					jQuery("#ulp-targets-window-content-posts").fadeIn(300);
				}
			} catch(error) {
				jQuery("#ulp-targets-window-content-posts").append("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
				jQuery("#ulp-targets-window-content-posts").fadeIn(300);
			}
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			ulp_targets_loading = false;
			jQuery("#ulp-targets-window-posts-loading").remove();
			jQuery(".ulp-targets-disabled-all").removeClass(".ulp-targets-disabled-all");
			jQuery("#ulp-targets-window-content-posts").html("<div class='ulp-targets-window-content-loading-error'>Something went wrong. We got unexpected server response.</div>");
			jQuery("#ulp-targets-window-content-posts").fadeIn(300);
		}
	});
	return false;
}
function ulp_targets_period_selected(_object) {
	var period_enable = jQuery(_object).attr("id");
	period_enable = period_enable.replace("ulp-targets-period-enable-", "");
	if (period_enable == jQuery("#ulp-period-enable").val()) return false;
	jQuery(".ulp-targets-period").removeClass("ulp-targets-input-item-selected");
	jQuery(_object).addClass("ulp-targets-input-item-selected");
	jQuery(".ulp-targets-period i").attr("class", "far fa-circle");
	jQuery(_object).find("i").attr("class", "far fa-dot-circle");
	jQuery("#ulp-period-enable").val(period_enable);
	if (period_enable == 'on') {
		jQuery(".ulp-targets-period-box").show();
	} else {
		jQuery(".ulp-targets-period-box").hide();
	}
	return false;
}
function ulp_targets_save() {
	if (ulp_targets_loading) return false;
	ulp_targets_loading = true;
	jQuery("#ulp-targets-window-content-errors").slideUp(300);
	jQuery("#ulp-targets-save").attr('disabled', 'disabled');
	jQuery("#ulp-targets-save i").attr('class', 'fas fa-spinner fa-spin');
	var post_data = "action=ulp_targets_save&"+jQuery("#ulp-targets-window-content").find("input, textarea, select").serialize();
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: post_data,
		success	: function(return_data) {
			ulp_targets_loading = false;
			jQuery("#ulp-targets-save").removeAttr('disabled');
			jQuery("#ulp-targets-save i").attr('class', 'fas fa-check');
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				var status = data.status;
				if (status == "OK") {
					if (data.action == 'insert') {
						jQuery("#ulp-targets-list-active").prepend(data.html);
						jQuery("#ulp-targets-list-active").find(".ulp-targets-noitems-message").hide();
					} else {
						jQuery("#ulp-targets-list-item-"+data.id).replaceWith(data.html);
					}
					jQuery("#ulp-targets-list-item-"+data.id).addClass("ulp-targets-list-item-animate");
					ulp_targets_window_close();
				} else if (status == "ERROR") {
					jQuery("#ulp-targets-window-content-errors").html(data.message);
					jQuery("#ulp-targets-window-content-errors").slideDown(300);
				} else {
					jQuery("#ulp-targets-window-content-errors").html("Something went wrong. We got unexpected server response.");
					jQuery("#ulp-targets-window-content-errors").slideDown(300);
				}
			} catch(error) {
				jQuery("#ulp-targets-window-content-errors").html("Something went wrong. We got unexpected server response.");
				jQuery("#ulp-targets-window-content-errors").slideDown(300);
			}
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			ulp_targets_loading = false;
			jQuery("#ulp-targets-save").removeAttr('disabled');
			jQuery("#ulp-targets-save i").attr('class', 'fas fa-check');
			jQuery("#ulp-targets-window-content-errors").html("Something went wrong. We got unexpected server response.");
			jQuery("#ulp-targets-window-content-errors").slideDown(300);
		}
	});
	return false;
}
var ulp_save_requested = false;
var ulp_global_message_timer;
var ulp_targets_deleted = [];
function ulp_targets_save_list() {
	if (ulp_targets_loading) {
		ulp_save_requested = true;
		return false;
	}
	ulp_targets_loading = true;
	clearTimeout(ulp_global_message_timer);
	jQuery(".ulp-targets-global-message").fadeOut(300, function() {
		jQuery(".ulp-targets-global-message").attr("class", "ulp-targets-global-message");
		jQuery(".ulp-targets-global-message").addClass("ulp-targets-global-message-info").html("<i class='fas fa-spinner fa-spin'></i> Saving targets...");
		jQuery(".ulp-targets-global-message").fadeIn(300);
	});
	var post_data = {"action" : "ulp_targets_save_list", "ulp_event": jQuery("#ulp-targets-event").val()};
	var active = [];
	jQuery("#ulp-targets-list-active .ulp-targets-list-item").each(function() {
		var id = jQuery(this).attr("data-id");
		active.push(parseInt(id, 10));
	});
	post_data["ulp_targets_active"] = active.join();
	post_data["ulp_targets_deleted"] = ulp_targets_deleted.join();
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: post_data,
		success	: function(return_data) {
			ulp_targets_loading = false;
			var data;
			try {
				var data = jQuery.parseJSON(return_data);
				var status = data.status;
				if (ulp_save_requested) {
					ulp_save_requested = false;
					ulp_targets_save_list();
					return;
				}
				if (status == "OK") {
					jQuery(".ulp-targets-global-message").fadeOut(300, function() {
						jQuery(".ulp-targets-global-message").attr("class", "ulp-targets-global-message");
						jQuery(".ulp-targets-global-message").addClass("ulp-targets-global-message-success").html(data.message);
						jQuery(".ulp-targets-global-message").fadeIn(300);
						ulp_global_message_timer = setTimeout(function(){jQuery(".ulp-targets-global-message").fadeOut(300);}, 5000);
					});
				} else if (status == "ERROR") {
					jQuery(".ulp-targets-global-message").fadeOut(300, function() {
						jQuery(".ulp-targets-global-message").attr("class", "ulp-targets-global-message");
						jQuery(".ulp-targets-global-message").addClass("ulp-targets-global-message-danger").html(data.message);
						jQuery(".ulp-targets-global-message").fadeIn(300);
						ulp_global_message_timer = setTimeout(function(){jQuery(".ulp-targets-global-message").fadeOut(300);}, 5000);
					});
				} else {
					jQuery(".ulp-targets-global-message").fadeOut(300, function() {
						jQuery(".ulp-targets-global-message").attr("class", "ulp-targets-global-message");
						jQuery(".ulp-targets-global-message").addClass("ulp-targets-global-message-danger").html("Something went wrong. We got unexpected server response.");
						jQuery(".ulp-targets-global-message").fadeIn(300);
						ulp_global_message_timer = setTimeout(function(){jQuery(".ulp-targets-global-message").fadeOut(300);}, 5000);
					});
				}
			} catch(error) {
				if (ulp_save_requested) {
					ulp_save_requested = false;
					ulp_targets_save_list();
				} else {
					jQuery(".ulp-targets-global-message").fadeOut(300, function() {
						jQuery(".ulp-targets-global-message").attr("class", "ulp-targets-global-message");
						jQuery(".ulp-targets-global-message").addClass("ulp-targets-global-message-danger").html("Something went wrong. We got unexpected server response.");
						jQuery(".ulp-targets-global-message").fadeIn(300);
						ulp_global_message_timer = setTimeout(function(){jQuery(".ulp-targets-global-message").fadeOut(300);}, 5000);
					});
				}
			}
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			ulp_targets_loading = false;
			if (ulp_save_requested) {
				ulp_save_requested = false;
				ulp_targets_save_list();
			} else {
				jQuery(".ulp-targets-global-message").fadeOut(300, function() {
					jQuery(".ulp-targets-global-message").attr("class", "ulp-targets-global-message");
					jQuery(".ulp-targets-global-message").addClass("ulp-targets-global-message-danger").html("Something went wrong. We got unexpected server response.");
					jQuery(".ulp-targets-global-message").fadeIn(300);
					ulp_global_message_timer = setTimeout(function(){jQuery(".ulp-targets-global-message").fadeOut(300);}, 5000);
				});
			}
		}
	});
	return false;
}
var ulp_targets_intro_step = -1;
function ulp_targets_intro_step_open(_step) {
	var viewport = {
		width: jQuery(window).width(),
		height: jQuery(window).height()
	};
	if (viewport.width < 720 || viewport.height < 540) return true;
	if (ulp_targets_intro_step >= 0) {
		var step = ulp_targets_intro_step;
		if (_step > ulp_targets_intro_step) jQuery("#ulp-targets-intro-"+ulp_targets_intro_step).attr("class", "ulp-targets-intro ulp-targets-intro-hide-left");
		else jQuery("#ulp-targets-intro-"+ulp_targets_intro_step).attr("class", "ulp-targets-intro ulp-targets-intro-hide-right");
		setTimeout(function(){
			jQuery("#ulp-targets-intro-"+step).hide();
		}, 500);
	}
	jQuery(".ulp-targets-intro-overlay").fadeIn(200);
	jQuery("#ulp-targets-intro-close").fadeIn(200);
	if (_step > ulp_targets_intro_step) jQuery("#ulp-targets-intro-"+_step).attr("class", "ulp-targets-intro ulp-targets-intro-show-left");
	else jQuery("#ulp-targets-intro-"+_step).attr("class", "ulp-targets-intro ulp-targets-intro-show-right");
	jQuery("#ulp-targets-intro-"+_step).show();
	ulp_targets_intro_step = _step;
	return false;
}
function ulp_targets_intro_step_close() {
	jQuery("#ulp-targets-intro-"+ulp_targets_intro_step).attr("class", "ulp-targets-intro ulp-targets-intro-hide-left");
	setTimeout(function(){
		jQuery(".ulp-targets-intro-overlay").fadeOut(200);
		jQuery("#ulp-targets-intro-close").fadeOut(200);
		jQuery("#ulp-targets-intro-"+ulp_targets_intro_step).hide();
		ulp_targets_intro_step = -1;
	}, 500);
	return false;
}
/* Advanced Targeting - 2017-04-10 - end */
function ulp_customfields_datetype_changed(object) {
	jQuery(object).parent().find("div").hide();
	if (jQuery(object).val() == 'date') {
		jQuery(object).parent().find(".ulp_customfields_minmaxdate_date").fadeIn(200);
	} else if (jQuery(object).val() == 'field') {
		jQuery(object).parent().find(".ulp_customfields_minmaxdate_field").fadeIn(200);
	}
}
function ulp_customfields_minmaxdate_options_set() {
	var fields = {};
	jQuery(".ulp-customfields-ids").each(function(){
		var field_id = jQuery(this).val();
		if (jQuery("#ulp_customfields_type_"+field_id).val() == 'date') fields[field_id] = jQuery("#ulp_customfields_name_"+field_id).val();
	});
	var min_html, max_html, min_value, max_value, min_selected, max_selected, min_selected_found, max_selected_found;
	for (var i in fields) { 
		if (fields.hasOwnProperty(i)) {
			min_html = "";
			max_html = "";
			min_value = jQuery("#ulp_customfields_mindate_field_"+i).attr("data-value");
			max_value = jQuery("#ulp_customfields_maxdate_field_"+i).attr("data-value");
			min_selected_found = false;
			max_selected_found = false;
			for (var j in fields) { 
				if (fields.hasOwnProperty(j)) {
					if (i != j) {
						if (j == min_value) {
							min_selected_found = true;
							min_selected = " selected='selected'";
						} else min_selected = "";
						min_html += "<option value='"+ulp_escape_html(j)+"'"+min_selected+">"+ulp_escape_html(j + " | " + fields[j])+"</option>";
						if (j == max_value) {
							max_selected_found = true;
							max_selected = " selected='selected'";
						} else max_selected = "";
						max_html += "<option value='"+ulp_escape_html(j)+"'"+max_selected+">"+ulp_escape_html(j + " | " + fields[j])+"</option>";
					}
				}
			}
			if (min_html == "") min_html = "<option>No date fields found</option>";
			if (max_html == "") max_html = "<option>No date fields found</option>";
			jQuery("#ulp_customfields_mindate_field_"+i).empty();
			jQuery("#ulp_customfields_mindate_field_"+i).append(min_html);
			jQuery("#ulp_customfields_maxdate_field_"+i).empty();
			jQuery("#ulp_customfields_maxdate_field_"+i).append(max_html);
			jQuery("#ulp_customfields_mindate_field_"+i).attr("data-value", jQuery("#ulp_customfields_mindate_field_"+i).val());
			jQuery("#ulp_customfields_maxdate_field_"+i).attr("data-value", jQuery("#ulp_customfields_maxdate_field_"+i).val());
			if (!min_selected_found && jQuery("#ulp_customfields_mindatetype_"+i).val() == 'field') {
				jQuery("#ulp_customfields_mindatetype_"+i).val("none");
				ulp_customfields_datetype_changed(jQuery("#ulp_customfields_mindatetype_"+i));
			}
			if (!max_selected_found && jQuery("#ulp_customfields_maxdatetype_"+i).val() == 'field') {
				jQuery("#ulp_customfields_maxdatetype_"+i).val("none");
				ulp_customfields_datetype_changed(jQuery("#ulp_customfields_maxdatetype_"+i));
			}
		}
	}
}
function ulp_customfields_minmaxdate_changed(object) {
	jQuery(object).attr("data-value", jQuery(object).val());
}
/* Modal Popup - begin */
var ulp_modal_buttons_disable = false;
function ulp_modal_open(_settings) {
	var settings = {
		width: 				480,
		height:				180,
		ok_label:			'Yes',
		cancel_label:		'Cancel',
		message:			'Do you really want to continue?',
		ok_function:		function() {ulp_modal_close();},
		cancel_function:	function() {ulp_modal_close();}
	}
	var objects = [settings, _settings],
    settings = objects.reduce(function (r, o) {
		Object.keys(o).forEach(function (k) {
			r[k] = o[k];
		});
		return r;
    }, {});
	
	ulp_modal_buttons_disable = false;
	jQuery(".ulp-modal-message").html(settings.message);
	jQuery(".ulp-modal").width(settings.width);
	jQuery(".ulp-modal").height(settings.height);
	jQuery(".ulp-modal-content").width(settings.width);
	jQuery(".ulp-modal-content").height(settings.height);
	jQuery(".ulp-modal-button").unbind("click");
	jQuery(".ulp-modal-button").removeClass("ulp-modal-button-disabled");
	jQuery("#ulp-modal-button-ok").find("label").html(settings.ok_label);
	jQuery("#ulp-modal-button-cancel").find("label").html(settings.cancel_label);
	jQuery("#ulp-modal-button-ok").bind("click", function(e){
		e.preventDefault();
		if (!ulp_modal_buttons_disable && typeof settings.ok_function == "function") {
			settings.ok_function();
		}
	});
	jQuery("#ulp-modal-button-cancel").bind("click", function(e){
		e.preventDefault();
		if (!ulp_modal_buttons_disable && typeof settings.cancel_function == "function") {
			settings.cancel_function();
		}
	});
	jQuery(".ulp-modal-overlay").fadeIn(300);
	jQuery(".ulp-modal").css({
		'top': 					'50%',
		'transform': 			'translate(-50%, -50%) scale(1)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(1)'
	});
}
function ulp_modal_close() {
	jQuery(".ulp-modal-overlay").fadeOut(300);
	jQuery(".ulp-modal").css({
		'transform': 			'translate(-50%, -50%) scale(0)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(0)'
	});
	setTimeout(function(){jQuery(".ulp-modal").css("top", "-3000px")}, 300);
}
/* Modal Popup - end */

function ulp_confirm_redirect(_object, _action) {
	var message, button_label;
	if (_action == "delete") {
		message = 'Please confirm that you want to delete the item.';
		button_label = 'Delete';
	} else if (_action == "delete-all") {
		message = 'Please confirm that you want to delete all items.';
		button_label = 'Delete';
	} else if (_action == "duplicate") {
		message = 'Please confirm that you want to duplicate the item.';
		button_label = 'Duplicate';
	} else if (_action == "reset-stats") {
		message = 'Please confirm that you want to drop counters.';
		button_label = 'Drop';
	} else {
		message = 'Please confirm that you want to perform this action.';
		button_label = 'Do';
	}
	ulp_modal_open({
		message:		message,
		ok_label:		button_label,
		ok_function:	function(e) {
			ulp_modal_close();
			location.href = jQuery(_object).attr("href");
		}
	});
	return false;
}
var ulp_ajax_multiselect_loading = false;
function ulp_ajax_multiselect_scroll(_object) {
	if (jQuery(_object).attr("data-more") != "on") return;
	var content_height = jQuery(_object).prop('scrollHeight');
	var position = jQuery(_object).scrollTop();
	var height = jQuery(_object).height();
	if (content_height - height - position < 20) {
		if (ulp_ajax_multiselect_loading) return;
		ulp_ajax_multiselect_loading = true;
		var post_data = {"action" : jQuery(_object).attr("data-action"), "start" : parseInt(jQuery(_object).attr("data-start"), 10), "range" : parseInt(jQuery(_object).attr("data-range"), 10)};
		var deps = jQuery(_object).attr("data-deps").split(",");
		for (var i=0; i<deps.length; i++) {
			post_data[deps[i]] = jQuery("input[name='"+deps[i]+"']").val();
		}
		jQuery(_object).find(".ulp-ajax-multiselect-loading").slideDown(300);
		jQuery.ajax({
			type	: "POST",
			url		: ulp_ajax_handler, 
			data	: post_data,
			success	: function(return_data) {
				jQuery(_object).find(".ulp-ajax-multiselect-loading").slideUp(300)
				var data;
				try {
					if (typeof return_data == "object") data = return_data;
					else data = jQuery.parseJSON(return_data);
					if (data.status == "OK") {
						jQuery(_object).find(".ulp-ajax-multiselect-loading").before(data.html);
						jQuery(_object).attr("data-more", data.more);
						jQuery(_object).attr("data-start", post_data.start + post_data.range);
					} else if (data.status == "ERROR") {
						jQuery(_object).attr("data-more", "off");
						ulp_global_message_show("danger", data.message);
					} else {
						jQuery(_object).attr("data-more", "off");
						ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
					}
				} catch(error) {
					jQuery(_object).attr("data-more", "off");
					ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
				}
				ulp_ajax_multiselect_loading = false;
			},
			error	: function(XMLHttpRequest, textStatus, errorThrown) {
				jQuery(_object).find(".ulp-ajax-multiselect-loading").slideUp(300)
				jQuery(_object).attr("data-more", "off");
				ulp_global_message_show("danger", "Something went wrong. We got unexpected server response.");
				ulp_ajax_multiselect_loading = false;
			}
		});
		
	}
}

function ulp_admin_popup_resize() {
	if (ulp_record_active) {
		var popup_height = 2*parseInt((jQuery(window).height() - 100)/2, 10);
		var popup_width = Math.min(Math.max(2*parseInt((jQuery(window).width() - 300)/2, 10), 640), 1080);
		jQuery("#ulp-admin-popup").height(popup_height);
		jQuery("#ulp-admin-popup").width(popup_width);
		jQuery("#ulp-admin-popup .ulp-admin-popup-inner").height(popup_height);
		jQuery("#ulp-admin-popup .ulp-admin-popup-content").height(popup_height - 52);
	}
}
function ulp_admin_popup_ready() {
	ulp_admin_popup_resize();
	jQuery(window).resize(function() {
		ulp_admin_popup_resize();
	});
}

var ulp_record_active = null;
function ulp_admin_popup_open(_object) {
	var action = jQuery(_object).attr("data-action");
	if (typeof action == typeof undefined) return false;
	jQuery("#ulp-admin-popup .ulp-admin-popup-content-form").html("");
	var window_height = 2*parseInt((jQuery(window).height() - 100)/2, 10);
	var window_width = Math.min(Math.max(2*parseInt((jQuery(window).width() - 300)/2, 10), 640), 1080);
	jQuery("#ulp-admin-popup").height(window_height);
	jQuery("#ulp-admin-popup").width(window_width);
	jQuery("#ulp-admin-popup .ulp-admin-popup-inner").height(window_height);
	jQuery("#ulp-admin-popup .ulp-admin-popup-content").height(window_height - 52);
	jQuery("#ulp-admin-popup-overlay").fadeIn(300);
	jQuery("#ulp-admin-popup").css({
		'top': 					'50%',
		'transform': 			'translate(-50%, -50%) scale(1)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(1)'
	});
	var title = jQuery(_object).attr("data-title");
	if (typeof title != typeof undefined) jQuery("#ulp-admin-popup .ulp-admin-popup-title h3 label").html(title);
	var subtitle = jQuery(_object).attr("data-subtitle");
	if (typeof subtitle != typeof undefined) jQuery("#ulp-admin-popup .ulp-admin-popup-title h3 span").html(subtitle);
	jQuery("#ulp-admin-popup .ulp-admin-popup-loading").show();
	ulp_record_active = jQuery(_object).attr("data-id");
	var post_data = {"action" : action, "id" : ulp_record_active};
	jQuery.ajax({
		type	: "POST",
		url		: ulp_ajax_handler, 
		data	: post_data,
		success	: function(return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery("#ulp-admin-popup .ulp-admin-popup-content-form").html(data.html);
					jQuery("#ulp-admin-popup .ulp-admin-popup-loading").hide();
				} else if (data.status == "ERROR") {
					ulp_admin_popup_close();
					ulp_global_message_show("danger", data.message);
				} else {
					ulp_admin_popup_close();
					ulp_global_message_show("danger", ulp_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch(error) {
				ulp_admin_popup_close();
				ulp_global_message_show("danger", ulp_esc_html__("Something went wrong. We got unexpected server response."));
			}
		},
		error	: function(XMLHttpRequest, textStatus, errorThrown) {
			ulp_admin_popup_close();
			ulp_global_message_show("danger", ulp_esc_html__("Something went wrong. We got unexpected server response."));
		}
	});

	return false;
}
function ulp_admin_popup_close() {
	jQuery("#ulp-admin-popup-overlay").fadeOut(300);
	jQuery("#ulp-admin-popup").css({
		'transform': 			'translate(-50%, -50%) scale(0)',
		'-webkit-transform': 	'translate(-50%, -50%) scale(0)'
	});
	ulp_record_active = null;
	setTimeout(function(){jQuery("#ulp-admin-popup .ulp-admin-popup-content-form").html(""); jQuery("#ulp-admin-popup").css("top", "-3000px")}, 1000);
}
function ulp_esc_html__(_string) {
	var string;
	if (typeof ulp_translations == typeof {} && ulp_translations.hasOwnProperty(_string)) {
		string = ulp_translations[_string];
		if (string.length == 0) string = _string;
	} else string = _string;
	return ulp_escape_html(string);
}
function ulp_escape_html(_text) {
	if (!_text) return "";
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return _text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
