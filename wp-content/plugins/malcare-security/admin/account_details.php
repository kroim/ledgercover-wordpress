<div id="content-wrapper" style="width: 99%;">
	<!-- Content HTML goes here -->
	<div class="mui-container-fluid">
		<div class="mui--appbar-height"></div>
		<br><br>
		<div class="mui-row">
			<div style="background: #4686f5; overflow: hidden;">
				<a href="https://www.malcare.com/?utm_source=mc_free_plugin_lp_logo&utm_medium=logo_link&utm_campaign=mc_free_plugin_lp_header&utm_term=header_logo&utm_content=image_link"><img src="<?php echo plugins_url($this->getPluginLogo(), __FILE__); ?>" style="padding: 10px;"></a>
				<div class="top-links" style="width:400px;float: right;margin-top: 15px;">
					<span class="bv-top-button" style="padding: 5px;margin:5px;border: 1px solid #17252A;display:inline-block;background:ghostwhite;border-radius: 5px;float:right;"><a href="https://wordpress.org/support/plugin/malcare-security/reviews/#new-post">Leave a Review</a></span>
					<span class="bv-top-button" style="padding: 5px;margin:5px;border: 1px solid #17252A;display:inline-block;background:ghostwhite;border-radius: 5px;float:right;"><a href="https://wordpress.org/support/plugin/malcare-security/">Need Help?</a></span>
				</div>
			</div>
		</div>
	</div>


</br> </br>
<div class="mui-container-fluid">
    <?php $accounts = MCAccount::accountsByPlugname($this->settings);?>
      <div class="mui-panel" style="width:800px; margin:0 auto;border:1px solid #CCC;">
        <div class="mui--text-body1" style="text-align:center;font-size:18px;">Accounts associated with this website.</div><br/>
        <table cellpadding="10" style="width:700px; margin:0 auto;border:1px solid black;">
          <tr style="text-align:center;font-size:15px;border: 1px solid black;"> <th> Account Email</th><th>Last Scanned At</th><th></th></tr>
            <?php
              $nonce = wp_create_nonce( 'bvnonce' );
              foreach($accounts as $key => $value){
            ?>
            <form dummy=">" action=""  style="padding:0 2% 2em 1%;" method="post">
              <input type='hidden' name='bvnonce' value="<?php echo $nonce ?>" />
              <input type='hidden' name='pubkey' value="<?php echo $key ?>" />
              <tr style="text-align:center;font-size:15px;border: 1px solid black;">
                <td >
                  <?php echo $value['email'] ?>
                </td>
                <td>
                  <?php echo date('Y-m-d H:i:s', $value['lastbackuptime']); ?>
                </td>
                <td >
                  <input type='submit' class="button-primary" value='Disconnect' name='disconnect'>
                </td>
              </tr>
            </form>
        <?php } ?>
        </table>
      <div class="mui-col-md-12 mui-col-md-offset-3" style="padding-top:2%;">
				
				<?php if(isset($this->account)) { ?>
        <a class="mui-btn mui-btn--raised mui-btn--primary" href=<?php echo $this->account->authenticatedUrl('/malcare/access') ?> target="_blank">Visit Dashboard</a>
				<?php } ?>
        <a class="mui-btn mui-btn--raised mui-btn--primary" href=<?php echo $this->mainUrl('&add_account=true'); ?> >Connect New Account</a>
      </div>
    </div>
  </div>
</div>