<div id="supsystic-admin-tour" class="">
	<div id="supsystic-welcome-first_welcome">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Welcome to %s plugin!', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('Thank you for choosing our %s plugin. Just click here to start using it - and we will show you it\'s possibilities and powerful features.', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="<?php echo framePps::_()->getModule('options')->getTabUrl('popup');?>" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-create_first-create_bar_btn">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Create your firs PopUp', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('Click on "Add New PopUp" button to create your firs PopUp. Just try - this is really simple!', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="<?php echo framePps::_()->getModule('options')->getTabUrl('popup_add_new');?>" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-create_first-enter_title">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Enter name for your PopUp', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('This will be name of your PopUp. You can change it latter.', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="_ppsOpenPointer('create_first', 'select_tpl'); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-create_first-select_tpl">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Selecte template for your PopUp', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('Choose any templates from this list. You will be able to customize it after creation, and also - you will be able to change it latter if you will need this.', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="_ppsOpenPointer('create_first', 'save_first_popup'); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-create_first-save_first_popup">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Save first PopUp', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('After you entered name of your PopUp and selected it\'s template - just save it, and you will be redirected to PopUp edit screen - where you will be able to customize your PopUp.', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="jQuery('#ppsCreatePopupForm').submit(); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-first_edit-popup_main_opts">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Main Settings', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('Here you can setup main display settings for your PopUp - when it should be visible for your user, when it need to be closed, if required - select specific pages/posts where you need to show your PopUp.', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="_ppsOpenPointerAndPopupTab('first_edit', 'popup_design_opts', '#ppsPopupTpl'); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-first_edit-popup_design_opts">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Design Settings', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('One of our most powerful features - possibility to <strong>customize</strong> design for each PopUp window for your needs. In this section you can select your PopUp colors and images, enter required texts that will describe your needs for your visitors, setup social settings (if required), select PopUp location, and in the end - select Animation style for your PopUp from list of more then 20 different animation styles!', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="_ppsOpenPointerAndPopupTab('first_edit', 'popup_subscribe_opts', '#ppsPopupSubscribe'); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-first_edit-popup_subscribe_opts">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Subscribe Settings', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('Setup your Subscription settings here - select Subscribers destination in "Subscribe to" option - it allow to flow your subscribers not only to WordPress Users, but to other popular subscribe services. With other subscription options you will be able to easily customize your subscribe form in PopUp window.', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="_ppsOpenPointerAndPopupTab('first_edit', 'popup_statistics_opts', '#ppsPopupStatistics'); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-first_edit-popup_statistics_opts">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('PopUp Statistics', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('After you will setup your PopUp - it will start displaying to your site visitors. And now - you need to check it\'s displaying statistics. Here, in Statistics tab, you will be able to see how many times PopUp was shown to your visitors, how many times visitors subscribed to it (if subscription is enabled), how many times visitors shared your site using Social Share PopUp functionality and what social networks for share is most popular (if it was enabled). If you will use AB Testing feature to increase your site popularity - you will see here all your main and tested PopUps statistics - in one graph or diagramm, - and this will provide you with all required information about your POpUp popularity!', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="_ppsOpenPointerAndPopupTab('first_edit', 'popup_code_opts', '#ppsPopupEditors'); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-first_edit-popup_code_opts">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('PopUp CSS / HTML Code', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('In case you will need modify source CSS / HTML code of your PopUp - you can easily do this here. Just make sure that you know what you are doing - don\'t break PopUp. You can also find additional information about editing source code <a href="%s" target="_blank">here</a>.', PPS_LANG_CODE), 'http://supsystic.com/edit-popup-html-css-code/')?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="close"><?php _e('Close', PPS_LANG_CODE)?></a>
			<a href="#" onclick="_ppsOpenPointer('first_edit', 'final'); return false;" class="button button-primary supsystic-tour-next-btn"><?php _e('Next', PPS_LANG_CODE)?></a>
		</div>
	</div>
	<div id="supsystic-first_edit-final">
		<div class="supsystic-tour-content">
			<h3><?php printf(__('Well Done!', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME)?></h3>
			<p><?php printf(__('<b>Upgrading</b> <br>Once you have purchased Premium version of plugin  - you’ll have to enter license key (you can find it in your personal account on our site). Go to the License tab and enter your email and license key. Once you have activated your PRO license - you can use all its advanced options. <br><br>That\'s it! Now you know how to use our %s. Just save your PopUp after you will setup it - and you will see results. You can also check our site - <a href="%s" target="_blank">supsystic.com</a> to find out more about our %s plugin. If you will have any questions - you can always contact us on <a href="%s" target="_blank">WordPress plugin forum</a> or in <a href="%s" target="_blank">our support system</a>. Besides you can always describe your questions on <a href="%s" target="_blank">Supsystic Forum.</a> <br><br><b>Enjoy this plugin?</b> <br>It will be nice if you`ll help us and boost plugin with <a href="%s" target="_blank">Five Stars rating on WordPress.org.</a>  <br><br>We really hope that our solution will be helpful for you. Good luck!', PPS_LANG_CODE), PPS_WP_PLUGIN_NAME, $this->finishSiteLink, PPS_WP_PLUGIN_NAME, 'https://wordpress.org/support/plugin/popup-by-supsystic', 'https://supsystic.com/contact-us/', 'http://supsystic.com/forum/popup-plugin/', 'https://wordpress.org/support/view/plugin-reviews/popup-by-supsystic?rate=5#postform/', $this->contactFormLink)?></p>
		</div>
		<div class="supsystic-tour-btns">
			<a href="#" class="button-primary supsystic-tour-finish-btn"><?php _e('Finish', PPS_LANG_CODE)?></a>
		</div>
	</div>
</div>