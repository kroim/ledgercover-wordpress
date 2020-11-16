<?php
if (!defined('UAP_CORE')) exit;
?>
<h3>Embedding Layered Popups into website</h3>
<p>To embed Layered Popups into any website you need perform the following steps:</p>
<ol>
<li>Make sure that your website loads jQuery version 1.8 or higher.</li>
<li>Make sure that your website has DOCTYPE. If not, add the following line as a first line of HTML-document:
<pre>&lt;!DOCTYPE html&gt;</pre>
</li>
<li><strong style="color: green;">Copy the following JS-snippet and paste it into your website. You need paste it at the end of <code>&lt;body&gt;</code> section (above closing <code>&lt;/body&gt;</code> tag).</strong>
<input class="widefat copy-container" readonly="readonly" onclick="this.focus();this.select();" value="<?php echo esc_html($remote_snippet); ?>" /></li>
<li>That's it. Integration finished. :-)</li>
</ol>
<h3>Using Popups</h3>
<p>There are several ways how you can use popups on your website.</p>
<ol>
<li>To raise the popup by clicking certain element (OnClick popup), add the following <code>onclick</code> handler to that element:
<pre>onclick="return ulp_open('OBJECT_ID');"</pre>
<code>OBJECT_ID</code> is a popup ID taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign ID taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns").
<br/>Example:
<pre>&lt;a href="#" onclick="return ulp_open('OBJECT_ID');"&gt;Raise the popup&lt;/a&gt;</pre>
</li>
<li>Another way to raise the popup by clicking link (OnClick popup) is to use the following URL:
<pre>#ulp-OBJECT_ID</pre>
<code>OBJECT_ID</code> is a popup ID taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign ID taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns").
<br/>Example:
<pre>&lt;a href="#ulp-OBJECT_ID"&gt;Raise the popup&lt;/a&gt;</pre>
If you want to raise different popup for desktops/laptops/tablets and smartphones, use URL like that:
<pre>#ulp-OBJECT1_ID*OBJECT2_ID</pre>
<code>OBJECT1_ID</code> and <code>OBJECT2_ID</code> are popups ID taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign ID taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns"). <code>OBJECT1_ID</code> is a popup/campaign for desktops/laptops/tablets. <code>OBJECT2_ID</code> is a popup/campaign for smartphones.<br/>Example:
<pre>&lt;a href="#ulp-OBJECT1_ID*OBJECT2_ID"&gt;Raise the popup&lt;/a&gt;</pre>
</li>
<li>To raise the popup, when website loaded (OnLoad popup), add the following JS-code to your page. It must be inserted below <code>remote.min.js</code>.
<pre>&lt;script&gt;
ulp_add_event("onload", {
	popup:		"OBJECT1_ID",
	popup_mobile:	"OBJECT2_ID",
	mode:		"every-time",
	period:		5,
	delay:		0,
	close_delay:	0
});
&lt;/script&gt;</pre>
As you can see function <code>ulp_add_event("onload", ..)</code> accept an object with several parameters:
<ul>
	<li><code>popup, popup_mobile</code>. Popup IDs taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign IDs taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns"). At least one of these parameters must be defined. Popup defined as <code>popup_mobile</code> will be displayed on smartphones only.</li>
	<li>
		<code>mode</code>. Select how often OnLoad popup must be displayed. You can use one of the following values:
		<ul>
			<li><code>every-time</code>. The popup is displayed every time until the user has submitted a subscription form.</li>
			<li><code>once-session</code>. The popup is displayed once per session until the user has submitted a subscription form. Session is a browser's session. Usually session ends when user close browser. Sometimes it ends when user reboot PC.</li>
			<li><code>once-period</code>. The popup is displayed once per X days (defined as <code>period</code> parameter) until the user has submitted a subscription form.</li>
			<li><code>once-only</code>. The popup is displayed only once.</li>
			<li><code>none</code>. The popup will never be displayed.</li>
		</ul>
	</li>
	<li><code>period</code>. Set period (in days) when <code>mode</code> is set as <code>once-period</code>. This parameter is ignored in all other cases.</li>
	<li><code>delay</code>. The popup can be displayed with certain delay when page loaded. This parameter defines delay (in seconds).</li>
	<li><code>close_delay</code>. The popup can be automatically closed after certain delay. This parameter defines delay (in seconds). Set 0 (zero) or omit parameter to disable this feature.</li>
</ul>
<br />
Example 1. You want to display popup with ID <code>yoZ8KupKSHejiwyZ</code> once per session, without delays and for all kind of devices (desktops, tablets, smartphones).
<pre>&lt;script&gt;
ulp_add_event("onload", {
	popup:		"yoZ8KupKSHejiwyZ",
	mode:		"once-session"
});
&lt;/script&gt;</pre>
Example 2. You want to display popup with ID <code>yv4RG8hfkSBb4up9</code> for smartphones only, every time, with start delay 10 seconds and without autoclosing feature.
<pre>&lt;script&gt;
ulp_add_event("onload", {
	popup_mobile:	"yv4RG8hfkSBb4up9",
	mode:		"every-time",
	delay:		10
});
&lt;/script&gt;</pre>
Please rememeber, you can use <code>ulp_add_event("onload", ..)</code> only once per page. If you insert it several times with different parameters, only last one will work.
</li>
<li>To raise the popup, when user scroll down website's page (OnScroll popup), add the following JS-code to your page. It must be inserted below <code>remote.min.js</code>.
<pre>&lt;script&gt;
ulp_add_event("onscroll", {
	popup:		"OBJECT1_ID",
	popup_mobile:	"OBJECT2_ID",
	mode:		"every-time",
	period:		5,
	offset:		"600"
});
&lt;/script&gt;</pre>
As you can see function <code>ulp_add_event("onscroll", ..)</code> accept an object with several parameters:
<ul>
	<li><code>popup, popup_mobile</code>. Popup IDs taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign IDs taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns"). At least one of these parameters must be defined. Popup defined as <code>popup_mobile</code> will be displayed on smartphones only.</li>
	<li>
		<code>mode</code>. Select how often OnScroll popup must be displayed. You can use one of the following values:
		<ul>
			<li><code>every-time</code>. The popup is displayed every time until the user has submitted a subscription form.</li>
			<li><code>once-session</code>. The popup is displayed once per session until the user has submitted a subscription form. Session is a browser's session. Usually session ends when user close browser. Sometimes it ends when user reboot PC.</li>
			<li><code>once-period</code>. The popup is displayed once per X days (defined as <code>period</code> parameter) until the user has submitted a subscription form.</li>
			<li><code>once-only</code>. The popup is displayed only once.</li>
			<li><code>none</code>. The popup will never be displayed.</li>
		</ul>
	</li>
	<li><code>period</code>. Set period (in days) when <code>mode</code> is set as <code>once-period</code>. This parameter is ignored in all other cases.</li>
	<li><code>offset</code>. The popup is displayed when user scroll down to this number of pixels. If you want this value to be in %, just add <code>%</code> symbol, like this: <code>offset: "80%"</code>.</li>
</ul>
Please rememeber, you can use <code>ulp_add_event("onscroll", ..)</code> only once per page. If you insert it several times with different parameters, only last one will work.
</li>
<li>To raise the popup, when user moves mouse cursor to top edge of browser window, assuming that he/she is going to leave the page (OnExit popup), add the following JS-code to your page. It must be inserted below <code>remote.min.js</code>.
<pre>&lt;script&gt;
ulp_add_event("onexit", {
	popup:		"OBJECT_ID",
	mode:		"every-time",
	period:		5
});
&lt;/script&gt;</pre>
As you can see function <code>ulp_add_event("onexit", ..)</code> accept an object with several parameters:
<ul>
	<li><code>popup</code>. Popup ID taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign ID taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns").</li>
	<li>
		<code>mode</code>. Select how often OnExit popup must be displayed. You can use one of the following values:
		<ul>
			<li><code>every-time</code>. The popup is displayed every time until the user has submitted a subscription form.</li>
			<li><code>once-session</code>. The popup is displayed once per session until the user has submitted a subscription form. Session is a browser's session. Usually session ends when user close browser. Sometimes it ends when user reboot PC.</li>
			<li><code>once-period</code>. The popup is displayed once per X days (defined as <code>period</code> parameter) until the user has submitted a subscription form.</li>
			<li><code>once-only</code>. The popup is displayed only once.</li>
			<li><code>none</code>. The popup will never be displayed.</li>
		</ul>
	</li>
	<li><code>period</code>. Set period (in days) when <code>mode</code> is set as <code>once-period</code>. This parameter is ignored in all other cases.</li>
</ul>
Please rememeber, you can use <code>ulp_add_event("onexit", ..)</code> only once per page. If you insert it several times with different parameters, only last one will work.
</li>
<li>To raise the popup, when user does nothing on website for certain period of time (OnInactivity popup), add the following JS-code to your page. It must be inserted below <code>remote.min.js</code>.
<pre>&lt;script&gt;
ulp_add_event("onidle", {
	popup:		"OBJECT1_ID",
	popup_mobile:	"OBJECT2_ID",
	mode:		"every-time",
	period:		5,
	delay:		60
});
&lt;/script&gt;</pre>
As you can see function <code>ulp_add_event("onidle", ..)</code> accept an object with several parameters:
<ul>
	<li><code>popup, popup_mobile</code>. Popup IDs taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign IDs taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns"). At least one of these parameters must be defined. Popup defined as <code>popup_mobile</code> will be displayed on smartphones only.</li>
	<li>
		<code>mode</code>. Select how often OnInactivity popup must be displayed. You can use one of the following values:
		<ul>
			<li><code>every-time</code>. The popup is displayed every time until the user has submitted a subscription form.</li>
			<li><code>once-session</code>. The popup is displayed once per session until the user has submitted a subscription form. Session is a browser's session. Usually session ends when user close browser. Sometimes it ends when user reboot PC.</li>
			<li><code>once-period</code>. The popup is displayed once per X days (defined as <code>period</code> parameter) until the user has submitted a subscription form.</li>
			<li><code>once-only</code>. The popup is displayed only once.</li>
			<li><code>none</code>. The popup will never be displayed.</li>
		</ul>
	</li>
	<li><code>period</code>. Set period (in days) when <code>mode</code> is set as <code>once-period</code>. This parameter is ignored in all other cases.</li>
	<li><code>delay</code>. The popup is displayed after this period of user's inactivity (in seconds).</li>
</ul>
Please rememeber, you can use <code>ulp_add_event("onidle", ..)</code> only once per page. If you insert it several times with different parameters, only last one will work.
</li>
<li>To raise the popup, when AdBlock detected (OnAdBlockDetected popup), add the following JS-code to your page. It must be inserted below <code>remote.min.js</code>.  Please notice, this is an experimental feature and can be deleted in future releases.
<pre>&lt;script&gt;
ulp_add_event("onabd", {
	popup:		"OBJECT1_ID",
	popup_mobile:	"OBJECT2_ID",
	mode:		"every-time",
	period:		5
});
&lt;/script&gt;</pre>
As you can see function <code>ulp_add_event("onabd", ..)</code> accept an object with several parameters:
<ul>
	<li><code>popup, popup_mobile</code>. Popup IDs taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups"), or A/B campaign IDs taken form relevant column on <strong>A/B Campaigns</strong> page (menu "Layered Popups &gt;&gt; A/B Campaigns"). At least one of these parameters must be defined. Popup defined as <code>popup_mobile</code> will be displayed on smartphones only.</li>
	<li>
		<code>mode</code>. Select how often OnAdBlockDetected popup must be displayed. You can use one of the following values:
		<ul>
			<li><code>every-time</code>. The popup is displayed every time until the user has submitted a subscription form.</li>
			<li><code>once-session</code>. The popup is displayed once per session until the user has submitted a subscription form. Session is a browser's session. Usually session ends when user close browser. Sometimes it ends when user reboot PC.</li>
			<li><code>once-period</code>. The popup is displayed once per X days (defined as <code>period</code> parameter) until the user has submitted a subscription form.</li>
			<li><code>once-only</code>. The popup is displayed only once.</li>
			<li><code>none</code>. The popup will never be displayed.</li>
		</ul>
	</li>
	<li><code>period</code>. Set period (in days) when <code>mode</code> is set as <code>once-period</code>. This parameter is ignored in all other cases.</li>
</ul>
Please rememeber, you can use <code>ulp_add_event("onabd", ..)</code> only once per page. If you insert it several times with different parameters, only last one will work.
</li>

<li>
To embed the popup into page and display it as a part of its content, use the following HTML shortcode:
<pre>&lt;div class="ulp-inline" data-id="POPUP_ID"&gt;&lt;/div&gt;</pre>
<code>POPUP_ID</code> is a popup ID taken from relevant column on Popups page (menu "Layered Popups >> Popups").
</li>
<li>To lock links you need construct locking URL. In general locking URL looks like:
<pre>#ulp-POPUP_ID:BASE64_ENCODED_ORIGINAL_URL</pre>
<code>POPUP_ID</code> is a popup ID taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups").<br />
<code>BASE64_ENCODED_ORIGINAL_URL</code> is an original URL encoded using <code>base64</code> algorithm. There are a lot of free online services that allows to encode/decode using base64 algorithm. For example: <a href="https://www.base64encode.org/" target="_blank">https://www.base64encode.org/</a>.<br />
If you want to have different popups for desktops/tablets and smartphones, use the following locking URL:
<pre>#ulp-POPUP1_ID*POPUP2_ID:BASE64_ENCODED_ORIGINAL_URL</pre>
<code>POPUP1_ID</code> is a popup ID that will be displayed for desktops/tablets. It's taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups").<br />
<code>POPUP2_ID</code> is a popup ID that will be displayed for smartphones. It's taken from relevant column on <strong>Popups</strong> page (menu "Layered Popups &gt;&gt; Popups").<br />
<code>BASE64_ENCODED_ORIGINAL_URL</code> is an original URL encoded using <code>base64</code> algorithm. There are a lot of free online services that allows to encode/decode using base64 algorithm. For example: <a href="https://www.base64encode.org/" target="_blank">https://www.base64encode.org/</a>.<br />
<strong>Example:</strong><br />
Imagine that we have popup with ID <code>QWERTYUIOP</code>. With this popup we want to lock link which has URL <code>http://website.com/path-to/file.html</code>. Base64 encoded URL would be <code>aHR0cDovL3dlYnNpdGUuY29tL3BhdGgtdG8vZmlsZS5odG1s</code> and locking URL:
<pre>#ulp-QWERTYUIOP:aHR0cDovL3dlYnNpdGUuY29tL3BhdGgtdG8vZmlsZS5odG1s</pre>
Use this URL for your link.
</li>
</ol>
