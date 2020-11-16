<?php
class tgm_promoPps extends modulePps {
	public function init() {
		parent::init();
		// Closed for now
		/*if(is_admin()) {
			require_once($this->getModDir(). 'classes'. DS. 'class-tgm-plugin-activation.php');
			add_action('tgmpa_register', array($this, 'registerPromo'));
		}*/
	}
	
	/*public function registerPromo() {
		// For new users only
		if(!installerPps::isNewUser())
			return;
		if(function_exists('addplus_fs')) {
			if(framePps::_()->getModule('options')->get('addendio_promo')) {
				framePps::_()->getModule('options')->getModel()->save('addendio_promo', 0);
				framePps::_()->getModule('supsystic_promo')->getModel()->bigStatAdd('addendio_promo');
			}
			return;
		}
		if(!framePps::_()->getModule('options')->get('addendio_promo'))
			framePps::_()->getModule('options')->getModel()->save('addendio_promo', 1);
		$plugins = array(
			array(
				'name'      => 'Addendio PLUS',
				'slug'      => 'addendio-plus',
				'source'    => 'https://assets.addendio.com/addendio-plus/assets/addendio-plus.zip',
			),
		);
		$config = array(
			'id'           => 'pps_lng',                 // Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => '',                      // Default absolute path to bundled plugins.
			'menu'         => 'tgmpa-install-plugins', // Menu slug.
			'parent_slug'  => 'plugins.php',            // Parent menu slug.
			'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => false,                   // Automatically activate plugins after installation or not.
			'message'      => '',                      // Message to output right before the plugins table.
		);

		tgmpa( $plugins, $config );
	}*/
}