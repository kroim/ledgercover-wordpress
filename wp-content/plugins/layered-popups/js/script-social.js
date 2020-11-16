function ulp_subscribe_facebook(object) {
	if (typeof FB != 'undefined' && ulp_facebook_initialized) {
		FB.login(function(response) {
			if (response.status === 'connected') {
				FB.api('/me?fields=name,email', function(response) {
					if (typeof response.email != 'undefined') {
						var name = "";
						if (typeof response.name != 'undefined') name = response.name;
						ulp_subscribe_social(object, 'facebook', response.email, name)
					} else {
						console.log(response);
					}
				});
			} else if (response.status === 'not_authorized') {
				console.log(response);
			} else {
				console.log(response);
			}
		}, {scope: 'public_profile,email', auth_type: 'rerequest'});
	}
	return false;
}
function ulp_google_load() {
	gapi.client.setApiKey(ulp_google_apikey);
}
function ulp_subscribe_google(object) {
	if (typeof gapi != 'undefined') {
		gapi.auth.authorize({client_id: ulp_google_clientid, scope: "https://www.googleapis.com/auth/userinfo.profile", immediate: false}, function(authResult) {
			if (authResult && !authResult.error) {
				gapi.client.load('oauth2', 'v2', function() {
					var request = gapi.client.oauth2.userinfo.get();
					request.execute(function(resp) {
						if (typeof resp.email != 'undefined') {
							var name = "";
							if (typeof resp.name != 'undefined') name = resp.name;
							ulp_subscribe_social(object, 'google', resp.email, name)
						} else {
							console.log(resp);
						}
					});
				});
			} else {
				console.log(authResult);
			}
		});
	}
}
function ulp_subscribe_social(object, source, email, name) {
	if (ulp_subscribing) return false;
	ulp_subscribing = true;
	
	var inline_id = jQuery(object).parents(".ulp-inline-window").attr("id");
	if (inline_id) {
		_ulp_inline_subscribe_social(inline_id, object, source, email, name);
		return false;
	}
	
	var button_icon_loading = "";
	var button_icon = jQuery(object).attr("data-fa");
	if (!button_icon) button_icon = "";
	else {
		button_icon = "<i class='"+button_icon+"'></i>&nbsp; ";
		button_icon_loading = "<i class='fas fa-spinner fa-spin'></i>&nbsp; ";
	}
	
	var button_label = jQuery(object).attr("data-label");
	var button_label_loading = jQuery(object).attr("data-loading");
	if (!button_label_loading || button_label_loading == "") button_label_loading = "Loading...";
	jQuery(object).html(button_icon_loading+button_label_loading);
	jQuery.ajax({
		url:		ulp_ajax_url,
		data:		{
						"ulp-name" : name,
						"ulp-email" : email,
						"ulp-phone" : "",
						"ulp-message" : "",
						"ulp-popup" : ulp_active_window_id,
						"ulp-campaign" : ulp_active_campaign,
						"action" : "ulp_subscribe"
					},
		type:		"POST",
		async:		true,
		success:	function(return_data) {
			var data;
			jQuery(object).html(button_icon+button_label);
			try {
				if (typeof return_data == 'object') {
					data = return_data;
				} else {
					data = jQuery.parseJSON(return_data);
				}
				var status = data.status;
				if (status == "OK") {
					var cookie_lifetime = 180;
					if (typeof data.cookie_lifetime != 'undefined') cookie_lifetime = parseInt(data.cookie_lifetime, 10);
					if (cookie_lifetime > 0) {
						if (ulp_active_window_id == ulp_onload_popup || (ulp_active_campaign == ulp_onload_popup && ulp_onload_popup != "") || (ulp_onexit_limits == "on" && ulp_onload_popup != "")) {
							ulp_write_cookie("ulp-onload-"+ulp_onload_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onload_displayed = true;
						}
						if (ulp_active_window_id == ulp_onexit_popup || (ulp_active_campaign == ulp_onexit_popup && ulp_onexit_popup != "") || (ulp_onexit_limits == "on" && ulp_onexit_popup != "")) {
							ulp_write_cookie("ulp-onexit-"+ulp_onexit_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onexit_displayed = true;
						}
						if (ulp_active_window_id == ulp_onscroll_popup || (ulp_active_campaign == ulp_onscroll_popup && ulp_onscroll_popup != "") || (ulp_onexit_limits == "on" && ulp_onscroll_popup != "")) {
							ulp_write_cookie("ulp-onscroll-"+ulp_onscroll_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onscroll_displayed = true;
						}
						if (ulp_active_window_id == ulp_onidle_popup || (ulp_active_campaign == ulp_onidle_popup && ulp_onidle_popup != "") || (ulp_onexit_limits == "on" && ulp_onidle_popup != "")) {
							ulp_write_cookie("ulp-onidle-"+ulp_onidle_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onidle_displayed = true;
						}
						if (ulp_active_window_id == ulp_onabd_popup || (ulp_active_campaign == ulp_onabd_popup && ulp_onabd_popup != "") || (ulp_onexit_limits == "on" && ulp_onabd_popup != "")) {
							ulp_write_cookie("ulp-onabd-"+ulp_onabd_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onabd_displayed = true;
						}
						ulp_write_cookie("ulp-inline-"+ulp_active_window_id, ulp_cookie_value, cookie_lifetime);
						ulp_write_cookie("ulp-"+ulp_active_window_id, ulp_cookie_value, cookie_lifetime); // linklocker
					}
					if (typeof data.forms != 'undefined') {
						var forms = data.forms;
						for (var key in forms){
							if (forms.hasOwnProperty(key)) {
								jQuery('body').append(forms[key]);
								jQuery("#submit-"+key).click();
							}
						}	
					}
					ulp_track(ulp_active_window_id, "layered-popup", "subscribe-"+source, email);
					_ulp_open(ulp_active_window_id, false, false);
					var close_delay = 0;
					if (data.close_delay) close_delay = data.close_delay;
					ulp_unlock_links(ulp_active_window_id); // linklocker
					var redirect_url = data.return_url;
					if (ulp_forced_location.length > 0) redirect_url = ulp_forced_location; // linklocker
					var thanksgiving_popup = data.thanksgiving_popup;
					setTimeout(function() {
						if (redirect_url.length > 0) {
							ulp_self_close();
							ulp_subscribing = false;
							if (redirect_url == '#refresh') location.reload(true);
							else location.href = redirect_url;
						} else if (thanksgiving_popup.length > 0) {
							ulp_subscribing = false;
							ulp_open(thanksgiving_popup);
						} else {
							ulp_self_close();
							ulp_subscribing = false;
						}
					}, close_delay);
				} else if (status == "ERROR") {
					ulp_subscribing = false;
					console.log(return_data);
				} else {
					ulp_subscribing = false;
					console.log(return_data);
				}
			} catch(error) {
				ulp_subscribing = false;
				console.log(return_data);
			}
		}
	});
	return false;
}
function _ulp_inline_subscribe_social(inline_id, object, source, email, name) {
	var inline_popup_id = jQuery("#"+inline_id).attr("data-id");
	var button_icon_loading = "";
	var button_icon = jQuery(object).attr("data-fa");
	if (!button_icon) button_icon = "";
	else {
		button_icon = "<i class='"+button_icon+"'></i>&nbsp; ";
		button_icon_loading = "<i class='fas fa-spinner fa-spin'></i>&nbsp; ";
	}
	
	var button_label = jQuery(object).attr("data-label");
	var button_label_loading = jQuery(object).attr("data-loading");
	if (!button_label_loading || button_label_loading == "") button_label_loading = "Loading...";
	jQuery(object).html(button_icon_loading+button_label_loading);
	jQuery.ajax({
		url:		ulp_ajax_url,
		data:		{
						"ulp-name" : name,
						"ulp-email" : email,
						"ulp-phone" : "",
						"ulp-message" : "",
						"ulp-popup" : inline_popup_id,
						"ulp-campaign" : "",
						"action" : "ulp_subscribe"
					},
		type:		"POST",
		async:		true,
		success:	function(return_data) {
			ulp_subscribing = false;
			var data;
			jQuery(object).html(button_icon+button_label);
			try {
				if (typeof return_data == 'object') {
					data = return_data;
				} else {
					data = jQuery.parseJSON(return_data);
				}
				var status = data.status;
				if (status == "OK") {
					var cookie_lifetime = 180;
					if (typeof data.cookie_lifetime != 'undefined') cookie_lifetime = parseInt(data.cookie_lifetime, 10);
					if (cookie_lifetime > 0) {
						if (inline_popup_id == ulp_onload_popup || (ulp_onexit_limits == "on" && ulp_onload_popup != "")) {
							ulp_write_cookie("ulp-onload-"+ulp_onload_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onload_displayed = true;
						}
						if (inline_popup_id == ulp_onexit_popup || (ulp_onexit_limits == "on" && ulp_onexit_popup != "")) {
							ulp_write_cookie("ulp-onexit-"+ulp_onexit_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onexit_displayed = true;
						}
						if (inline_popup_id == ulp_onscroll_popup || (ulp_onexit_limits == "on" && ulp_onscroll_popup != "")) {
							ulp_write_cookie("ulp-onscroll-"+ulp_onscroll_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onscroll_displayed = true;
						}
						if (inline_popup_id == ulp_onidle_popup || (ulp_onexit_limits == "on" && ulp_onidle_popup != "")) {
							ulp_write_cookie("ulp-onidle-"+ulp_onidle_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onidle_displayed = true;
						}
						if (inline_popup_id == ulp_onabd_popup || (ulp_onexit_limits == "on" && ulp_onabd_popup != "")) {
							ulp_write_cookie("ulp-onabd-"+ulp_onabd_popup, ulp_cookie_value, cookie_lifetime);
							ulp_onabd_displayed = true;
						}
						ulp_write_cookie("ulp-inline-"+inline_popup_id, ulp_cookie_value, cookie_lifetime);
						ulp_write_cookie("ulp-"+inline_popup_id, ulp_cookie_value, cookie_lifetime); // linklocker
					}
					if (typeof data.forms != 'undefined') {
						var forms = data.forms;
						for (var key in forms){
							if (forms.hasOwnProperty(key)) {
								jQuery('body').append(forms[key]);
								jQuery("#submit-"+key).click();
							}
						}	
					}
					ulp_track(inline_id, "layered-inline", "subscribe-"+source, email);
					_ulp_inline_open(inline_id, false, true);
					if (typeof ulp_inline_subscribed == 'function') { 
						ulp_inline_subscribed(inline_id, data);
					}
					ulp_unlock_links(inline_popup_id); // linklocker
					var redirect_url = data.return_url;
					var close_delay = 0;
					if (data.close_delay) close_delay = parseInt(data.close_delay, 10);
					var thanksgiving_popup = data.thanksgiving_popup;
					setTimeout(function() {
						_ulp_inline_hide_confirmation(inline_id);
						if (redirect_url.length > 0) {
							if (redirect_url == '#refresh') location.reload(true);
							else location.href = redirect_url;
						}
						if (thanksgiving_popup.length > 0) ulp_open(thanksgiving_popup);
					}, close_delay);
				} else if (status == "ERROR") {
					console.log(return_data);
				} else {
					console.log(return_data);
				}
			} catch(error) {
				console.log(return_data);
			}
		}
	});
	return false;
}