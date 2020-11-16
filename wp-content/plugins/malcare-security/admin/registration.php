<div class="malcare9">
	<section id="malcare-1" >
		<div class="malcare-logo-img">
			<img height="42" width="169" src="<?php echo plugins_url("/../img/mc-full-logo.png", __FILE__); ?>" alt="">
		</div>
		<div class="container-malcare" id="">
			<div class="row">
				<div class="col-xs-12 malcare-1-container">
					<span class="float-right">
						<a href="https://wordpress.org/support/plugin/malcare-security/reviews/#new-post" class="malcare-top mr-1">Leave a Review</a>
						<a href="https://wordpress.org/support/plugin/malcare-security/" class="malcare-top">Need Help?</a>
					</span>
					<h2 class="text-white text-center heading">High Performance Security Without Slowing Down Your Website</h2>
					<?php $this->showErrors(); ?>
					<div class="search-container text-center ">
						<form dummy=">" action="<?php echo $this->bvinfo->appUrl(); ?>/home/mc_signup" style="padding-top:10px; margin: 0px;" onsubmit="document.getElementById('get-started').disabled = true;"  method="post" name="signup">
							<input type='hidden' name='bvsrc' value='wpplugin' />
							<input type='hidden' name='origin' value='protect' />
							<?php echo $this->siteInfoTags(); ?>
							<input type="text" placeholder="Enter your email address" id="email" name="email" class="search form-control" value="<?php echo get_option('admin_email');?>" required>
							<button id="get-started" type="submit" class="e-mail-button"><span>Secure Site Now</span></button>
							<img class="man-img" width="" src="<?php echo plugins_url("/../img/graphic.svg", __FILE__); ?>" alt="" style="">
							<h5 class="check-box-text mt-2"><input type="checkbox" class="check-box" name="consent" value="1">
							<label>I agree to MalCare <a href="https://www.malcare.com/tos" target="_blank" rel="noopener noreferrer">Terms of Service</a> and <a href="https://www.malcare.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a></label></h5>
						</form>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="malcare-2">
		<div class="container-malcare text-center" id="">
			<div class="heading-malcare text-center">
				<h4>Why choose MalCare</h4>
				<h5>Smart Security for Smart People</h5>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="col-xs-12 col-md-8 col-md-offset-2 col-lg-4 col-lg-offset-0">
						<div class="card-body">
							<h5 class="card-title"><img src="<?php echo plugins_url("/../img/malcare-feature1.svg", __FILE__); ?>"></h5>
							<h6 class="card-subtitle mb-2 text-muted">Cloud Based Scanning</h6>
							<p class="card-text">that keeps your site running at peak speed.</p>
						</div>
					</div>
					<div class="col-xs-12 col-md-8 col-md-offset-2 col-lg-4 col-lg-offset-0">
						<div class="card-body">
							<h5 class="card-title"><img src="<?php echo plugins_url("/../img/malcare-feature2.svg", __FILE__); ?>"></h5>
							<h6 class="card-subtitle mb-2 text-muted">10 Seconds Set Up</h6>
							<p class="card-text">Eliminate Complexity, Embrace Automatic Security.</p>
						</div>
					</div>
					<div class=" col-xs-12 col-md-8 col-md-offset-2 col-lg-4 col-lg-offset-0">
						<div class="card-body">
							<h5 class="card-title"><img src="<?php echo plugins_url("/../img/malcare-feature3.svg", __FILE__); ?>"></h5>
							<h6 class="card-subtitle mb-2 text-muted">Firewall Protection</h6>
							<p class="card-text">Smartest Firewall & Bot Protection to eliminate the bad hackers and attacks.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="malcare-3">
		<div class="container-malcare" id="">
			<div class="heading-malcare text-center">
				<h4>See what our happy customers say</h4>
			</div>
			<div class="row">
				<div class="col-xs-12 d-flex">
					<div class="col-xs-12 col-lg-6">
						<div class="malcare-video">
							<div class="embed-responsive embed-responsive-16by9">
								<iframe width="560" height="315" src="https://www.youtube.com/embed/rBuYh2dIadk" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-lg-6 d-flex">
						<div class="malcare-testimonial d-flex">
							<div id="carousel-example-generic" class="carousel slide d-flex" data-ride="carousel">
								<div class="carousel-inner text-center">
									<div class="item active">
										<div class="row" style="">
											<div class="testimonial-border" >
												<div class="" >
													<img src="https://www.malcare.com/wp-content/uploads/2019/08/Yardena-Epstein-1-1-e1563366762821.png" >
													<h4>Yardena Epstein - Owner, YardenaWeb</h4>
													<h5 class="testimonial_subtitle">
													</h5>
													<p class="testimonial_para">“Several of my client sites got hacked. Within the hour I signed up, the customer service has gone above and beyond for me and all my sites have been cleaned thoroughly and quickly.“</p><br>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="malcare-5">
		<div class="container-malcare text-center" id="">
			<div class="heading-malcare text-center">
				<h4>Premium Feature Upgrade</h4>
				<h5>Get our premium subscription & unlock all new features</h5>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="col-lg-12">
						<div class="subscription ">
							<div class="col-lg-6 h-100 center-align-dflex col-sm-12 col-xs-12">
								<h6 class="fw-600">What’s in MalCare Pro?</h6>
								<ul>
									<li>✓ Login Protection</li>
									<li>✓ Daily Automatic Malware Scans</li>
									<li>✓ 1-Click Unlimited Malware Removal</li>
									<li>✓ Real time firewall updates</li>
									<li>✓ Personalized Support</li>
								</ul>
							</div>
							<div class="col-lg-6 col-sm-12 col-xs-12 h-100 bg-light-green premium-subscription justify-content-center">
								<a href="https://www.malcare.com/pricing/">Get MalCare Pro</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="malcare-4">
		<div class="container-malcare text-center" id="">
			<div class="row">
				<div class="col-lg-12">
					<div class="heading-malcare">
						<h4>Trusted By <b>Those Who You Trust!</b></h4>
					</div>
					<div class="heading-malcare text-center brand d-flex ">
						<img src="<?php echo plugins_url("/../img/codeinwp.png", __FILE__); ?>" style="height: 42px;"/>
						<img src="<?php echo plugins_url("/../img/gowp.png", __FILE__); ?>" style="height: 42px;"	/>
						<img src="<?php echo plugins_url("/../img/valet.png", __FILE__); ?>" style="height: 42px;"/>
						<img src="<?php echo plugins_url("/../img/whole-grain-digital.png", __FILE__); ?>" style="height: 42px;" />
					</div>
				</div>
			</div>
		</div>
	</section>
</div>