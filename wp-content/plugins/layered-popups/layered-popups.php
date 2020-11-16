<?php
/*
Plugin Name: Layered Popups
Plugin URI: https://layeredpopups.com/
Description: Create multi-layers animated popups.
Version: 6.64
Author: پرشین اسکریپت
Author URI: https://www.persianscript.ir
*/
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
define('ULP_RECORDS_PER_PAGE', '50');
define('ULP_VERSION', 6.64);
define('ULP_WEBFONTS_VERSION', 3);
define('ULP_EXPORT_VERSION', '0001');
define('ULP_API_URL', 'http://layeredpopups.com/updates/');
define('ULP_UPLOADS_DIR', 'ulp');
define('ULP_SUBSCRIBER_UNCONFIRMED', 1);
define('ULP_SUBSCRIBER_CONFIRMED', 2);

register_activation_hook(__FILE__, array("ulp_class", "install"));
register_deactivation_hook(__FILE__, array("ulp_class", "uninstall"));

class ulp_class {
	var $plugins_url;
	var $options;
	var $error;
	var $info;
	var $google_fonts = array();
	var $postdata = array();
	var $front_header = '';
	var $front_footer = '';
	var $fa_solid = array("ad","address-book","address-card","adjust","air-freshener","align-center","align-justify","align-left","align-right","allergies","ambulance","american-sign-language-interpreting","anchor","angle-double-down","angle-double-left","angle-double-right","angle-double-up","angle-down","angle-left","angle-right","angle-up","angry","ankh","apple-alt","archive","archway","arrow-alt-circle-down","arrow-alt-circle-left","arrow-alt-circle-right","arrow-alt-circle-up","arrow-circle-down","arrow-circle-left","arrow-circle-right","arrow-circle-up","arrow-down","arrow-left","arrow-right","arrow-up","arrows-alt","arrows-alt-h","arrows-alt-v","assistive-listening-systems","asterisk","at","atlas","atom","audio-description","award","baby","baby-carriage","backspace","backward","bacon","balance-scale","ban","band-aid","barcode","bars","baseball-ball","basketball-ball","bath","battery-empty","battery-full","battery-half","battery-quarter","battery-three-quarters","bed","beer","bell","bell-slash","bezier-curve","bible","bicycle","binoculars","biohazard","birthday-cake","blender","blender-phone","blind","blog","bold","bolt","bomb","bone","bong","book","book-dead","book-medical","book-open","book-reader","bookmark","bowling-ball","box","box-open","boxes","braille","brain","bread-slice","briefcase","briefcase-medical","broadcast-tower","broom","brush","bug","building","bullhorn","bullseye","burn","bus","bus-alt","business-time","calculator","calendar","calendar-alt","calendar-check","calendar-day","calendar-minus","calendar-plus","calendar-times","calendar-week","camera","camera-retro","campground","candy-cane","cannabis","capsules","car","car-alt","car-battery","car-crash","car-side","caret-down","caret-left","caret-right","caret-square-down","caret-square-left","caret-square-right","caret-square-up","caret-up","carrot","cart-arrow-down","cart-plus","cash-register","cat","certificate","chair","chalkboard","chalkboard-teacher","charging-station","chart-area","chart-bar","chart-line","chart-pie","check","check-circle","check-double","check-square","cheese","chess","chess-bishop","chess-board","chess-king","chess-knight","chess-pawn","chess-queen","chess-rook","chevron-circle-down","chevron-circle-left","chevron-circle-right","chevron-circle-up","chevron-down","chevron-left","chevron-right","chevron-up","child","church","circle","circle-notch","city","clinic-medical","clipboard","clipboard-check","clipboard-list","clock","clone","closed-captioning","cloud","cloud-download-alt","cloud-meatball","cloud-moon","cloud-moon-rain","cloud-rain","cloud-showers-heavy","cloud-sun","cloud-sun-rain","cloud-upload-alt","cocktail","code","code-branch","coffee","cog","cogs","coins","columns","comment","comment-alt","comment-dollar","comment-dots","comment-medical","comment-slash","comments","comments-dollar","compact-disc","compass","compress","compress-arrows-alt","concierge-bell","cookie","cookie-bite","copy","copyright","couch","credit-card","crop","crop-alt","cross","crosshairs","crow","crown","crutch","cube","cubes","cut","database","deaf","democrat","desktop","dharmachakra","diagnoses","dice","dice-d20","dice-d6","dice-five","dice-four","dice-one","dice-six","dice-three","dice-two","digital-tachograph","directions","divide","dizzy","dna","dog","dollar-sign","dolly","dolly-flatbed","donate","door-closed","door-open","dot-circle","dove","download","drafting-compass","dragon","draw-polygon","drum","drum-steelpan","drumstick-bite","dumbbell","dumpster","dumpster-fire","dungeon","edit","egg","eject","ellipsis-h","ellipsis-v","envelope","envelope-open","envelope-open-text","envelope-square","equals","eraser","ethernet","euro-sign","exchange-alt","exclamation","exclamation-circle","exclamation-triangle","expand","expand-arrows-alt","external-link-alt","external-link-square-alt","eye","eye-dropper","eye-slash","fast-backward","fast-forward","fax","feather","feather-alt","female","fighter-jet","file","file-alt","file-archive","file-audio","file-code","file-contract","file-csv","file-download","file-excel","file-export","file-image","file-import","file-invoice","file-invoice-dollar","file-medical","file-medical-alt","file-pdf","file-powerpoint","file-prescription","file-signature","file-upload","file-video","file-word","fill","fill-drip","film","filter","fingerprint","fire","fire-alt","fire-extinguisher","first-aid","fish","fist-raised","flag","flag-checkered","flag-usa","flask","flushed","folder","folder-minus","folder-open","folder-plus","font","football-ball","forward","frog","frown","frown-open","funnel-dollar","futbol","gamepad","gas-pump","gavel","gem","genderless","ghost","gift","gifts","glass-cheers","glass-martini","glass-martini-alt","glass-whiskey","glasses","globe","globe-africa","globe-americas","globe-asia","globe-europe","golf-ball","gopuram","graduation-cap","greater-than","greater-than-equal","grimace","grin","grin-alt","grin-beam","grin-beam-sweat","grin-hearts","grin-squint","grin-squint-tears","grin-stars","grin-tears","grin-tongue","grin-tongue-squint","grin-tongue-wink","grin-wink","grip-horizontal","grip-lines","grip-lines-vertical","grip-vertical","guitar","h-square","hamburger","hammer","hamsa","hand-holding","hand-holding-heart","hand-holding-usd","hand-lizard","hand-middle-finger","hand-paper","hand-peace","hand-point-down","hand-point-left","hand-point-right","hand-point-up","hand-pointer","hand-rock","hand-scissors","hand-spock","hands","hands-helping","handshake","hanukiah","hard-hat","hashtag","hat-wizard","haykal","hdd","heading","headphones","headphones-alt","headset","heart","heart-broken","heartbeat","helicopter","highlighter","hiking","hippo","history","hockey-puck","holly-berry","home","horse","horse-head","hospital","hospital-alt","hospital-symbol","hot-tub","hotdog","hotel","hourglass","hourglass-end","hourglass-half","hourglass-start","house-damage","hryvnia","i-cursor","ice-cream","icicles","id-badge","id-card","id-card-alt","igloo","image","images","inbox","indent","industry","infinity","info","info-circle","italic","jedi","joint","journal-whills","kaaba","key","keyboard","khanda","kiss","kiss-beam","kiss-wink-heart","kiwi-bird","landmark","language","laptop","laptop-code","laptop-medical","laugh","laugh-beam","laugh-squint","laugh-wink","layer-group","leaf","lemon","less-than","less-than-equal","level-down-alt","level-up-alt","life-ring","lightbulb","link","lira-sign","list","list-alt","list-ol","list-ul","location-arrow","lock","lock-open","long-arrow-alt-down","long-arrow-alt-left","long-arrow-alt-right","long-arrow-alt-up","low-vision","luggage-cart","magic","magnet","mail-bulk","male","map","map-marked","map-marked-alt","map-marker","map-marker-alt","map-pin","map-signs","marker","mars","mars-double","mars-stroke","mars-stroke-h","mars-stroke-v","mask","medal","medkit","meh","meh-blank","meh-rolling-eyes","memory","menorah","mercury","meteor","microchip","microphone","microphone-alt","microphone-alt-slash","microphone-slash","microscope","minus","minus-circle","minus-square","mitten","mobile","mobile-alt","money-bill","money-bill-alt","money-bill-wave","money-bill-wave-alt","money-check","money-check-alt","monument","moon","mortar-pestle","mosque","motorcycle","mountain","mouse-pointer","mug-hot","music","network-wired","neuter","newspaper","not-equal","notes-medical","object-group","object-ungroup","oil-can","om","otter","outdent","pager","paint-brush","paint-roller","palette","pallet","paper-plane","paperclip","parachute-box","paragraph","parking","passport","pastafarianism","paste","pause","pause-circle","paw","peace","pen","pen-alt","pen-fancy","pen-nib","pen-square","pencil-alt","pencil-ruler","people-carry","pepper-hot","percent","percentage","person-booth","phone","phone-slash","phone-square","phone-volume","piggy-bank","pills","pizza-slice","place-of-worship","plane","plane-arrival","plane-departure","play","play-circle","plug","plus","plus-circle","plus-square","podcast","poll","poll-h","poo","poo-storm","poop","portrait","pound-sign","power-off","pray","praying-hands","prescription","prescription-bottle","prescription-bottle-alt","print","procedures","project-diagram","puzzle-piece","qrcode","question","question-circle","quidditch","quote-left","quote-right","quran","radiation","radiation-alt","rainbow","random","receipt","recycle","redo","redo-alt","registered","reply","reply-all","republican","restroom","retweet","ribbon","ring","road","robot","rocket","route","rss","rss-square","ruble-sign","ruler","ruler-combined","ruler-horizontal","ruler-vertical","running","rupee-sign","sad-cry","sad-tear","satellite","satellite-dish","save","school","screwdriver","scroll","sd-card","search","search-dollar","search-location","search-minus","search-plus","seedling","server","shapes","share","share-alt","share-alt-square","share-square","shekel-sign","shield-alt","ship","shipping-fast","shoe-prints","shopping-bag","shopping-basket","shopping-cart","shower","shuttle-van","sign","sign-in-alt","sign-language","sign-out-alt","signal","signature","sim-card","sitemap","skating","skiing","skiing-nordic","skull","skull-crossbones","slash","sleigh","sliders-h","smile","smile-beam","smile-wink","smog","smoking","smoking-ban","sms","snowboarding","snowflake","snowman","snowplow","socks","solar-panel","sort","sort-alpha-down","sort-alpha-up","sort-amount-down","sort-amount-up","sort-down","sort-numeric-down","sort-numeric-up","sort-up","spa","space-shuttle","spider","spinner","splotch","spray-can","square","square-full","square-root-alt","stamp","star","star-and-crescent","star-half","star-half-alt","star-of-david","star-of-life","step-backward","step-forward","stethoscope","sticky-note","stop","stop-circle","stopwatch","store","store-alt","stream","street-view","strikethrough","stroopwafel","subscript","subway","suitcase","suitcase-rolling","sun","superscript","surprise","swatchbook","swimmer","swimming-pool","synagogue","sync","sync-alt","syringe","table","table-tennis","tablet","tablet-alt","tablets","tachometer-alt","tag","tags","tape","tasks","taxi","teeth","teeth-open","temperature-high","temperature-low","tenge","terminal","text-height","text-width","th","th-large","th-list","theater-masks","thermometer","thermometer-empty","thermometer-full","thermometer-half","thermometer-quarter","thermometer-three-quarters","thumbs-down","thumbs-up","thumbtack","ticket-alt","times","times-circle","tint","tint-slash","tired","toggle-off","toggle-on","toilet","toilet-paper","toolbox","tools","tooth","torah","torii-gate","tractor","trademark","traffic-light","train","tram","transgender","transgender-alt","trash","trash-alt","trash-restore","trash-restore-alt","tree","trophy","truck","truck-loading","truck-monster","truck-moving","truck-pickup","tshirt","tty","tv","umbrella","umbrella-beach","underline","undo","undo-alt","universal-access","university","unlink","unlock","unlock-alt","upload","user","user-alt","user-alt-slash","user-astronaut","user-check","user-circle","user-clock","user-cog","user-edit","user-friends","user-graduate","user-injured","user-lock","user-md","user-minus","user-ninja","user-nurse","user-plus","user-secret","user-shield","user-slash","user-tag","user-tie","user-times","users","users-cog","utensil-spoon","utensils","vector-square","venus","venus-double","venus-mars","vial","vials","video","video-slash","vihara","volleyball-ball","volume-down","volume-mute","volume-off","volume-up","vote-yea","vr-cardboard","walking","wallet","warehouse","water","weight","weight-hanging","wheelchair","wifi","wind","window-close","window-maximize","window-minimize","window-restore","wine-bottle","wine-glass","wine-glass-alt","won-sign","wrench","x-ray","yen-sign","yin-yang");
	var $fa_regular = array("address-book","address-card","angry","arrow-alt-circle-down","arrow-alt-circle-left","arrow-alt-circle-right","arrow-alt-circle-up","bell","bell-slash","bookmark","building","calendar","calendar-alt","calendar-check","calendar-minus","calendar-plus","calendar-times","caret-square-down","caret-square-left","caret-square-right","caret-square-up","chart-bar","check-circle","check-square","circle","clipboard","clock","clone","closed-captioning","comment","comment-alt","comment-dots","comments","compass","copy","copyright","credit-card","dizzy","dot-circle","edit","envelope","envelope-open","eye","eye-slash","file","file-alt","file-archive","file-audio","file-code","file-excel","file-image","file-pdf","file-powerpoint","file-video","file-word","flag","flushed","folder","folder-open","frown","frown-open","futbol","gem","grimace","grin","grin-alt","grin-beam","grin-beam-sweat","grin-hearts","grin-squint","grin-squint-tears","grin-stars","grin-tears","grin-tongue","grin-tongue-squint","grin-tongue-wink","grin-wink","hand-lizard","hand-paper","hand-peace","hand-point-down","hand-point-left","hand-point-right","hand-point-up","hand-pointer","hand-rock","hand-scissors","hand-spock","handshake","hdd","heart","hospital","hourglass","id-badge","id-card","image","images","keyboard","kiss","kiss-beam","kiss-wink-heart","laugh","laugh-beam","laugh-squint","laugh-wink","lemon","life-ring","lightbulb","list-alt","map","meh","meh-blank","meh-rolling-eyes","minus-square","money-bill-alt","moon","newspaper","object-group","object-ungroup","paper-plane","pause-circle","play-circle","plus-square","question-circle","registered","sad-cry","sad-tear","save","share-square","smile","smile-beam","smile-wink","snowflake","square","star","star-half","sticky-note","stop-circle","sun","surprise","thumbs-down","thumbs-up","times-circle","tired","trash-alt","user","user-circle","window-close","window-maximize","window-minimize","window-restore");
	var $fa_brands = array("500px","accessible-icon","accusoft","acquisitions-incorporated","adn","adobe","adversal","affiliatetheme","algolia","alipay","amazon","amazon-pay","amilia","android","angellist","angrycreative","angular","app-store","app-store-ios","apper","apple","apple-pay","artstation","asymmetrik","atlassian","audible","autoprefixer","avianex","aviato","aws","bandcamp","behance","behance-square","bimobject","bitbucket","bitcoin","bity","black-tie","blackberry","blogger","blogger-b","bluetooth","bluetooth-b","btc","buromobelexperte","canadian-maple-leaf","cc-amazon-pay","cc-amex","cc-apple-pay","cc-diners-club","cc-discover","cc-jcb","cc-mastercard","cc-paypal","cc-stripe","cc-visa","centercode","centos","chrome","cloudscale","cloudsmith","cloudversify","codepen","codiepie","confluence","connectdevelop","contao","cpanel","creative-commons","creative-commons-by","creative-commons-nc","creative-commons-nc-eu","creative-commons-nc-jp","creative-commons-nd","creative-commons-pd","creative-commons-pd-alt","creative-commons-remix","creative-commons-sa","creative-commons-sampling","creative-commons-sampling-plus","creative-commons-share","creative-commons-zero","critical-role","css3","css3-alt","cuttlefish","d-and-d","d-and-d-beyond","dashcube","delicious","deploydog","deskpro","dev","deviantart","dhl","diaspora","digg","digital-ocean","discord","discourse","dochub","docker","draft2digital","dribbble","dribbble-square","dropbox","drupal","dyalog","earlybirds","ebay","edge","elementor","ello","ember","empire","envira","erlang","ethereum","etsy","expeditedssl","facebook","facebook-f","facebook-messenger","facebook-square","fantasy-flight-games","fedex","fedora","figma","firefox","first-order","first-order-alt","firstdraft","flickr","flipboard","fly","font-awesome","font-awesome-alt","font-awesome-flag","fonticons","fonticons-fi","fort-awesome","fort-awesome-alt","forumbee","foursquare","free-code-camp","freebsd","fulcrum","galactic-republic","galactic-senate","get-pocket","gg","gg-circle","git","git-square","github","github-alt","github-square","gitkraken","gitlab","gitter","glide","glide-g","gofore","goodreads","goodreads-g","google","google-drive","google-play","google-plus","google-plus-g","google-plus-square","google-wallet","gratipay","grav","gripfire","grunt","gulp","hacker-news","hacker-news-square","hackerrank","hips","hire-a-helper","hooli","hornbill","hotjar","houzz","html5","hubspot","imdb","instagram","intercom","internet-explorer","invision","ioxhost","itunes","itunes-note","java","jedi-order","jenkins","jira","joget","joomla","js","js-square","jsfiddle","kaggle","keybase","keycdn","kickstarter","kickstarter-k","korvue","laravel","lastfm","lastfm-square","leanpub","less","line","linkedin","linkedin-in","linode","linux","lyft","magento","mailchimp","mandalorian","markdown","mastodon","maxcdn","medapps","medium","medium-m","medrt","meetup","megaport","mendeley","microsoft","mix","mixcloud","mizuni","modx","monero","napster","neos","nimblr","nintendo-switch","node","node-js","npm","ns8","nutritionix","odnoklassniki","odnoklassniki-square","old-republic","opencart","openid","opera","optin-monster","osi","page4","pagelines","palfed","patreon","paypal","penny-arcade","periscope","phabricator","phoenix-framework","phoenix-squadron","php","pied-piper","pied-piper-alt","pied-piper-hat","pied-piper-pp","pinterest","pinterest-p","pinterest-square","playstation","product-hunt","pushed","python","qq","quinscape","quora","r-project","raspberry-pi","ravelry","react","reacteurope","readme","rebel","red-river","reddit","reddit-alien","reddit-square","redhat","renren","replyd","researchgate","resolving","rev","rocketchat","rockrms","safari","sass","schlix","scribd","searchengin","sellcast","sellsy","servicestack","shirtsinbulk","shopware","simplybuilt","sistrix","sith","sketch","skyatlas","skype","slack","slack-hash","slideshare","snapchat","snapchat-ghost","snapchat-square","soundcloud","sourcetree","speakap","spotify","squarespace","stack-exchange","stack-overflow","staylinked","steam","steam-square","steam-symbol","sticker-mule","strava","stripe","stripe-s","studiovinari","stumbleupon","stumbleupon-circle","superpowers","supple","suse","teamspeak","telegram","telegram-plane","tencent-weibo","the-red-yeti","themeco","themeisle","think-peaks","trade-federation","trello","tripadvisor","tumblr","tumblr-square","twitch","twitter","twitter-square","typo3","uber","ubuntu","uikit","uniregistry","untappd","ups","usb","usps","ussunnah","vaadin","viacoin","viadeo","viadeo-square","viber","vimeo","vimeo-square","vimeo-v","vine","vk","vnv","vuejs","weebly","weibo","weixin","whatsapp","whatsapp-square","whmcs","wikipedia-w","windows","wix","wizards-of-the-coast","wolf-pack-battalion","wordpress","wordpress-simple","wpbeginner","wpexplorer","wpforms","wpressr","xbox","xing","xing-square","y-combinator","yahoo","yandex","yandex-international","yarn","yelp","yoast","youtube","youtube-square","zhihu");
	var $sort_methods = array('date-za', 'date-az', 'title-za', 'title-az');
	var $local_fonts = array(
		'inherit' => 'Inherit',
		'arial' => 'Arial',
		'verdana' => 'Verdana'
	);
	var $phone_masks = array(
		'none' => 'None',
		'(000)000-0000' => '(000)000-0000',
		'+0(000)000-0000' => '+0(000)000-0000',
		'+00(000)000-0000' => '+00(000)000-0000',
		'(00)0000-0000' => '(00)0000-0000',
		'custom' => 'Custom Mask'
	);
	var $alignments = array(
		'inherit' => 'Inherit',
		'left' => 'Left',
		'right' => 'Right',
		'center' => 'Center',
		'justify' => 'Justify'
	);
	var $background_repeats = array(
		'repeat' => 'Repeat',
		'repeat-x' => 'Repeat X',
		'repeat-y' => 'Repeat Y',
		'no-repeat' => 'No Repeat'
	);
	var $background_sizes = array(
		'auto' => 'Original',
		'cover' => 'Cover',
		'contain' => 'Contain'
	);
	var $border_styles = array(
		'none' => 'None',
		'dotted' => 'Dotted',
		'dashed' => 'Dashed',
		'solid' => 'Solid',
		'double' => 'Double',
		'groove' => 'Groove',
		'ridge' => 'Ridge',
		'inset' => 'Inset',
		'outset' => 'Outset'
	);
	var $display_modes = array(
		'none' => 'Disable popup',
		'every-time' => 'Every time', 
		'once-session' => 'Once per session',
		'once-period' => 'Once per %X days',
		'once-only' => 'Only once'
	);
	var $appearances = array(
		'fade-in' => 'Fade In',
		'slide-up' => 'Slide Up',
		'slide-down' => 'Slide Down',
		'slide-left' => 'Slide Left',
		'slide-right' => 'Slide Right'
	);
	var $css3_appearances = array(
		'bounceIn' => 'Bounce',
		'bounceInUp' => 'Bounce Up',
		'bounceInDown' => 'Bounce Down',
		'bounceInLeft' => 'Bounce Right',
		'bounceInRight' => 'Bounce Left',
		'fadeIn' => 'Fade',
		'fadeInUp' => 'Fade Up',
		'fadeInDown' => 'Fade Down',
		'fadeInLeft' => 'Fade Right',
		'fadeInRight' => 'Fade Left',
		'flipInX' => 'Flip X',
		'flipInY' => 'Flip Y',
		'lightSpeedIn' => 'Light Speed',
		'rotateIn' => 'Rotate',
		'rotateInDownLeft' => 'Rotate Down Left',
		'rotateInDownRight' => 'Rotate Down Right',
		'rotateInUpLeft' => 'Rotate Up Left',
		'rotateInUpRight' => 'Rotate Up Right',
		'rollIn' => 'Roll',
		'zoomIn' => 'Zoom',
		'zoomInUp' => 'Zoom Up',
		'zoomInDown' => 'Zoom Down',
		'zoomInLeft' => 'Zoom Right',
		'zoomInRight' => 'Zoom Left'
	);
	var $font_weights = array(
		'inherit' => 'Inherit',
		'100' => 'Thin',
		'200' => 'Extra-light',
		'300' => 'Light',
		'400' => 'Normal',
		'500' => 'Medium',
		'600' => 'Demi-bold',
		'700' => 'Bold',
		'800' => 'Heavy',
		'900' => 'Black'
	);
	var $ajax_spinners = array(
		'classic' => '<div class="ulp-spinner ulp-spinner-classic"></div>',
		'chasing-dots' => '<div class="ulp-spinner ulp-spinner-chasing-dots"><div class="ulp-spinner-child ulp-spinner-dot1"></div><div class="ulp-spinner-child ulp-spinner-dot2"></div></div>',
		'circle' => '<div class="ulp-spinner ulp-spinner-circle"><div class="ulp-spinner-circle1 ulp-spinner-child"></div><div class="ulp-spinner-circle2 ulp-spinner-child"></div><div class="ulp-spinner-circle3 ulp-spinner-child"></div><div class="ulp-spinner-circle4 ulp-spinner-child"></div><div class="ulp-spinner-circle5 ulp-spinner-child"></div><div class="ulp-spinner-circle6 ulp-spinner-child"></div><div class="ulp-spinner-circle7 ulp-spinner-child"></div><div class="ulp-spinner-circle8 ulp-spinner-child"></div><div class="ulp-spinner-circle9 ulp-spinner-child"></div><div class="ulp-spinner-circle10 ulp-spinner-child"></div><div class="ulp-spinner-circle11 ulp-spinner-child"></div><div class="ulp-spinner-circle12 ulp-spinner-child"></div></div>',
		'double-bounce' => '<div class="ulp-spinner ulp-spinner-double-bounce"><div class="ulp-spinner-child ulp-spinner-double-bounce1"></div><div class="ulp-spinner-child ulp-spinner-double-bounce2"></div></div>',
		'fading-circle' => '<div class="ulp-spinner ulp-spinner-fading-circle"><div class="ulp-spinner-circle1 ulp-spinner-child"></div><div class="ulp-spinner-circle2 ulp-spinner-child"></div><div class="ulp-spinner-circle3 ulp-spinner-child"></div><div class="ulp-spinner-circle4 ulp-spinner-child"></div><div class="ulp-spinner-circle5 ulp-spinner-child"></div><div class="ulp-spinner-circle6 ulp-spinner-child"></div><div class="ulp-spinner-circle7 ulp-spinner-child"></div><div class="ulp-spinner-circle8 ulp-spinner-child"></div><div class="ulp-spinner-circle9 ulp-spinner-child"></div><div class="ulp-spinner-circle10 ulp-spinner-child"></div><div class="ulp-spinner-circle11 ulp-spinner-child"></div><div class="ulp-spinner-circle12 ulp-spinner-child"></div></div>',
		'folding-cube' => '<div class="ulp-spinner ulp-spinner-folding-cube"><div class="ulp-spinner-cube1 ulp-spinner-child"></div><div class="ulp-spinner-cube2 ulp-spinner-child"></div><div class="ulp-spinner-cube4 ulp-spinner-child"></div><div class="ulp-spinner-cube3 ulp-spinner-child"></div></div>',
		'pulse' => '<div class="ulp-spinner ulp-spinner-spinner-pulse"></div>',
		'rotating-plane' => '<div class="ulp-spinner ulp-spinner-rotating-plane"></div>',
		'three-bounce' => '<div class="ulp-spinner ulp-spinner-three-bounce"><div class="ulp-spinner-child ulp-spinner-bounce1"></div><div class="ulp-spinner-child ulp-spinner-bounce2"></div><div class="ulp-spinner-child ulp-spinner-bounce3"></div></div>',
		'wandering-cubes' => '<div class="ulp-spinner ulp-spinner-wandering-cubes"><div class="ulp-spinner-child ulp-spinner-cube1"></div><div class="ulp-spinner-child ulp-spinner-cube2"></div></div>',
		'wave' => '<div class="ulp-spinner ulp-spinner-wave"><div class="ulp-spinner-child ulp-spinner-rect1"></div><div class="ulp-spinner-child ulp-spinner-rect2"></div><div class="ulp-spinner-child ulp-spinner-rect3"></div><div class="ulp-spinner-child ulp-spinner-rect4"></div><div class="ulp-spinner-child ulp-spinner-rect5"></div></div>'
	);
	
	var $default_popup_options = array(
		"title" => "",
		"width" => "640",
		"height" => "400",
		'position' => 'middle-center',
		'disable_overlay' => 'off',
		"overlay_color" => "#333333",
		"overlay_opacity" => 0.8,
		"overlay_animation" => "fadeIn",
		"ajax_spinner" => "classic",
		"ajax_spinner_color" => "#ffffff",
		"enable_close" => "on",
		"enable_enter" => "on",
		'name_placeholder' => 'Enter your name...',
		'email_placeholder' => 'Enter your e-mail...',
		'phone_placeholder' => 'Enter your phone number...',
		'phone_length' => '',
		'message_placeholder' => 'Enter your message...',
		'email_mandatory' => 'on',
		'name_mandatory' => 'off',
		'phone_mandatory' => 'off',
		'message_mandatory' => 'off',
		'phone_mask' => 'none',
		'phone_custom_mask' => '(000)000-0000',
		'button_label' => 'Subscribe',
		'button_icon' => 'fa-noicon',
		'button_label_loading' => 'Loading...',
		'button_color' => '#0147A3',
		'button_border_radius' => 2,
		'button_gradient' => 'on',
		'button_inherit_size' => 'off',
		'button_css' => '',
		'button_css_hover' => '',
		'input_border_color' => '#444444',
		'input_border_width' => 1,
		'input_border_radius' => 2,
		'input_background_color' => '#FFFFFF',
		'input_background_opacity' => 0.7,
		'input_icons' => 'off',
		'input_css' => '',
		'recaptcha_mandatory' => 'off',
		'recaptcha_theme' => 'light',
		'return_url' => '',
		'close_delay' => 0,
		'thanksgiving_popup' => '',
		'cookie_lifetime' => 360,
		"doubleoptin_enable" => "off",
		"doubleoptin_subject" => "",
		"doubleoptin_message" => "",
		"doubleoptin_confirmation_message" => "",
		"doubleoptin_redirect_url" => ""
	);
	var $default_layer_options = array(
		"title" => "New Layer",
		"content" => "",
		"width" => "",
		"height" => "",
		"scrollbar" => "off",
		"left" => 0,
		"top" => 0,
		"background_color" => "",
		"background_hover_color" => "",
		"background_gradient" => "off",
		"background_gradient_to" => "",
		"background_gradient_angle" => "135",
		"background_hover_gradient_to" => "",
		"background_opacity" => 1,
		"background_image" => "",
		"background_image_repeat" => "repeat",
		"background_image_size" => "auto",
		"border_width" => 1,
		"border_style" => 'none',
		"border_color" => "",
		"border_hover_color" => "",
		"border_radius" => 0,
		"box_shadow" => "off",
		"box_shadow_h" => 0,
		"box_shadow_v" => 5,
		"box_shadow_blur" => 20,
		"box_shadow_spread" => 0,
		"box_shadow_color" => "#202020",
		"box_shadow_inset" => "off",
		"content_align" => "left",
		"padding_v" => 0,
		"padding_h" => 0,
		"index" => 5,
		"appearance" => "fade-in",
		"appearance_delay" => "200",
		"appearance_speed" => "1000",
		"font" => "arial",
		"font_color" => "#000000",
		"font_hover_color" => "",
		"font_weight" => "400",
		"font_size" => 14,
		"text_shadow_size" => 0,
		"text_shadow_color" => "#000000",
		"confirmation_layer" => "off",
		"inline_disable" => "off",
		"style" => ""
	);
	var $ext_options = array(
		'enable_customfields' => 'off',
		'enable_js' => 'off',
		'enable_social' => 'off',
		'enable_social2' => 'off',
		'enable_mailchimp' => 'on',
		'enable_mailgun' => 'off',
		'enable_bitrix24' => 'off',
		'enable_birdsend' => 'off',
		'enable_conversio' => 'off',
		'enable_rapidmail' => 'off',
		'enable_sendfox' => 'off',
		'enable_omnisend' => 'off',
		'enable_dotmailer' => 'off',
		'enable_mnb' => 'off',
		'enable_markethero' => 'off',
		'enable_kirimemail' => 'off',
		'enable_squalomail' => 'off',
		'enable_unisender' => 'off',
		'enable_moosend' => 'off',
		'enable_zohocampaigns' => 'off',
		'enable_zohocrm' => 'off',
		'enable_mailigen' => 'off',
		'enable_sendloop' => 'off',
		'enable_perfit' => 'off',
		'enable_newsletter2go' => 'off',
		'enable_acellemail' => 'off',
		'enable_mailfit' => 'off',
		'enable_streamsend' => 'off',
		'enable_vision6' => 'off',
		'enable_mailleader' => 'off',
		'enable_mpzmail' => 'off',
		'enable_stampready' => 'off',
		'enable_mautic' => 'off',
		'enable_emailoctopus' => 'off',
		'enable_intercom' => 'off',
		'enable_firedrum' => 'off',
		'enable_activetrail' => 'off',
		'enable_userengage' => 'off',
		'enable_jetpack' => 'off',
		'enable_pipedrive' => 'off',
		'enable_sgautorepondeur' => 'off',
		'enable_drip' => 'off',
		'enable_sendlane' => 'off',
		'enable_emma' => 'off',
		'enable_hubspot' => 'off',
		'enable_esputnik' => 'off',
		'enable_thenewsletterplugin' => 'off',
		'enable_klaviyo' => 'off',
		'enable_easysendypro' => 'off',
		'enable_cleverreach' => 'off',
		'enable_mailkitchen' => 'off',
		'enable_rocketresponder' => 'off',
		'enable_salesmanago' => 'off',
		'enable_agilecrm' => 'off',
		'enable_simplycast' => 'off',
		'enable_convertkit' => 'off',
		'enable_totalsend' => 'off',
		'enable_campayn' => 'off',
		'enable_sendinblue' => 'off',
		'enable_sendgrid' => 'off',
		'enable_elasticemail' => 'off',
		'enable_egoi' => 'off',
		'enable_aweber' => 'off',
		'enable_getresponse' => 'off',
		'enable_icontact' => 'off',
		'enable_madmimi' => 'off',
		'enable_campaignmonitor' => 'off',
		'enable_salesautopilot' => 'off',
		'enable_sendy' => 'off',
		'enable_interspire' => 'off',
		'enable_benchmark' => 'off',
		'enable_activecampaign' => 'off',
		'enable_ontraport' => 'off',
		'enable_mailerlite' => 'off',
		'enable_mailrelay' => 'off',
		'enable_mymail' => 'off',
		'enable_fue' => 'off',
		'enable_mailboxmarketing' => 'off',
		'enable_enewsletter' => 'off',
		'enable_arigatopro' => 'off',
		'enable_subscribe2' => 'off',
		'enable_mailpoet' => 'off',
		'enable_tribulant' => 'off',
		'enable_sendpress' => 'off',
		'enable_ymlp' => 'off',
		'enable_freshmail' => 'off',
		'enable_sendreach' => 'off',
		'enable_constantcontact' => 'off',
		'enable_directmail' => 'off',
		'enable_htmlform' => 'off',
		'enable_wpuser' => 'off',
		'enable_mail' => 'on',
		'enable_welcomemail' => 'on',
		'enable_mailwizz' => 'off',
		'enable_mumara' => 'off',
		'enable_avangemail' => 'off',
		'enable_mailautic' => 'off',
		'enable_customerio' => 'off',
		'enable_klicktipp' => 'off',
		'enable_sendpulse' => 'off',
		'enable_mailjet' => 'off',
		'enable_algocheck' => 'on',
		'enable_bulkemailchecker' => 'off',
		'enable_thechecker' => 'off',
		'enable_emaillistverify' => 'off',
		'enable_proofy' => 'off',
		'enable_kickbox' => 'off',
		'enable_clearout' => 'off',
		'enable_neverbounce' => 'off',
		'enable_hunter' => 'off',
		'enable_truemail' => 'off',
		'late_init' => 'off',
		'minified_sources' => 'on',
		'enable_library' => 'on',
		'enable_addons' => 'on',
		'enable_remote' => 'off',
		'admin_only_meta' => 'on',
		'inline_ajaxed' => 'off',
		'log_data' => 'on',
		'advanced_targeting' => 'off',
		'count_impressions' => 'on',
		'async_init' => 'on',
		'clean_database' => 'off'
	);
	var $default_meta = array(
		"version" => ULP_VERSION,
		"onload_mode" => 'default',
		"onload_period" => '5',
		"onload_delay" => 0,
		"onload_close_delay" => 0,
		"onload_popup" => 'default',
		"onload_popup_mobile" => 'default',
		"onexit_mode" => 'default',
		"onexit_period" => '5',
		"onexit_popup" => 'default',
		"onexit_popup_mobile" => 'default',
		"onscroll_popup" => 'default',
		"onscroll_popup_mobile" => 'default',
		"onscroll_mode" => 'default',
		"onscroll_period" => '5',
		"onscroll_offset" => 600,
		"onidle_mode" => 'default',
		"onidle_delay" => 30,
		"onidle_period" => '5',
		"onidle_popup" => 'default',
		"onidle_popup_mobile" => 'default',
		"onabd_mode" => 'default',
		"onabd_period" => '5',
		"onabd_popup" => 'default',
		"onabd_popup_mobile" => 'default'
	);
	var $user_statuses = array(
		ULP_SUBSCRIBER_UNCONFIRMED => array('label' => 'Unconfirmed', 'class' => 'ulp-badge ulp-badge-unconfirmed'),
		ULP_SUBSCRIBER_CONFIRMED => array('label' => 'Confirmed', 'class' => 'ulp-badge ulp-badge-confirmed')
	);
	var $events = array(
		'onload' => array(
			'label' => 'OnLoad',
			'description' => 'Popups are displayed when webpage loaded.'
		),
		'onscroll' => array(
			'label' => 'OnScroll',
			'description' => 'Popups are displayed when user scroll down webpage.'
		),
		'onexit' => array(
			'label' => 'OnExit',
			'description' => 'Popups are displayed when user moves mouse cursor to top edge of browser window, assuming that he/she is going to leave the page.'
		),
		'onidle' => array(
			'label' => 'OnInactivity',
			'description' => 'Popups are displayed when user does nothing on your website (move mouse cursor, press buttons, touch screen) for certain period of time.'
		),
		'onabd' => array(
			'label' => 'OnAdblockDetected',
			'description' => 'Popups are displayed if AdBlock (or similar) software detected.'
		)
	);
	function __construct() {
		global $ulp_admin, $ulp_social2;
		if (function_exists('load_plugin_textdomain')) {
			load_plugin_textdomain('ulp', false, dirname(plugin_basename(__FILE__)).'/languages/');
		}
		$this->plugins_url = plugins_url('', __FILE__);
		
		$url = get_bloginfo('url');
		$domain = parse_url($url, PHP_URL_HOST);
		$this->options = array(
			"version" => ULP_VERSION,
			"webfonts_version" => 0,
			"post_method" => "array",
			"cookie_value" => 'ilovelencha',
			"onload_mode" => 'none',
			"onload_period" => '5',
			"onload_delay" => 0,
			"onload_close_delay" => 0,
			"onload_popup" => '',
			"onload_popup_mobile" => 'same',
			"onexit_mode" => 'none',
			"onexit_period" => '5',
			"onexit_popup" => '',
			"onexit_popup_mobile" => 'same',
			"onscroll_mode" => 'none',
			"onscroll_period" => '5',
			"onscroll_popup" => '',
			"onscroll_popup_mobile" => 'same',
			"onscroll_offset" => 600,
			"onidle_mode" => 'none',
			"onidle_delay" => 30,
			"onidle_period" => '5',
			"onidle_popup" => '',
			"onidle_popup_mobile" => 'same',
			"onabd_mode" => 'none',
			"onabd_period" => '5',
			"onabd_popup" => '',
			"onabd_popup_mobile" => 'same',
			"onexit_limits" => 'off',
			"csv_separator" => ";",
			"email_validation" => "off",
			"ga_tracking" => "off",
			"km_tracking" => "off",
			"css3_enable" => "on",
			"fa_enable" => "off",
			"fa_solid_enable" => "on",
			"fa_regular_enable" => "on",
			"fa_brands_enable" => "on",
			"fa_css_disable" => "off",
			"spinkit_enable" => "on",
			"linkedbuttons_enable" => "on",
			"mask_enable" => "off",
			"mask_js_disable" => "off",
			"recaptcha_enable" => "off",
			"recaptcha_js_disable" => "off",
			"recaptcha_public_key" => "",
			"recaptcha_secret_key" => "",
			"no_preload" => 'on',
			"preload_event_popups" => 'off',
			"from_type" => 'html',
			"from_name" => get_bloginfo('name'),
			"from_email" => "noreply@".str_replace("www.", "", $domain),
			"popups_sort" => 'date-za',
			"campaigns_sort" => 'date-za',
			"subscribers_sort" => 'date-za',
			"purchase_code" => ''
		);

		$this->get_ext_options();
		$this->get_options();
		if (defined('UAP_CORE')) $this->options['no_preload'] = 'on';

		if (!class_exists('SoapClient')) {
			$this->ext_options = array_merge($this->ext_options, array(
				'enable_cleverreach' => 'off',
				'enable_mailkitchen' => 'off'
			));
		}
		if (!in_array('curl', get_loaded_extensions())) {
			$this->ext_options = array_merge($this->ext_options, array(
				'enable_library' => 'off',
				'enable_addons' => 'off',
				'enable_mailchimp' => 'off',
				'enable_mailgun' => 'off',
				'enable_bitrix24' => 'off',
				'enable_birdsend' => 'off',
				'enable_conversio' => 'off',
				'enable_rapidmail' => 'off',
				'enable_sendfox' => 'off',
				'enable_omnisend' => 'off',
				'enable_dotmailer' => 'off',
				'enable_mnb' => 'off',
				'enable_markethero' => 'off',
				'enable_kirimemail' => 'off',
				'enable_squalomail' => 'off',
				'enable_unisender' => 'off',
				'enable_moosend' => 'off',
				'enable_zohocampaigns' => 'off',
				'enable_zohocrm' => 'off',
				'enable_mailigen' => 'off',
				'enable_sendloop' => 'off',
				'enable_perfit' => 'off',
				'enable_newsletter2go' => 'off',
				'enable_acellemail' => 'off',
				'enable_mailfit' => 'off',
				'enable_streamsend' => 'off',
				'enable_vision6' => 'off',
				'enable_mailleader' => 'off',
				'enable_mpzmail' => 'off',
				'enable_stampready' => 'off',
				'enable_mautic' => 'off',
				'enable_emailoctopus' => 'off',
				'enable_intercom' => 'off',
				'enable_firedrum' => 'off',
				'enable_activetrail' => 'off',
				'enable_userengage' => 'off',
				'enable_pipedrive' => 'off',
				'enable_sgautorepondeur' => 'off',
				'enable_sendlane' => 'off',
				'enable_emma' => 'off',
				'enable_hubspot' => 'off',
				'enable_esputnik' => 'off',
				'enable_klaviyo' => 'off',
				'enable_easysendypro' => 'off',
				'enable_rocketresponder' => 'off',
				'enable_salesmanago' => 'off',
				'enable_agilecrm' => 'off',
				'enable_simplycast' => 'off',
				'enable_convertkit' => 'off',
				'enable_totalsend' => 'off',
				'enable_campayn' => 'off',
				'enable_drip' => 'off',
				'enable_sendinblue' => 'off',
				'enable_klicktipp' => 'off',
				'enable_sendpulse' => 'off',
				'enable_mailjet' => 'off',
				'enable_sendgrid' => 'off',
				'enable_elasticemail' => 'off',
				'enable_egoi' => 'off',
				'enable_customerio' => 'off',
				'enable_mailwizz' => 'off',
				'enable_mumara' => 'off',
				'enable_avangemail' => 'off',
				'enable_mailautic' => 'off',
				'enable_icontact' => 'off',
				'enable_getresponse' => 'off',
				'enable_madmimi' => 'off',
				'enable_directmail' => 'off',
				'enable_campaignmonitor' => 'off',
				'enable_salesautopilot' => 'off',
				'enable_activecampaign' => 'off',
				'enable_benchmark' => 'off',
				'enable_sendy' => 'off',
				'enable_interspire' => 'off',
				'enable_ontraport' => 'off',
				'enable_mailerlite' => 'off',
				'enable_mailrelay' => 'off',
				'enable_ymlp' => 'off',
				'enable_sendreach' => 'off',
				'enable_aweber' => 'off',
				'enable_constantcontact' => 'off',
				'enable_htmlform' => 'off',
				'enable_freshmail' => 'off',
				'enable_algocheck' => 'off',
				'enable_truemail' => 'off',
				'enable_bulkemailchecker' => 'off',
				'enable_thechecker' => 'off',
				'enable_emaillistverify' => 'off',
				'enable_proofy' => 'off',
				'enable_kickbox' => 'off',
				'enable_clearout' => 'off',
				'enable_neverbounce' => 'off',
				'enable_hunter' => 'off'
			));
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-algocheck.php') && $this->ext_options['enable_algocheck'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-algocheck.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-bulkemailchecker.php') && $this->ext_options['enable_bulkemailchecker'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-bulkemailchecker.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-clearout.php') && $this->ext_options['enable_clearout'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-clearout.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-emaillistverify.php') && $this->ext_options['enable_emaillistverify'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-emaillistverify.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-hunter.php') && $this->ext_options['enable_hunter'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-hunter.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-kickbox.php') && $this->ext_options['enable_kickbox'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-kickbox.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-neverbounce.php') && $this->ext_options['enable_neverbounce'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-neverbounce.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-proofy.php') && $this->ext_options['enable_proofy'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-proofy.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-thechecker.php') && $this->ext_options['enable_thechecker'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-thechecker.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-truemail.php') && $this->ext_options['enable_truemail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-truemail.php');
		
		if (file_exists(dirname(__FILE__).'/modules/ulp-custom-fields.php') && $this->ext_options['enable_customfields'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-custom-fields.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-social.php') && $this->ext_options['enable_social'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-social.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-social2.php') && $this->ext_options['enable_social2'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-social2.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mail.php') && $this->ext_options['enable_mail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-welcomemail.php') && $this->ext_options['enable_welcomemail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-welcomemail.php');
		
		if (file_exists(dirname(__FILE__).'/modules/ulp-acellemail.php') && $this->ext_options['enable_acellemail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-acellemail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-active-campaign.php') && $this->ext_options['enable_activecampaign'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-active-campaign.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-activetrail.php') && $this->ext_options['enable_activetrail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-activetrail.php');
		
		if (file_exists(dirname(__FILE__).'/modules/ulp-agilecrm.php') && $this->ext_options['enable_agilecrm'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-agilecrm.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-arigatopro.php') && $this->ext_options['enable_arigatopro'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-arigatopro.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-avangemail.php') && $this->ext_options['enable_avangemail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-avangemail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-aweber.php') && $this->ext_options['enable_aweber'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-aweber.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-benchmark.php') && $this->ext_options['enable_benchmark'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-benchmark.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-birdsend.php') && $this->ext_options['enable_birdsend'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-birdsend.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-bitrix24.php') && $this->ext_options['enable_bitrix24'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-bitrix24.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-campaign-monitor.php') && $this->ext_options['enable_campaignmonitor'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-campaign-monitor.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-campayn.php') && $this->ext_options['enable_campayn'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-campayn.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-cleverreach.php') && $this->ext_options['enable_cleverreach'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-cleverreach.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-constant-contact.php') && $this->ext_options['enable_constantcontact'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-constant-contact.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-conversio.php') && $this->ext_options['enable_conversio'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-conversio.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-convertkit.php') && $this->ext_options['enable_convertkit'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-convertkit.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-customerio.php') && $this->ext_options['enable_customerio'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-customerio.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-direct-mail.php') && $this->ext_options['enable_directmail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-direct-mail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-dotmailer.php') && $this->ext_options['enable_dotmailer'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-dotmailer.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-drip.php') && $this->ext_options['enable_drip'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-drip.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-easysendypro.php') && $this->ext_options['enable_easysendypro'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-easysendypro.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-egoi.php') && $this->ext_options['enable_egoi'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-egoi.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-elasticemail.php') && $this->ext_options['enable_elasticemail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-elasticemail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-emailoctopus.php') && $this->ext_options['enable_emailoctopus'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-emailoctopus.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-emma.php') && $this->ext_options['enable_emma'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-emma.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-enewsletter.php') && $this->ext_options['enable_enewsletter'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-enewsletter.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-esputnik.php') && $this->ext_options['enable_esputnik'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-esputnik.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-firedrum.php') && $this->ext_options['enable_firedrum'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-firedrum.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-fue.php') && $this->ext_options['enable_fue'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-fue.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-freshmail.php') && $this->ext_options['enable_freshmail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-freshmail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-getresponse.php') && $this->ext_options['enable_getresponse'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-getresponse.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-hubspot.php') && $this->ext_options['enable_hubspot'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-hubspot.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-icontact.php') && $this->ext_options['enable_icontact'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-icontact.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-intercom.php') && $this->ext_options['enable_intercom'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-intercom.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-interspire.php') && $this->ext_options['enable_interspire'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-interspire.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-jetpack.php') && $this->ext_options['enable_jetpack'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-jetpack.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-kirimemail.php') && $this->ext_options['enable_kirimemail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-kirimemail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-klaviyo.php') && $this->ext_options['enable_klaviyo'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-klaviyo.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-klicktipp.php') && $this->ext_options['enable_klicktipp'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-klicktipp.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mad-mimi.php') && $this->ext_options['enable_madmimi'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mad-mimi.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailautic.php') && $this->ext_options['enable_mailautic'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailautic.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-mailboxmarketing.php') && $this->ext_options['enable_mailboxmarketing'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailboxmarketing.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailchimp.php') && $this->ext_options['enable_mailchimp'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailchimp.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailerlite.php') && $this->ext_options['enable_mailerlite'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailerlite.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailgun.php') && $this->ext_options['enable_mailgun'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailgun.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailfit.php') && $this->ext_options['enable_mailfit'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailfit.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailigen.php') && $this->ext_options['enable_mailigen'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailigen.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailjet.php') && $this->ext_options['enable_mailjet'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailjet.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailkitchen.php') && $this->ext_options['enable_mailkitchen'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailkitchen.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailleader.php') && $this->ext_options['enable_mailleader'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailleader.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-mailpoet.php') && $this->ext_options['enable_mailpoet'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailpoet.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailrelay.php') && $this->ext_options['enable_mailrelay'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailrelay.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-mymail.php') && $this->ext_options['enable_mymail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mymail.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-mailwizz.php') && $this->ext_options['enable_mailwizz'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mailwizz.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-markethero.php') && $this->ext_options['enable_markethero'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-markethero.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mautic.php') && $this->ext_options['enable_mautic'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mautic.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-moosend.php') && $this->ext_options['enable_moosend'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-moosend.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mpzmail.php') && $this->ext_options['enable_mpzmail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mpzmail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mnb.php') && $this->ext_options['enable_mnb'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mnb.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-mumara.php') && $this->ext_options['enable_mumara'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-mumara.php');
		//if (file_exists(dirname(__FILE__).'/modules/ulp-newsletter2go.php') && $this->ext_options['enable_newsletter2go'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-newsletter2go.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-omnisend.php') && $this->ext_options['enable_omnisend'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-omnisend.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-ontraport.php') && $this->ext_options['enable_ontraport'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-ontraport.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-perfit.php') && $this->ext_options['enable_perfit'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-perfit.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-pipedrive.php') && $this->ext_options['enable_pipedrive'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-pipedrive.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-rapidmail.php') && $this->ext_options['enable_rapidmail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-rapidmail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-rocketresponder.php') && $this->ext_options['enable_rocketresponder'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-rocketresponder.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-salesautopilot.php') && $this->ext_options['enable_salesautopilot'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-salesautopilot.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-salesmanago.php') && $this->ext_options['enable_salesmanago'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-salesmanago.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendloop.php') && $this->ext_options['enable_sendloop'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendloop.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendfox.php') && $this->ext_options['enable_sendfox'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendfox.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendgrid.php') && $this->ext_options['enable_sendgrid'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendgrid.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendinblue.php') && $this->ext_options['enable_sendinblue'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendinblue.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendlane.php') && $this->ext_options['enable_sendlane'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendlane.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-sendpress.php') && $this->ext_options['enable_sendpress'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendpress.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendpulse.php') && $this->ext_options['enable_sendpulse'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendpulse.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendreach.php') && $this->ext_options['enable_sendreach'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendreach.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sendy.php') && $this->ext_options['enable_sendy'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sendy.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-sgautorepondeur.php') && $this->ext_options['enable_sgautorepondeur'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-sgautorepondeur.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-simplycast.php') && $this->ext_options['enable_simplycast'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-simplycast.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-squalomail.php') && $this->ext_options['enable_squalomail'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-squalomail.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-stampready.php') && $this->ext_options['enable_stampready'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-stampready.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-streamsend.php') && $this->ext_options['enable_streamsend'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-streamsend.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-subscribe2.php') && $this->ext_options['enable_subscribe2'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-subscribe2.php');
			if (file_exists(dirname(__FILE__).'/modules/ulp-thenewsletterplugin.php') && $this->ext_options['enable_thenewsletterplugin'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-thenewsletterplugin.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-totalsend.php') && $this->ext_options['enable_totalsend'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-totalsend.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-tribulant.php') && $this->ext_options['enable_tribulant'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-tribulant.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-unisender.php') && $this->ext_options['enable_unisender'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-unisender.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-userengage.php') && $this->ext_options['enable_userengage'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-userengage.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-vision6.php') && $this->ext_options['enable_vision6'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-vision6.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-ymlp.php') && $this->ext_options['enable_ymlp'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-ymlp.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-zohocampaigns.php') && $this->ext_options['enable_zohocampaigns'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-zohocampaigns.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-zohocrm.php') && $this->ext_options['enable_zohocrm'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-zohocrm.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-htmlform.php') && $this->ext_options['enable_htmlform'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-htmlform.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-wpuser.php') && $this->ext_options['enable_wpuser'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-wpuser.php');
		}
		if (file_exists(dirname(__FILE__).'/modules/ulp-library.php') && $this->ext_options['enable_library'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-library.php');
		if (file_exists(dirname(__FILE__).'/modules/ulp-js.php') && $this->ext_options['enable_js'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-js.php');
		if (!defined('UAP_CORE')) {
			if (file_exists(dirname(__FILE__).'/modules/ulp-addons.php') && $this->ext_options['enable_addons'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-addons.php');
			if (file_exists(dirname(__FILE__).'/modules/ulp-remote.php') && $this->ext_options['enable_remote'] == 'on') include_once(dirname(__FILE__).'/modules/ulp-remote.php');
		} else {
			include_once(dirname(__FILE__).'/modules/ulp-remote.php');
		}

		if (!empty($_COOKIE["ulp_error"])) {
			$this->error = stripslashes($_COOKIE["ulp_error"]);
			setcookie("ulp_error", "", time()+30, "/", ".".str_replace("www.", "", $domain));
		}
		if (!empty($_COOKIE["ulp_info"])) {
			$this->info = stripslashes($_COOKIE["ulp_info"]);
			setcookie("ulp_info", "", time()+30, "/", ".".str_replace("www.", "", $domain));
		}

		if (function_exists('register_block_type')) {
			add_action('init', array(&$this, 'register_block'));
		}
		if (defined('DOING_AJAX') && DOING_AJAX) {
			include_once(dirname(__FILE__).'/modules/core-ajax.php');
			$ulp_ajax = new ulp_ajax_class();
		} else if (is_admin()) {
			add_action('wpmu_new_blog', array(&$this, 'install_new_blog'), 10, 6);
			add_action('delete_blog', array(&$this, 'uninstall_blog'), 10, 2);
			$this->default_popup_options = array_merge($this->default_popup_options, array(
				"doubleoptin_subject" => __('Confirm your e-mail address', 'ulp'),
				"doubleoptin_message" => __('Dear Friend,', 'ulp').PHP_EOL.PHP_EOL.__('Somebody (probably you) submitted e-mail address {subscription-email} into our list. Please confirm your e-mail address by clicking the link below or igonore this message.', 'ulp').PHP_EOL.'<a href="{confirmation-link}">{confirmation-link}</a>'.PHP_EOL.PHP_EOL.__('Thanks,', 'ulp').PHP_EOL.get_bloginfo("name"),
				"doubleoptin_confirmation_message" => __('Your e-mail address successfully confirmed.', 'ulp')
			));
			include_once(dirname(__FILE__).'/modules/core-admin.php');
			$ulp_admin = new ulp_admin_class();
		} else {
			include_once(dirname(__FILE__).'/modules/core-front.php');
			$ulp_front = new ulp_front_class();
		}
	}

	static function install($_networkwide = null) {
		global $wpdb;
		if (function_exists('is_multisite') && is_multisite()) {
			if ($_networkwide) {
				$old_blog = $wpdb->blogid;
				$blog_ids = $wpdb->get_col('SELECT blog_id FROM '.$wpdb->blogs);
				foreach ($blog_ids as $blog_id) {
					switch_to_blog($blog_id);
					self::activate();
				}
				switch_to_blog($old_blog);
				return;
			}
		}
		self::activate();
	}

	function install_new_blog($_blog_id, $_user_id, $_domain, $_path, $_site_id, $_meta) {
		if (is_plugin_active_for_network(basename(dirname(__FILE__)).'/' ).basename(__FILE__)) {
			switch_to_blog($_blog_id);
			self::activate();
			restore_current_blog();
		}
	}
	
	static function activate() {
		global $wpdb;
		$add_default = false;
		// Create tables for Advanced Targeting - 2017-04-10 - begin
		if (!defined('UAP_CORE')) {
			include_once(dirname(__FILE__).'/modules/core-targeting.php');
			ulp_class_targeting::activate();
		}
		// Create tables for Advanced Targeting - 2017-04-10 - end
		$table_name = $wpdb->prefix."ulp_popups";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL auto_increment,
				str_id varchar(31) collate latin1_general_cs NULL,
				title varchar(255) collate utf8_unicode_ci NULL,
				width int(11) NULL default '640',
				height int(11) NULL default '400',
				options longtext collate utf8_unicode_ci NULL,
				impressions int(11) NULL default '0',
				clicks int(11) NULL default '0',
				created int(11) NULL,
				blocked int(11) NULL default '0',
				deleted int(11) NULL default '0',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
			$add_default = true;
		}
		if ($wpdb->get_var("SHOW COLUMNS FROM ".$wpdb->prefix."ulp_popups LIKE 'impressions'") != 'impressions') {
			$sql = "ALTER TABLE ".$wpdb->prefix."ulp_popups ADD impressions int(11) NULL default '0'";
			$wpdb->query($sql);
		}
		if ($wpdb->get_var("SHOW COLUMNS FROM ".$wpdb->prefix."ulp_popups LIKE 'clicks'") != 'clicks') {
			$sql = "ALTER TABLE ".$wpdb->prefix."ulp_popups ADD clicks int(11) NULL default '0'";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."ulp_layers";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL auto_increment,
				popup_id int(11) NULL,
				title varchar(255) collate utf8_unicode_ci NULL,
				content longtext collate utf8_unicode_ci NULL,
				zindex int(11) NULL default '5',
				details longtext collate utf8_unicode_ci NULL,
				created int(11) NULL,
				deleted int(11) NULL default '0',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."ulp_campaigns";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL auto_increment,
				title varchar(255) collate utf8_unicode_ci NULL,
				str_id varchar(31) collate latin1_general_cs NULL,
				details longtext collate utf8_unicode_ci NULL,
				created int(11) NULL,
				blocked int(11) NULL default '0',
				deleted int(11) NULL default '0',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."ulp_campaign_items";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL auto_increment,
				campaign_id int(11) NULL,
				popup_id int(11) NULL,
				impressions int(11) NULL default '0',
				clicks int(11) NULL default '0',
				created int(11) NULL,
				deleted int(11) NULL default '0',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix . "ulp_subscribers";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL auto_increment,
				popup_id int(11) NULL,
				name varchar(255) collate utf8_unicode_ci NULL,
				email varchar(255) collate utf8_unicode_ci NULL,
				phone varchar(255) collate utf8_unicode_ci NULL,
				message longtext collate utf8_unicode_ci NULL,
				custom_fields longtext collate utf8_unicode_ci NULL,
				status int(11) NULL default '0',
				confirmation_id varchar(31) collate latin1_general_cs NULL,
				created int(11) NULL,
				deleted int(11) NULL default '0',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		/*
		$table_name = $wpdb->prefix . "ulp_stats";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL auto_increment,
				popup_id int(11) NULL,
				event int(11) NULL,
				impressions int(11) NULL,
				clicks int(11) NULL,
				impressions_exposure_time int(11) NULL,
				clicks_exposure_time int(11) NULL,
				time int(11) NULL,
				deleted int(11) NULL default '0',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		*/
		if ($wpdb->get_var("SHOW COLUMNS FROM ".$wpdb->prefix."ulp_subscribers LIKE 'phone'") != 'phone') {
			$sql = "ALTER TABLE ".$wpdb->prefix."ulp_subscribers ADD phone varchar(255) collate utf8_unicode_ci NULL";
			$wpdb->query($sql);
		}
		if ($wpdb->get_var("SHOW COLUMNS FROM ".$wpdb->prefix."ulp_subscribers LIKE 'message'") != 'message') {
			$sql = "ALTER TABLE ".$wpdb->prefix."ulp_subscribers ADD message longtext collate utf8_unicode_ci NULL";
			$wpdb->query($sql);
		}
		if ($wpdb->get_var("SHOW COLUMNS FROM ".$wpdb->prefix."ulp_subscribers LIKE 'custom_fields'") != 'custom_fields') {
			$sql = "ALTER TABLE ".$wpdb->prefix."ulp_subscribers ADD custom_fields longtext collate utf8_unicode_ci NULL";
			$wpdb->query($sql);
		}
		if ($wpdb->get_var("SHOW COLUMNS FROM ".$wpdb->prefix."ulp_subscribers LIKE 'status'") != 'status') {
			$sql = "ALTER TABLE ".$wpdb->prefix."ulp_subscribers ADD status int(11) NULL default '0'";
			$wpdb->query($sql);
		}
		if ($wpdb->get_var("SHOW COLUMNS FROM ".$wpdb->prefix."ulp_subscribers LIKE 'confirmation_id'") != 'confirmation_id') {
			$sql = "ALTER TABLE ".$wpdb->prefix."ulp_subscribers ADD confirmation_id varchar(31) collate latin1_general_cs NULL";
			$wpdb->query($sql);
		}
		$table_name = $wpdb->prefix."ulp_webfonts";
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				id int(11) NOT NULL auto_increment,
				family varchar(255) collate utf8_unicode_ci NULL,
				variants varchar(255) collate utf8_unicode_ci NULL,
				subsets varchar(255) collate utf8_unicode_ci NULL,
				source varchar(31) collate latin1_general_cs NULL,
				deleted int(11) NULL default '0',
				UNIQUE KEY  id (id)
			);";
			$wpdb->query($sql);
		}
		$webfont_version = get_option('ulp_webfonts_version', 0);
		if ($webfont_version < ULP_WEBFONTS_VERSION) {
			include(dirname(__FILE__).'/webfonts.php');
			$webfonts_array = json_decode($fonts, true);
			if (is_array($webfonts_array['items'])) {
				$sql = "DELETE FROM ".$wpdb->prefix."ulp_webfonts";
				$wpdb->query($sql);
				$values = array();
				foreach($webfonts_array['items'] as $fontvars) {
					if (!empty($fontvars['family'])) {
						$variants = '';
						if (!empty($fontvars['variants']) && is_array($fontvars['variants'])) {
							foreach ($fontvars['variants'] as $key => $var) {
									if ($var == 'regular') $fontvars['variants'][$key] = '400';
									if ($var == 'italic') $fontvars['variants'][$key] = '400italic';
							}
							$variants = implode(",", $fontvars['variants']);
						}
						$subsets = '';
						if (!empty($fontvars['subsets']) && is_array($fontvars['subsets'])) {
							$subsets = implode(",", $fontvars['subsets']);
						}
						$values[] = "('".esc_sql($fontvars['family'])."', '".esc_sql($variants)."', '".esc_sql($subsets)."', 'google', '0')";
						if (sizeof($values) > 9) {
							$sql = "INSERT INTO ".$wpdb->prefix."ulp_webfonts (family, variants, subsets, source, deleted) 
									VALUES ".implode(', ', $values);
							$wpdb->query($sql);
							$values = array();
						}
					}
				}
				if (sizeof($values) > 0) {
					$sql = "INSERT INTO ".$wpdb->prefix."ulp_webfonts (family, variants, subsets, source, deleted) 
							VALUES ".implode(', ', $values);
					$wpdb->query($sql);
				}
			}
			update_option('ulp_webfonts_version', ULP_WEBFONTS_VERSION);
		}
		update_option('ulp_version', ULP_VERSION);
		update_option('ulp_ext_clean_database', 'off');
		$upload_dir = wp_upload_dir();
		wp_mkdir_p($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR);
		wp_mkdir_p($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp');
		if (file_exists($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR) && !file_exists($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/index.html')) {
			file_put_contents($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/index.html', 'Silence is the gold!');
		}
		if (file_exists($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp') && !file_exists($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp/index.html')) {
			file_put_contents($upload_dir["basedir"].'/'.ULP_UPLOADS_DIR.'/temp/index.html', 'Silence is the gold!');
		}
		if ($add_default) {
			if (file_exists(dirname(__FILE__).'/default') && is_dir(dirname(__FILE__).'/default')) {
				$dircontent = scandir(dirname(__FILE__).'/default');
				for ($i=0; $i<sizeof($dircontent); $i++) {
					if ($dircontent[$i] != "." && $dircontent[$i] != ".." && $dircontent[$i] != "index.html" && $dircontent[$i] != ".htaccess") {
						if (is_file(dirname(__FILE__).'/default/'.$dircontent[$i])) {
							$lines = file(dirname(__FILE__).'/default/'.$dircontent[$i]);
							if (sizeof($lines) != 3) continue;
							$version = intval(trim($lines[0]));
							if ($version > intval(ULP_EXPORT_VERSION)) continue;
							$md5_hash = trim($lines[1]);
							$popup_data = trim($lines[2]);
							$popup_data = base64_decode($popup_data);
							if (!$popup_data || md5($popup_data) != $md5_hash) continue;
							$popup = unserialize($popup_data);
							$popup_details = $popup['popup'];
							$symbols = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
							$str_id = '';
							for ($j=0; $j<16; $j++) {
								$str_id .= $symbols[rand(0, strlen($symbols)-1)];
							}
							$sql = "INSERT INTO ".$wpdb->prefix."ulp_popups (str_id, title, width, height, options, created, blocked, deleted) 
								VALUES (
								'".$str_id."', 
								'".esc_sql($popup_details['title'])."', 
								'".intval($popup_details['width'])."', 
								'".intval($popup_details['height'])."', 
								'".esc_sql($popup_details['options'])."', 
								'".time()."', '1', '0')";
							$wpdb->query($sql);
							$popup_id = $wpdb->insert_id;
							$layers = $popup['layers'];
							if (sizeof($layers) > 0) {
								foreach ($layers as $layer) {
									$sql = "INSERT INTO ".$wpdb->prefix."ulp_layers (
										popup_id, title, content, zindex, details, created, deleted) VALUES (
										'".$popup_id."',
										'".esc_sql($layer['title'])."',
										'".esc_sql($layer['content'])."',
										'".esc_sql($layer['zindex'])."',
										'".esc_sql($layer['details'])."',
										'".time()."', '0')";
									$wpdb->query($sql);
								}
							}
						}
					}
				}
			}
		}
	}

	static function uninstall() {
		global $wpdb;
		if (function_exists('is_multisite') && is_multisite()) {
			$old_blog = $wpdb->blogid;
			$blog_ids = $wpdb->get_col('SELECT blog_id FROM '.$wpdb->blogs);
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				self::deactivate(false);
			}
			switch_to_blog($old_blog);
		} else {
			self::deactivate(false);
		}
	}

	function uninstall_blog($_blog_id, $_drop) {
		if (is_plugin_active_for_network(basename(dirname(__FILE__)).'/'.basename(__FILE__)) && $_drop) {
			switch_to_blog($_blog_id);
			self::deactivate(true);
			restore_current_blog();
		}
	}
	
	static function deactivate($_force_delete = false) {
		global $wpdb;
		$clean_database = get_option('ulp_ext_clean_database', 'off');
		if ($clean_database == 'on' || $_force_delete) {
			$sql = "DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key LIKE 'ulp_%'";
			$wpdb->query($sql);
			$sql = "DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE 'ulp_%' AND option_name != 'ulp_ext_clean_database'";
			$wpdb->query($sql);
			$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix."ulp_popups";
			$wpdb->query($sql);
			$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix."ulp_layers";
			$wpdb->query($sql);
			$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix."ulp_campaigns";
			$wpdb->query($sql);
			$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix."ulp_campaign_items";
			$wpdb->query($sql);
			$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix."ulp_subscribers";
			$wpdb->query($sql);
			$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix."ulp_webfonts";
			$wpdb->query($sql);
			// Drop tables for Advanced Targeting - 2017-04-10 - begin
			if (!defined('UAP_CORE')) {
				include_once(dirname(__FILE__).'/modules/core-targeting.php');
				ulp_class_targeting::deactivate();
			}
			// Drop tables for Advanced Targeting - 2017-04-10 - end
		}
	}

	function get_ext_options() {
		foreach ($this->ext_options as $key => $value) {
			$this->ext_options[$key] = get_option('ulp_ext_'.$key, $this->ext_options[$key]);
		}
	}

	function update_ext_options() {
		if (current_user_can('manage_options')) {
			foreach ($this->ext_options as $key => $value) {
				update_option('ulp_ext_'.$key, $value);
			}
		}
	}

	function populate_ext_options() {
		foreach ($this->ext_options as $key => $value) {
			if (isset($_POST['ulp_ext_'.$key])) {
				$this->ext_options[$key] = trim(stripslashes($_POST['ulp_ext_'.$key]));
			}
		}
	}

	function get_options() {
		$exists = get_option('ulp_version');
		if ($exists) {
			foreach ($this->options as $key => $value) {
				$this->options[$key] = get_option('ulp_'.$key, $this->options[$key]);
			}
		}
	}

	function update_options() {
		if (current_user_can('manage_options')) {
			foreach ($this->options as $key => $value) {
				update_option('ulp_'.$key, $value);
			}
		}
	}

	function populate_options() {
		foreach ($this->options as $key => $value) {
			if (isset($_POST['ulp_'.$key])) {
				if (in_array($key, array('onload_popup', 'onload_popup_mobile', 'onexit_popup', 'onexit_popup_mobile', 'onscroll_popup', 'onscroll_popup_mobile', 'onidle_popup', 'onidle_popup_mobile', 'onabd_popup', 'onabd_popup_mobile'))) {
					$this->options[$key] = $this->wpml_compile_popup_id(trim(stripslashes($_POST['ulp_'.$key])), $this->options[$key]);
				} else {
					$this->options[$key] = trim(stripslashes($_POST['ulp_'.$key]));
				}
			}
		}
	}

	function get_meta($post_id) {
		$meta = array();
		$version = get_post_meta($post_id, 'ulp_version', true);
		if (empty($version)) $meta = $this->default_meta;
		else {
			foreach($this->default_meta as $key => $value) {
				$meta[$key] = get_post_meta($post_id, 'ulp_'.$key, true);
			}
			if ($version < 3.50) {
				$meta['onload_popup_mobile'] = $this->default_meta['onload_popup_mobile'];
				$meta['onscroll_popup_mobile'] = $this->default_meta['onscroll_popup_mobile'];
				$meta['onexit_popup_mobile'] = $this->default_meta['onexit_popup_mobile'];
			}
			if ($version < 3.71) {
				$meta['onload_period'] = $this->default_meta['onload_period'];
				$meta['onexit_period'] = $this->default_meta['onexit_period'];
				$meta['onscroll_period'] = $this->default_meta['onscroll_period'];
			}
		}
		if (empty($meta['onexit_mode'])) {
			$meta['onexit_mode'] = $this->default_meta['onexit_mode'];
			$meta['onexit_popup'] = $this->default_meta['onexit_popup'];
		}
		if (empty($meta['onscroll_mode'])) {
			$meta['onscroll_mode'] = $this->default_meta['onscroll_mode'];
			$meta['onscroll_popup'] = $this->default_meta['onscroll_popup'];
			$meta['onscroll_offset'] = $this->default_meta['onscroll_offset'];
		}
		if (empty($meta['onidle_mode'])) {
			$meta['onidle_mode'] = $this->default_meta['onidle_mode'];
			$meta['onidle_popup'] = $this->default_meta['onidle_popup'];
			$meta['onidle_popup_mobile'] = $this->default_meta['onidle_popup_mobile'];
			$meta['onidle_delay'] = $this->default_meta['onidle_delay'];
			$meta['onidle_period'] = $this->default_meta['onidle_period'];
		}
		if (empty($meta['onabd_mode'])) {
			$meta['onabd_mode'] = $this->default_meta['onabd_mode'];
			$meta['onabd_popup'] = $this->default_meta['onabd_popup'];
			$meta['onabd_popup_mobile'] = $this->default_meta['onabd_popup_mobile'];
			$meta['onabd_period'] = $this->default_meta['onabd_period'];
		}
		return $meta;
	}

	function render_block($_attributes, $_content) {
		return $_content;
	}
	
	function register_block() {
		wp_register_script('ulp-block', plugins_url('js/block.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-i18n'));
		register_block_type('ulp/inline', array('editor_script' => 'ulp-block'));
	}
	
	function shortcode_handler($_atts) {
		include_once(dirname(__FILE__).'/modules/core-front.php');
		$ulp_front = new ulp_front_class();
		$html = $ulp_front->shortcode_handler($_atts);
		return $html;
	}
	
	function wpml_parse_popup_id($_popup_id, $_default_all_value = '', $_current_language = '') {
		$popup_id = $_popup_id;
		$popups = array('all' => $_default_all_value);
		$pairs = explode(',', $_popup_id);
		foreach($pairs as $pair) {
			$data = explode(':', $pair);
			if (sizeof($data) != 2) $popups['all'] = $data[0];
			else $popups[$data[0]] = $data[1];
		}
		if (!defined('ICL_LANGUAGE_CODE')) $popup_id = $popups['all'];
		else {
			if (!empty($_current_language) && array_key_exists($_current_language, $popups)) $popup_id = $popups[$_current_language];
			else if (array_key_exists(ICL_LANGUAGE_CODE, $popups)) $popup_id = $popups[ICL_LANGUAGE_CODE];
			else $popup_id = $popups['all'];
		}
		return $popup_id;
	}
	
	function wpml_compile_popup_id($_popup_id, $_old) {
		$new = $_popup_id;
		if (defined('ICL_LANGUAGE_CODE')) {
			if (ICL_LANGUAGE_CODE == 'all') {
				$new = $_popup_id;
			} else {
				$popups = array();
				$pairs = explode(',', $_old);
				foreach($pairs as $pair) {
					$data = explode(':', $pair);
					if (sizeof($data) != 2) $popups['all'] = $data[0];
					else $popups[$data[0]] = $data[1];
				}
				$popups[ICL_LANGUAGE_CODE] = $_popup_id;
				$data = array();
				foreach ($popups as $key => $value) {
					$data[] = $key.':'.$value;
				}
				$new = implode(',', $data);
			}
		}
		return $new;
	}
	
	function page_switcher ($_urlbase, $_currentpage, $_totalpages) {
		$pageswitcher = "";
		if ($_totalpages > 1) {
			$pageswitcher = '<div class="tablenav bottom"><div class="tablenav-pages">'.__('Pages:', 'ulp').' <span class="pagiation-links">';
			if (strpos($_urlbase,"?") !== false) $_urlbase .= "&amp;";
			else $_urlbase .= "?";
			if ($_currentpage == 1) $pageswitcher .= "<a class='page disabled'>1</a> ";
			else $pageswitcher .= " <a class='page' href='".$_urlbase."p=1'>1</a> ";

			$start = max($_currentpage-3, 2);
			$end = min(max($_currentpage+3,$start+6), $_totalpages-1);
			$start = max(min($start,$end-6), 2);
			if ($start > 2) $pageswitcher .= " <b>...</b> ";
			for ($i=$start; $i<=$end; $i++) {
				if ($_currentpage == $i) $pageswitcher .= " <a class='page disabled'>".$i."</a> ";
				else $pageswitcher .= " <a class='page' href='".$_urlbase."p=".$i."'>".$i."</a> ";
			}
			if ($end < $_totalpages-1) $pageswitcher .= " <b>...</b> ";

			if ($_currentpage == $_totalpages) $pageswitcher .= " <a class='page disabled'>".$_totalpages."</a> ";
			else $pageswitcher .= " <a class='page' href='".$_urlbase."p=".$_totalpages."'>".$_totalpages."</a> ";
			$pageswitcher .= "</span></div></div>";
		}
		return $pageswitcher;
	}

	function datetime_string($_datetime) {
		$dt = (string)$_datetime;
		if (strlen($dt) != 12) return '';
		return substr($dt, 0, 4).'-'.substr($dt, 4, 2).'-'.substr($dt, 6, 2).' '.substr($dt, 8, 2).':'.substr($dt, 10, 2);
	}
	function filter_lp($_layer_options) {
		foreach ($_layer_options as $key => $value) {
			$_layer_options[$key] = str_replace(array('ULP-DEMO-IMAGES-URL', 'http://datastorage.pw/images'), array(plugins_url('/images/default', __FILE__), plugins_url('/images/default', __FILE__)), $value);
		}
		return $_layer_options;
	}
	
	function filter_lp_reverse($_layer_options) {
		foreach ($_layer_options as $key => $value) {
			$_layer_options[$key] = str_replace(array('http://datastorage.pw/images', plugins_url('/images/default', __FILE__)), array('ULP-DEMO-IMAGES-URL', 'ULP-DEMO-IMAGES-URL'), $value);
		}
		return $_layer_options;
	}
	
	function get_rgb($_color) {
		if (strlen($_color) != 7 && strlen($_color) != 4) return false;
		$color = preg_replace('/[^#a-fA-F0-9]/', '', $_color);
		if (strlen($color) != strlen($_color)) return false;
		if (strlen($color) == 7) list($r, $g, $b) = array($color[1].$color[2], $color[3].$color[4], $color[5].$color[6]);
		else list($r, $g, $b) = array($color[1].$color[1], $color[2].$color[2], $color[3].$color[3]);
		return array("r" => hexdec($r), "g" => hexdec($g), "b" => hexdec($b));
	}

	function admin_modal_html() {
		return '
<div class="ulp-modal-overlay"></div>
<div class="ulp-modal">
	<div class="ulp-modal-content">
		<div class="ulp-modal-message"></div>
		<div class="ulp-modal-buttons">
			<a class="ulp-modal-button" id="ulp-modal-button-ok" href="#" onclick="return false;"><i class="fas fa-check"></i><label></label></a>
			<a class="ulp-modal-button" id="ulp-modal-button-cancel" href="#" onclick="return false;"><i class="fas fa-times"></i><label></label></a>
		</div>
	</div>
</div>';
	}
	
	function random_string($_length = 16) {
		$symbols = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = "";
		for ($i=0; $i<$_length; $i++) {
			$string .= $symbols[rand(0, strlen($symbols)-1)];
		}
		return $string;
	}

	function verify_recaptcha($_response) {
		$request = http_build_query(array(
			'secret' => $this->options['recaptcha_secret_key'],
			'response' => $_response,
			'remoteip' => $_SERVER['REMOTE_ADDR']
		));
		try {
			$curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_HEADER, 0);
								
			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
			if(!$result) return false;
			if (array_key_exists('success', $result)) {
				return $result['success'];
			} else return false;
		} catch (Exception $e) {
			return false;
		}
	}
}
$ulp_social2 = null;
$ulp = new ulp_class();
?>