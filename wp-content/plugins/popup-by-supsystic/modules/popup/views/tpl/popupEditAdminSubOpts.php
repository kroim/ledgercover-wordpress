<div class="ppsPopupOptRow">
	<label>
		<?php echo htmlPps::checkbox('params[tpl][enb_subscribe]', array(
			'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_subscribe'),
			'attrs' => 'data-switch-block="subShell"',
		))?>
		<?php  _e('Enable Subscription', PPS_LANG_CODE)?>
	</label>
</div>
<span data-block-to-switch="subShell">
	<table class="form-table ppsSubShellMainTbl" style="width: auto;">
		<tr>
			<th scope="row">
				<?php _e('Subscribe to', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Destination for your Subscribers.', PPS_LANG_CODE))?>"></i>
			</th>
			<td>
				<?php echo htmlPps::selectbox('params[tpl][sub_dest]', array(
					'options' => $this->subDestListForSelect, 
					'value' => (isset($this->popup['params']['tpl']['sub_dest']) ? $this->popup['params']['tpl']['sub_dest'] : '')))?>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_wordpress">
			<th scope="row">
				<?php _e('Create user with the chosen role after subscribing', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Use this only if you really need it. Remember! After you change this option - your new subscriber will have more privileges than usual subscribers, so be careful with this option!', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::selectbox('params[tpl][sub_wp_create_user_role]', array(
					'options' => $this->availableUserRoles,
					'value' => (isset($this->popup['params']['tpl']['sub_wp_create_user_role']) ? $this->popup['params']['tpl']['sub_wp_create_user_role'] : 'subscriber')))?>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_wordpress">
			<th scope="row">
				<?php _e('Create Subscriber without confirmation', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Usually, after user subscribes, we send an email with the confirmation link - to confirm the email address, and only after user clicks on the link from this email - we will create a new subscriber. This option allows you to create a subscriber - right after subscription, without the email confirmation process.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::checkbox('params[tpl][sub_ignore_confirm]', array(
					'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_ignore_confirm')))?>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_wordpress">
			<th scope="row">
				<?php _e('Confirmation page reload time', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Time that require to Confirm page to be realoaded and redirect user to your site, in seconds. Usually - 10 seconds.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_confirm_reload_time]', array(
					'value' => isset($this->popup['params']['tpl']['sub_confirm_reload_time']) ? $this->popup['params']['tpl']['sub_confirm_reload_time'] : 10))?>(sec)
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_wordpress">
			<th scope="row">
				<?php _e('Export Subscribers', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Export all subscribers, who subscribed using WordPress "Subscribe to" method, as CSV file.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<a href="<?php echo $this->wpCsvExportUrl;?>" class="button"><?php _e('Get CSV List', PPS_LANG_CODE)?></a>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_aweber">
			<th scope="row">
				<?php _e('Aweber Unique List ID', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('Check <a href="%s" target="_blank">this page</a> for more details', PPS_LANG_CODE), 'https://help.aweber.com/hc/en-us/articles/204028426-What-Is-The-Unique-List-ID-'))?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_aweber_listname]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_aweber_listname']) ? $this->popup['params']['tpl']['sub_aweber_listname'] : '')))?>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_aweber">
			<th scope="row">
				<?php _e('Aweber AD Tracking', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('You can easy track your subscribers from PopUp using this feature. For more info - check <a href="%s" target="_blank">this page</a>.', PPS_LANG_CODE), 'https://help.aweber.com/hc/en-us/articles/204028856-Where-Can-I-See-My-Subscribers-Ad-Tracking-Categories-'))?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_aweber_adtracking]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_aweber_adtracking']) ? $this->popup['params']['tpl']['sub_aweber_adtracking'] : '')))?>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_mailchimp">
			<th scope="row">
				<?php _e('MailChimp API key', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('To find your MailChimp API Key login to your mailchimp account at <a href="%s" target="_blank">%s</a> then from the left main menu, click on your Username, then select "Account" in the flyout menu. From the account page select "Extras", "API Keys". Your API Key will be listed in the table labeled "Your API Keys". Copy / Paste your API key into "MailChimp API key" field here. For more detailed instruction - check article <a href="%s" target="_blank">here</a>.', PPS_LANG_CODE), 'http://mailchimp.com', 'http://mailchimp.com', 'https://supsystic.com/mailchimp-integration/'))?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_mailchimp_api_key]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_mailchimp_api_key']) ? $this->popup['params']['tpl']['sub_mailchimp_api_key'] : ''),
					'attrs' => 'style="min-width: 300px;"'))?>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_mailchimp">
			<th scope="row">
				<?php _e('Lists for subscribe', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip-bottom" title="<?php _e('Select lists for subscribe. They are taken from your MailChimp account - so make sure that you entered correct API key before.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<div id="ppsMailchimpListsShell" style="display: none;">
					<?php echo htmlPps::selectlist('params[tpl][sub_mailchimp_lists]', array(
						'value' => (isset($this->popup['params']['tpl']['sub_mailchimp_lists']) ? $this->popup['params']['tpl']['sub_mailchimp_lists'] : ''),
						'attrs' => 'id="ppsMailchimpLists" class="chosen" data-placeholder="'. __('Choose Lists', PPS_LANG_CODE). '"',
					))?>
				</div>
				<span id="ppsMailchimpNoApiKey"><?php _e('Enter API key - and your list will appear here', PPS_LANG_CODE)?></span>
				<span id="ppsMailchimpMsg"></span>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_mailchimp">
			<th scope="row">
				<?php _e('Disable double opt-in', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo _e('Disable double opt-in confirmation message sending - will create subscriber directly after he will sign-up to your form.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::checkbox('params[tpl][sub_dsbl_dbl_opt_id]', array(
					'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_dsbl_dbl_opt_id')))?><br />
				
				<label id="ppsSubMcSendWelcome">
					<?php echo htmlPps::checkbox('params[tpl][sub_mc_enb_welcome]', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_mc_enb_welcome')))?>
					<?php _e('Send MailChimp Welcome Email', PPS_LANG_CODE)?>&nbsp;
					<i style="float: none;" class="fa fa-question supsystic-tooltip" title="<?php echo _e('If double opt-in is disable - there will be no Welcome email from MailChimp by default. But if you still need it - just enable this opton, and Welcome email from MailChimp will be sent to your user even in this case.', PPS_LANG_CODE)?>"></i>
				</label>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_mailchimp">
			<th scope="row">
				<?php _e('Group for subscribe', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip-bottom" title="<?php _e('In MailChimp there are possibility to select groups for your subscribers. This is not mandatory, but some times is really helpful. So, we added this possibility for you in our plugin too - hope you will like it!', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<div id="ppsMailchimpGroupsShell" style="display: none;">
					<?php echo htmlPps::selectlist('params[tpl][sub_mailchimp_groups]', array(
						'value' => (isset($this->popup['params']['tpl']['sub_mailchimp_groups']) ? $this->popup['params']['tpl']['sub_mailchimp_groups'] : ''),
						'attrs' => 'id="ppsMailchimpGroups" class="chosen" data-placeholder="'. __('Choose Groups', PPS_LANG_CODE). '"',
					))?>
				</div>
				<span id="ppsMailchimpGroupsNoApiKey"><?php _e('Enter API key, select List - and your groups will appear here', PPS_LANG_CODE)?></span>
				<span id="ppsMailchimpGroupsMsg"></span>
				<?php echo htmlPps::hidden('params[tpl][sub_mailchimp_groups_full]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_mailchimp_groups_full']) ? $this->popup['params']['tpl']['sub_mailchimp_groups_full'] : ''),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_mailpoet">
			<?php if($this->mailPoetAvailable) { ?>
				<th scope="row">
					<?php _e('MailPoet Subscribe Lists', PPS_LANG_CODE)?>
				</th>
				<td>
					<?php if(!empty($this->mailPoetListsSelect)) { ?>
						<?php echo htmlPps::selectbox('params[tpl][sub_mailpoet_list]', array(
							'value' => (isset($this->popup['params']['tpl']['sub_mailpoet_list']) ? $this->popup['params']['tpl']['sub_mailpoet_list'] : ''),
							'options' => $this->mailPoetListsSelect,
							/*'attrs' => 'style="min-width: 300px;"'*/))?>
					<?php } else { ?>
						<div class="description"><?php printf(__('You have no subscribe lists, <a target="_blank" href="%s">create lists</a> at first, then - select them here.', PPS_LANG_CODE), admin_url('admin.php?page=wysija_subscribers&action=addlist'))?></div>
					<?php }?>
				</td>
			<?php } else { ?>
				<th scope="row" colspan="2">
					<div class="description"><?php printf(__('To use this subscribe engine - you must have <a target="_blank" href="%s">MailPoet plugin</a> installed on your site', PPS_LANG_CODE), admin_url('plugin-install.php?tab=search&s=MailPoet'))?></div>
				</th>
			<?php }?>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_supsystic">
			<?php if($this->supNewsletterAvailable) { ?>
				<th scope="row">
					<?php _e('Supsystic Subscribe Lists', PPS_LANG_CODE)?>
				</th>
				<td>
					<?php if(!empty($this->supNewsletterListsSelect)) { ?>
						<?php echo htmlPps::selectbox('params[tpl][sub_supsystic_list]', array(
							'value' => (isset($this->popup['params']['tpl']['sub_supsystic_list']) ? $this->popup['params']['tpl']['sub_supsystic_list'] : ''),
							'options' => $this->supNewsletterListsSelect))?>
					<?php } else { ?>
						<div class="description"><?php printf(__('You have no subscribe lists, <a target="_blank" href="%s">create lists</a> at first, then - select them here.', PPS_LANG_CODE), frameNbs::_()->getModule('options')->getTabUrl('subscribers_lists', 'nbsAddSubList'))?></div>
					<?php }?>
				</td>
			<?php } else { ?>
				<th scope="row" colspan="2">
					<div class="description"><?php printf(__('To use this subscribe engine - you must have <a target="_blank" href="%s">Newsletter by Supsystic plugin</a> installed on your site', PPS_LANG_CODE), admin_url('plugin-install.php?tab=search&s=Newsletter+by+Supsystic'))?></div>
				</th>
			<?php }?>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_supsystic">
			<?php if($this->supNewsletterAvailable) { ?>
				<th scope="row">
					<?php _e('Create subscriber Disabled', PPS_LANG_CODE)?>
				</th>
				<td>
					<?php echo htmlPps::checkbox('params[tpl][sub_sup_dsbl]', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_sup_dsbl')))?>
				</td>
			<?php }?>
		</tr>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_supsystic">
			<?php if($this->supNewsletterAvailable) { ?>
				<th scope="row">
					<?php _e('Send Confirm email', PPS_LANG_CODE)?>
				</th>
				<td>
					<?php echo htmlPps::checkbox('params[tpl][sub_sup_send_confirm]', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_sup_send_confirm')))?>
				</td>
			<?php }?>
		</tr>
		<?php /*?><tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_newsletter">
			<?php if($this->newsletterAvailable) { ?>
				<th scope="row">
					<?php _e('Newsletter Subscribe Lists', PPS_LANG_CODE)?>
				</th>
				<td>
					<?php if(!empty($this->newsletterListsSelect)) { ?>
						<?php echo htmlPps::selectbox('params[tpl][sub_newsletter_list]', array(
							'value' => (isset($this->popup['params']['tpl']['sub_newsletter_list']) ? $this->popup['params']['tpl']['sub_newsletter_list'] : ''),
							'options' => $this->newsletterListsSelect))?>
					<?php } else { ?>
						<div class="description"><?php printf(__('You have no subscribe lists, <a target="_blank" href="%s">create lists</a> at first, then - select them here.', PPS_LANG_CODE), admin_url('admin.php?page=wysija_subscribers&action=addlist'))?></div>
					<?php }?>
				</td>
			<?php } else { ?>
				<th scope="row" colspan="2">
					<div class="description"><?php printf(__('To use this subscribe engine - you must have <a target="_blank" href="%s">Newsletter plugin</a> installed on your site', PPS_LANG_CODE), admin_url('plugin-install.php?tab=search&s=Newsletter'))?></div>
				</th>
			<?php }?>
		</tr><?php */?>
		<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_jetpack">
			<?php if(!$this->jetpackAvailable) { ?>
				<th scope="row" colspan="2">
					<div class="description"><?php printf(__('To use this subscribe engine - you must have <a target="_blank" href="%s">Jetpack plugin</a> installed on your site', PPS_LANG_CODE), admin_url('plugin-install.php?tab=search&s=Jetpack'))?></div>
				</th>
			<?php }?>
		</tr>
		<?php
			$proSubModules = array(
				'constantcontact' => array('label' => __('Constant Contact', PPS_LANG_CODE)), 
				'campaignmonitor' => array('label' => __('Campaign Monitor', PPS_LANG_CODE)), 
				'sendgrid' => array('label' => __('SendGrid', PPS_LANG_CODE)),
				'get_response' => array('label' => __('GetResponse', PPS_LANG_CODE)),
				'icontact' => array('label' => __('iContact', PPS_LANG_CODE)),
				'verticalresponse' => array('label' => __('Vertical Response', PPS_LANG_CODE)),
				'activecampaign' => array('label' => __('Active Campaign', PPS_LANG_CODE)),
				'infusionsoft' => array('label' => __('Infusion Soft', PPS_LANG_CODE)),	// Not ready for production - too much questions about their API service
				'mailrelay'=> array('label' => __('Mailrelay', PPS_LANG_CODE)),
				'arpreach' => array('label' => __('arpReach', PPS_LANG_CODE)),
				'sgautorepondeur' => array('label' => __('SG Autorepondeur', PPS_LANG_CODE)),
				'benchmarkemail' => array('label' => __('Benchmark', PPS_LANG_CODE)),
				'salesforce' => array('label' => __('SalesForce - Web-to-Lead', PPS_LANG_CODE)),
				'convertkit' => array('label' => __('ConvertKit', PPS_LANG_CODE)),
				'myemma' => array('label' => __('Emma', PPS_LANG_CODE)),
				'sendinblue' => array('label' => __('SendinBlue', PPS_LANG_CODE)),
				'vision6' => array('label' => __('Vision6', PPS_LANG_CODE)),
				'vtiger' => array('label' => __('Vtiger', PPS_LANG_CODE)),
				'ymlp' => array('label' => __('Your Mailing List Provider (Ymlp)', PPS_LANG_CODE)),
				'fourdem' => array('label' => __('4Dem.it', PPS_LANG_CODE)),
				'dotmailer' => array('label' => __('Dotmailer', PPS_LANG_CODE)),
				'madmimi' => array('label' => __('Mad Mimi', PPS_LANG_CODE)),
			);
		?>
		<script type="text/javascript">
			var g_ppsProSubMethods = <?php echo utilsPps::jsonEncode($proSubModules)?>;
		</script>
		<?php foreach($proSubModules as $proSubMod => $proSubModData) { ?>
			<?php if($this->isPro && framePps::_()->getModule( $proSubMod )) {?>
				<?php echo framePps::_()->getModule( $proSubMod )->getView()->generateAdminFields( $this->popup )?>
			<?php } elseif(($this->isPro && !framePps::_()->getModule( $proSubMod )) || (!$this->isPro && framePps::_()->getModule('license'))) {?>
				<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_<?php echo $proSubMod?>">
					<th scope="row">
						<?php _e('Activate License or update PRO version plugin', PPS_LANG_CODE)?>
						<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Apparently - you have installed PRO version, but did not activate it license - then please activate it. Or you have old version of plugin - then you need go to Plugins page and Update PRO version plugin, after this go to License tab and re-activate license (just click one more time on "Activate" button).', PPS_LANG_CODE))?>"></i>
					</th>
					<td>
						<a href="<?php echo framePps::_()->getModule('options')->getTabUrl('license');?>" class="button"><?php _e('Activate License', PPS_LANG_CODE)?></a>
					</td>
				</tr>
			<?php } else {?>
				<tr class="ppsPopupSubDestOpts ppsPopupSubDestOpts_<?php echo $proSubMod?>">
					<th scope="row">
						<?php printf(__('Enable %s with PRO', PPS_LANG_CODE), $proSubModData['label'])?>
						<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('This is PRO feature, and it will be available once you will install <a href="%s" target="_blank">PRO version</a> of our plugin', PPS_LANG_CODE), framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium='. $proSubMod. '&utm_campaign=popup')))?>"></i>
						<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium='. $proSubMod. '&utm_campaign=popup');?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
					</th>
					<td>
						<a href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium='. $proSubMod. '&utm_campaign=popup');?>" target="_blank" class="button"><?php _e('Get PRO', PPS_LANG_CODE)?></a>
					</td>
				</tr>
			<?php }?>
		<?php }?>
		<tr>
			<th scope="row">
				<?php _e('Subscribe with Facebook', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('Add button to your PopUp with possibility to subscribe just in one click - without filling fields in your subscribe form, <img src="%s" />', PPS_LANG_CODE), $this->promoModPath. 'img/fb-subscribe.jpg'))?>"></i>
				<?php if(!$this->isPro) {?>
					<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium=fb_subscribe&utm_campaign=popup');?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
				<?php }?>
			</th>
			<td>
				<?php echo htmlPps::checkbox('params[tpl][sub_enb_fb_subscribe]', array(
					'attrs' => 'class="ppsProOpt"',
					'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_enb_fb_subscribe')))?>
			</td>
		</tr>
		<tr class="ppsPopupSubCreateWpUser">
			<th scope="row">
				<?php _e('Create WP user', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo _e('Once user will subscribe to selected Subscription service - it will create WordPress Subscriber too. PLease be carefull using this option: WordPressusers will be created right after you submit your Subscribe form without confirmation.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::checkbox('params[tpl][sub_create_wp_user]', array(
					'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_create_wp_user')))?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('Test Email Function', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Email delivery depends from your server configuration. For some cases - you and your subscribers can not receive emails just because email on your server is not working correctly. You can easy test it here - by sending test email. If you receive it - then it means that email functionality on your server works well. If not - this means that it is not working correctly and you should contact your hosting provider with this issue and ask them to setup email functionality for you on your server.', PPS_LANG_CODE))?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('test_email', array(
					'value' => get_bloginfo('admin_email'),
				))?>
				<a href="#" class="ppsTestEmailFuncBtn button">
					<i class="fa fa-paper-plane"></i>
					<?php _e('Send Test Email', PPS_LANG_CODE)?>
				</a>
				<div class="ppsTestEmailWasSent" style="display: none;">
					<?php _e('Email was sent. Now check your email inbox / spam folders for test mail. If you don’t find it - it means that your server can\'t send emails - and you need to contact your hosting provider with this issue.', PPS_LANG_CODE)?>
				</div>
			</td>
		</tr>
	</table>
	<div class="ppsPopupOptRow">
		<fieldset id="ppoPopupSubFields" class="ppoPopupSubFields" style="padding: 10px;">
			<legend>
				<?php _e('Subscription fields', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('To change field position - just drag-&-drop it to required place between other fields. To add new field to Subscribe form - click on "+ Add" button.', PPS_LANG_CODE))?>"></i>
			</legend>
			<?php foreach($this->popup['params']['tpl']['sub_fields'] as $k => $f) { ?>
				<?php
					$labelClass = 'ppsSubFieldShell';
					if($k == 'email')
						$labelClass .= ' supsystic-tooltip-bottom ppsSubFieldEmailShell';
				?>
				<div
					class="<?php echo $labelClass?>"
					data-name="<?php echo $k?>"
					<?php if($k == 'email') { ?>
						title="Email field is mandatory for most of subscribe engines - so it should be always enabled"
					<?php }?>
				>
					<span class="ppsSortHolder"></span>
					<?php 
						if($k == 'email') {
							$checkParams = array('checked' => 1, 'disabled' => 1);
						} else {
							$checkParams = array('checked' => htmlPps::checkedOpt($f, 'enb'));
						}
					?>
					<?php echo htmlPps::checkbox('params[tpl][sub_fields]['. $k. '][enb]', $checkParams)?>
					
					<span class="ppsSubFieldLabel"><?php echo $f['label']?></span>
					
					<?php echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][name]', array('value' => esc_html(isset($f['name']) ? $f['name'] : $k)))?>
					<?php echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][html]', array('value' => esc_html($f['html'])))?>
					<?php echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][label]', array('value' => esc_html($f['label'])))?>
					<?php echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][value]', array('value' => esc_html(isset($f['value']) ? $f['value'] : '')))?>
					<?php echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][custom]', array('value' => esc_html(isset($f['custom']) ? $f['custom'] : 0)))?>
					<?php echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][mandatory]', array('value' => isset($f['mandatory']) ? $f['mandatory'] : 0))?>
					<?php echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][set_preset]', array('value' => isset($f['set_preset']) ? $f['set_preset'] : ''))?>
					<?php if(isset($f['options']) && !empty($f['options'])) {
						foreach($f['options'] as $i => $opt) {
							echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][options]['. $i. '][name]', array('value' => esc_html($opt['name'])));
							echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][options]['. $i. '][label]', array('value' => esc_html($opt['label'])));
						}
					}?>
					<?php 
						if($k == 'email') {	// Email is always checked
							echo htmlPps::hidden('params[tpl][sub_fields]['. $k. '][enb]', array('value' => 1));
						}
					?>
				</div>
			<?php }?>
			<label id="ppsSubAddFieldShell">
				<a id="ppsSubAddFieldBtn" href="#" class="button button-primary">
					<i class="fa fa-plus"></i>
					<?php _e('Add', PPS_LANG_CODE)?>
				</a>
				<?php if(!$this->isPro) {?>
					<span class="ppsProOptMiniLabel" style="margin-bottom: 0; margin-top: -5px;">
						<a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium=sub_fields&utm_campaign=popup');?>"><?php _e('PRO option', PPS_LANG_CODE)?></a>
					</span>
				<?php }?>
			</label>
			<div style="clear: both;"></div>
			<div class="ppsReCaptchaShell">
				<label>
					<?php echo htmlPps::checkbox('params[tpl][enb_captcha]', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_captcha'),
						'attrs' => 'class="ppsProOpt"',
					))?>
					<?php _e('Re-Captcha', PPS_LANG_CODE)?>
				</label>
				<?php if(!$this->isPro) {?>
					<span class="ppsProOptMiniLabel" style="margin-bottom: 0; margin-top: -5px;">
						<a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium=re_captcha&utm_campaign=popup');?>"><?php _e('PRO option', PPS_LANG_CODE)?></a>
					</span>
				<?php } else { ?>
				<div class="ppsReCaptchaOptsShell">
					<table class="form-table ppsSubShellOptsTbl">
						<tr>
							<th scope="row">
								<?php _e('ReCaptcha Site Key', PPS_LANG_CODE)?>
								<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('Your site key, generated on <a href="%s" target="_blank">%s</a>.', PPS_LANG_CODE), 'https://www.google.com/recaptcha/admin#list', 'https://www.google.com/recaptcha/admin#list'))?>"></i>
							</th>
							<td>
								<?php echo htmlPps::text('params[tpl][capt_site_key]', array(
									'value' => (isset($this->popup['params']['tpl']['capt_site_key']) ? $this->popup['params']['tpl']['capt_site_key'] : ''),
								))?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php _e('ReCaptcha Secret Key', PPS_LANG_CODE)?>
								<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(sprintf(__('Your secret key, generated on <a href="%s" target="_blank">%s</a>.', PPS_LANG_CODE), 'https://www.google.com/recaptcha/admin#list', 'https://www.google.com/recaptcha/admin#list'))?>"></i>
							</th>
							<td>
								<?php echo htmlPps::text('params[tpl][capt_secret_key]', array(
									'value' => (isset($this->popup['params']['tpl']['capt_secret_key']) ? $this->popup['params']['tpl']['capt_secret_key'] : ''),
								))?>
							</td>
						</tr>						
					</table>
				</div>
				<?php } ?>
			</div>
		</fieldset>
	</div>
	<table class="form-table ppsSubShellOptsTbl">
		<tr>
			<th scope="row">
				<?php _e('Blacklist', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Here you can add emails, or email doamains, into blacklist - and they will not be able to subscribe. To add several emails (rules) - use "," symbol (coma) as separator. To add email domain - for examle @email.com - use "*@email.com" rule.', PPS_LANG_CODE))?>"></i>
				<?php if(!$this->isPro) {?>
					<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium=blacklist&utm_campaign=popup');?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
				<?php }?>
			</th>
			<td>
				<?php echo htmlPps::textarea('params[tpl][blacklist]', array(
					'value' => (isset($this->popup['params']['tpl']['blacklist']) ? $this->popup['params']['tpl']['blacklist'] : ''),
					'attrs' => 'class="ppsProOpt"',
				))?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('Blacklist error message', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Error message, that user will see if he / she email is in Blacklist', PPS_LANG_CODE))?>"></i>
				<?php if(!$this->isPro) {?>
					<span class="ppsProOptMiniLabel"><a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium=blacklist&utm_campaign=popup');?>"><?php _e('PRO option', PPS_LANG_CODE)?></a></span>
				<?php }?>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][blacklist_error]', array(
					'value' => (isset($this->popup['params']['tpl']['blacklist_error']) ? $this->popup['params']['tpl']['blacklist_error'] : __('Your email is in blacklist', PPS_LANG_CODE)),
					'attrs' => 'class="ppsProOpt"',
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubTxtsAndRedirect" style="display: none;">
			<th scope="row">
				<?php _e('"Confirmation sent" message', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('This is the message that the user will see after subscription, when letter with confirmation link was sent.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_confirm_sent]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_txt_confirm_sent']) ? esc_html( $this->popup['params']['tpl']['sub_txt_confirm_sent'] ) : __('Confirmation link was sent to your email address. Check your email!', PPS_LANG_CODE)),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubTxtsAndRedirect" style="display: none;">
			<th scope="row">
				<?php _e('Subscribe success message', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Right after subscriber will be created and confirmed - this message will be shown.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_success]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_txt_success']) ? esc_html( $this->popup['params']['tpl']['sub_txt_success'] ) : __('Thank you for subscribing!', PPS_LANG_CODE)),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubTxtsAndRedirect" style="display: none;">
			<th scope="row">
				<?php _e('Email error message', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('If email that was entered by user is invalid, user will see this message', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_invalid_email]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_txt_invalid_email']) ? esc_html( $this->popup['params']['tpl']['sub_txt_invalid_email'] ) : __('Empty or invalid email', PPS_LANG_CODE)),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubTxtsAndRedirect" style="display: none;">
			<th scope="row">
				<?php _e('Email exists error message', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('If email that was entered by user already exists - user will see this message. But be careful: this can be used by hackers - to detect existing email in your database, so it\'s better for you to leave this message same as error message about invalid email above.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_exists_email]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_txt_exists_email']) ? esc_html( $this->popup['params']['tpl']['sub_txt_exists_email'] ) : __('Empty or invalid email', PPS_LANG_CODE)),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubRedirect">
			<th scope="row">
				<?php _e('Redirect after subscription URL', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('You can enable redirection after subscription, just enter here URL that you want to redirect to after subscribe - and user will be redirected there. If you don\'t need this feature - just leave this field empty.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_redirect_url]', array(
					'value' => (isset($this->popup['params']['tpl']['sub_redirect_url']) ? esc_url( $this->popup['params']['tpl']['sub_redirect_url'] ) : ''),
					'attrs' => 'placeholder="http://example.com"',
				))?>
				<label>
					<?php echo htmlPps::checkbox('params[tpl][sub_redirect_new_wnd]', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_redirect_new_wnd')))?>
					<?php _e('Open in a new window (tab)', PPS_LANG_CODE)?>
				</label>
				<label class="supsystic-tooltip" title="<?php _e('If you set PopUp to Show On -> Click on certain link, and this link have href parameter - you can redirect your users there after successful subscribe.', PPS_LANG_CODE)?>">
					<?php echo htmlPps::checkbox('params[tpl][sub_redirect_to_btn_url]', array(
						'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sub_redirect_to_btn_url')))?>
					<?php _e('Redirect to button URL', PPS_LANG_CODE)?>
				</label>
			</td>
		</tr>
		<tr class="ppsPopupSubEmailTxt" style="display: none;">
			<th scope="row">
				<?php _e('Confirmation email subject', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Email with confirmation link subject', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_confirm_mail_subject]', array(
					'value' => esc_html ( isset($this->popup['params']['tpl']['sub_txt_confirm_mail_subject']) 
						? $this->popup['params']['tpl']['sub_txt_confirm_mail_subject'] 
						: __('Confirm subscription on [sitename]', PPS_LANG_CODE)),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubEmailTxt" style="display: none;">
			<th scope="row">
				<?php _e('Confirmation email From field', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Email with confirmation link From field', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_confirm_mail_from]', array(
					'value' => esc_html ( isset($this->popup['params']['tpl']['sub_txt_confirm_mail_from']) 
						? $this->popup['params']['tpl']['sub_txt_confirm_mail_from'] 
						: $this->adminEmail),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubEmailTxt" style="display: none;">
			<th scope="row">
				<?php _e('Confirmation email text', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Email with confirmation link content', PPS_LANG_CODE)?>"></i>
				<?php $allowVarsInMail = array('sitename', 'siteurl', 'confirm_link', 'subscribe_url');?>
				<?php if(isset($this->popup['params']['tpl']['sub_fields']) && !empty($this->popup['params']['tpl']['sub_fields'])) {
					foreach($this->popup['params']['tpl']['sub_fields'] as $fName => $fData) {
						$allowVarsInMail[] = 'user_'. $fName;
					}
				}?>
				<div class="description"><?php printf(__('You can use next variables here: %s, and any other subscribe field value - just place here [user_FIELD_NAME], where FIELD_NAME - is name attribute of required field.', PPS_LANG_CODE), '['. implode('], [', $allowVarsInMail).']')?></div>
			</th>
			<td>
				<?php echo htmlPps::textarea('params[tpl][sub_txt_confirm_mail_message]', array(
					'value' => esc_html( isset($this->popup['params']['tpl']['sub_txt_confirm_mail_message']) 
						? $this->popup['params']['tpl']['sub_txt_confirm_mail_message'] 
						: __('You subscribed on site <a href="[siteurl]">[sitename]</a>. Follow <a href="[confirm_link]">this link</a> to complete your subscription. If you did not subscribe here - just ignore this message.', PPS_LANG_CODE)),
				))?><br />
				<div class="ppsPopupAttachFilesShell" data-key="confirm">
					<a href="#" class="button ppsPopupAddEmailAttachBtn"><i class="fa fa-plus"></i><?php _e('Add Attach', PPS_LANG_CODE)?></a>
				</div>
			</td>
		</tr>
		<tr class="ppsPopupSubEmailTxt" style="display: none;">
			<th scope="row">
				<?php _e('New Subscriber email subject', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Email to New Subscriber subject', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_subscriber_mail_subject]', array(
					'value' => esc_html ( isset($this->popup['params']['tpl']['sub_txt_subscriber_mail_subject']) 
						? $this->popup['params']['tpl']['sub_txt_subscriber_mail_subject'] 
						: __('[sitename] Your username and password', PPS_LANG_CODE)),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubEmailTxt" style="display: none;">
			<th scope="row">
				<?php _e('New Subscriber email From field', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('New Subscriber email From field', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_txt_subscriber_mail_from]', array(
					'value' => esc_html ( isset($this->popup['params']['tpl']['sub_txt_subscriber_mail_from']) 
						? $this->popup['params']['tpl']['sub_txt_subscriber_mail_from'] 
						: $this->adminEmail),
				))?>
			</td>
		</tr>
		<tr class="ppsPopupSubEmailTxt" style="display: none;">
			<th scope="row">
				<?php _e('New Subscriber email text', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Email to New Subscriber content', PPS_LANG_CODE)?>"></i>
				<?php $allowVarsInMail = array('user_login', 'user_email', 'password', 'login_url', 'sitename', 'siteurl', 'subscribe_url');?>
				<?php if(isset($this->popup['params']['tpl']['sub_fields']) && !empty($this->popup['params']['tpl']['sub_fields'])) {
					foreach($this->popup['params']['tpl']['sub_fields'] as $fName => $fData) {
						$allowVarsInMail[] = 'user_'. $fName;
					}
				}?>
				<div class="description" style=""><?php printf(__('You can use next variables here: %s, and any other subscribe field value - just place here [user_FIELD_NAME], where FIELD_NAME - is name attribute of required field.', PPS_LANG_CODE), '['. implode('], [', $allowVarsInMail).']')?></div>
			</th>
			<td>
				<?php echo htmlPps::textarea('params[tpl][sub_txt_subscriber_mail_message]', array(
					'value' => esc_html( isset($this->popup['params']['tpl']['sub_txt_subscriber_mail_message']) 
						? $this->popup['params']['tpl']['sub_txt_subscriber_mail_message'] 
						: __('Username: [user_login]<br />Password: [password]<br />[login_url]', PPS_LANG_CODE)),
				))?><br />
				<div class="ppsPopupAttachFilesShell" data-key="subscriber">
					<a href="#" class="button ppsPopupAddEmailAttachBtn"><i class="fa fa-plus"></i><?php _e('Add Attach', PPS_LANG_CODE)?></a>
				</div>
			</td>
		</tr>
		<tr class="ppsPopupSubEmailTxt" style="display: none;">
			<th scope="row">
				<?php _e('Redirect if email already exists', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Link to redirect to if user subscribes - but this email already exists', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_redirect_email_exists]', array(
					'value' => esc_html ( isset($this->popup['params']['tpl']['sub_redirect_email_exists']) 
						? $this->popup['params']['tpl']['sub_redirect_email_exists'] 
						: ''),
					'attrs' => 'placeholder="http://example.com"'
				))?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('Submit button name', PPS_LANG_CODE)?>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_btn_label]', array('value' => $this->popup['params']['tpl']['sub_btn_label']))?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('New Subscriber Notification', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Enter the email addresses that should receive notifications (separate by comma). Leave it blank - and you will not get any notifications.', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_new_email]', array(
					'value' => isset($this->popup['params']['tpl']['sub_new_email']) 
						? $this->popup['params']['tpl']['sub_new_email'] 
						: $this->adminEmail,
				))?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('New Subscriber Notification Subject', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Email about new subscriber Subject', PPS_LANG_CODE)?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('params[tpl][sub_new_subject]', array(
					'value' => isset($this->popup['params']['tpl']['sub_new_subject']) 
						? $this->popup['params']['tpl']['sub_new_subject'] 
						: sprintf(__('New Subscriber on %s', PPS_LANG_CODE), wp_specialchars_decode(get_bloginfo('name'))),
				))?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('New Subscriber Notification email text', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php _e('Message that you will receive about new subscribers on your site.', PPS_LANG_CODE)?>"></i>
				<?php $allowVarsInMail = array('sitename', 'siteurl', 'subscriber_data', 'subscribe_url');?>
				<div class="description" style=""><?php printf(__('You can use next variables here: %s', PPS_LANG_CODE), '['. implode('], [', $allowVarsInMail).']')?></div>
			</th>
			<td>
				<?php echo htmlPps::textarea('params[tpl][sub_new_message]', array(
					'value' => isset($this->popup['params']['tpl']['sub_new_message']) 
						? $this->popup['params']['tpl']['sub_new_message'] 
						: __('You have new subscriber on your site <a href="[siteurl]">[sitename]</a>, here is subscriber information:<br />[subscriber_data]', PPS_LANG_CODE),
				))?><br />
				<div class="ppsPopupAttachFilesShell" data-key="new_message">
					<a href="#" class="button ppsPopupAddEmailAttachBtn"><i class="fa fa-plus"></i><?php _e('Add Attach', PPS_LANG_CODE)?></a>
				</div>
			</td>
		</tr>
	</table>
</span>
<!--Add Field promo Wnd-->
<div id="ppsSubAddFieldWnd" title="<?php _e('Subscribe Field Settings', PPS_LANG_CODE)?>" style="display: none;">
	<a target="_blank" href="<?php echo framePps::_()->getModule('supsystic_promo')->generateMainLink('utm_source=plugin&utm_medium=sub_fields&utm_campaign=popup');?>" class="ppsPromoImgUrl">
		<img src="<?php echo $this->promoModPath?>img/sub-fields-edit.jpg" />
	</a>
</div>
<!--Standard fields toolbar-->
<div id="ppsSfFieldToolbarStandardExl" class="ppsSfFieldToolbar">
	<a class="ppsSfFieldSettingsBtn" href="#" title="<?php _e('Settings', PPS_LANG_CODE)?>">
		<i class="fa fa-gear"></i>
	</a>
</div>
<!--Add/edit standard subscribe fields popup-->
<div id="ppsSfEditFieldsStandardWnd" title="<?php _e('Subscribe Field Settings', PPS_LANG_CODE)?>" style="display: none;">
	<table class="form-table">
		<tr class="ppsSfLabelShell">
			<th scope="row">
				<?php _e('Label', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Label that will be visible for your subscribers.', PPS_LANG_CODE))?>"></i>
			</th>
			<td>
				<?php echo htmlPps::text('label')?>
			</td>
		</tr>
		<tr class="ppsSfMandatoryStandardRow">
			<th scope="row">
				<?php _e('Mandatory', PPS_LANG_CODE)?>
				<i class="fa fa-question supsystic-tooltip" title="<?php echo esc_html(__('Is this field mandatory to fill-in. If yes - then users will not be able to continue without filling-in this field.', PPS_LANG_CODE))?>"></i>
			</th>
			<td>
				<?php echo htmlPps::checkbox('mandatory', array(
					'value' => 1,
				))?>
			</td>
		</tr>
	</table>
	<?php echo htmlPps::hidden('name')?>
</div>
<div id="ppsPopupAttachShell" class="ppsPopupAttachShell">
	<a href="#" class="button ppsPopupAttachBtn"><?php _e('Select File', PPS_LANG_CODE)?></a>
	<?php echo htmlPps::hidden('params[tpl][sub_attach][]', array(
		'disabled' => true,
	))?>
	<span class="ppsPopupAttachFile"></span>
	<a href="#" class="button ppsPopupAttachRemoveBtn" title="<?php _e('Remove', PPS_LANG_CODE)?>"><i class="fa fa-trash"></i></a>
</div>