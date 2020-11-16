jQuery(document).ready(function($) {
	tinymce.create('tinymce.plugins.ulp_plugin', {
		init: function(ed, url) {
			ed.addCommand('ulp_handle_tinymce_button', function() {
				ed.windowManager.open({
					title : 'Layered Popups Shortcodes',
					file : ajaxurl + '?action=ulp_handle_tinymce_button',
					width : 380,
					height : 190,
					inline : 1
				}, {
					plugin_url : url,
					selected: tinyMCE.activeEditor.selection.getContent()
				});
			});
			ed.addButton('ulp_button', {
			title : 'Layered Popups Shortcodes', 
			cmd : 'ulp_handle_tinymce_button', 
			image: url + '/../images/tinymce-button.png'
			});
		}
    });
    tinymce.PluginManager.add('ulp_button', tinymce.plugins.ulp_plugin);
});