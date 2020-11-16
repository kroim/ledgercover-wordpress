var g_ppsCurrTour = null
,	g_ppsTourOpenedWithTab = false
,	g_ppsAdminTourDissmissed = false;
jQuery(document).ready(function(){
	setTimeout(function(){
		if(typeof(ppsAdminTourData) !== 'undefined' && ppsAdminTourData.tour) {
			jQuery('body').append( ppsAdminTourData.html );
			ppsAdminTourData._$ = jQuery('#supsystic-admin-tour');
			for(var tourId in ppsAdminTourData.tour) {
				if(ppsAdminTourData.tour[ tourId ].points) {
					for(var pointId in ppsAdminTourData.tour[ tourId ].points) {
						_ppsOpenPointer(tourId, pointId);
						break;	// Open only first one
					}
				}
			}
			for(var tourId in ppsAdminTourData.tour) {
				if(ppsAdminTourData.tour[ tourId ].points) {
					for(var pointId in ppsAdminTourData.tour[ tourId ].points) {
						if(ppsAdminTourData.tour[ tourId ].points[ pointId ].sub_tab) {
							var subTab = ppsAdminTourData.tour[ tourId ].points[ pointId ].sub_tab;
							jQuery('a[href="'+ subTab+ '"]')
								.data('tourId', tourId)
								.data('pointId', pointId);
							var tabChangeEvt = str_replace(subTab, '#', '')+ '_tabSwitch';
							jQuery(document).bind(tabChangeEvt, function(event, selector) {
								if(!g_ppsTourOpenedWithTab && !g_ppsAdminTourDissmissed) {
									var $clickTab = jQuery('a[href="'+ selector+ '"]');
									_ppsOpenPointer($clickTab.data('tourId'), $clickTab.data('pointId'));
								}
							});
						}
					}
				}
			}
		}
	}, 500);
});

function _ppsOpenPointerAndPopupTab(tourId, pointId, tab) {
	g_ppsTourOpenedWithTab = true;
	jQuery('#ppsPopupEditTabs').wpTabs('activate', tab);
	_ppsOpenPointer(tourId, pointId);
	g_ppsTourOpenedWithTab = false;
}
function _ppsOpenPointer(tourId, pointId) {
	var pointer = ppsAdminTourData.tour[ tourId ].points[ pointId ];
	var $content = ppsAdminTourData._$.find('#supsystic-'+ tourId+ '-'+ pointId);
	if(!jQuery(pointer.target) || !jQuery(pointer.target).length)
		return;
	if(g_ppsCurrTour) {
		_ppsTourSendNext(g_ppsCurrTour._tourId, g_ppsCurrTour._pointId);
		g_ppsCurrTour.element.pointer('close');
		g_ppsCurrTour = null;
	}
	if(pointer.sub_tab && jQuery('#ppsPopupEditTabs').wpTabs('getActiveTab') != pointer.sub_tab) {
		return;
	}
	var options = jQuery.extend( pointer.options, {
		content: $content.find('.supsystic-tour-content').html()
	,	pointerClass: 'wp-pointer supsystic-pointer'
	,	close: function() {
			//console.log('closed');
		}
	,	buttons: function(event, t) {
			g_ppsCurrTour = t;
			g_ppsCurrTour._tourId = tourId;
			g_ppsCurrTour._pointId = pointId;
			var $btnsShell = $content.find('.supsystic-tour-btns')
			,	$closeBtn = $btnsShell.find('.close')
			,	$finishBtn = $btnsShell.find('.supsystic-tour-finish-btn');

			if($finishBtn && $finishBtn.length) {
				$finishBtn.click(function(e){
					e.preventDefault();
					jQuery.sendFormPps({
						msgElID: 'noMessages'
					,	data: {mod: 'supsystic_promo', action: 'addTourFinish', tourId: tourId, pointId: pointId}
					});
					g_ppsCurrTour.element.pointer('close');
				});
			}
			if($closeBtn && $closeBtn.length) {
				$closeBtn.bind( 'click.pointer', function(e) {
					e.preventDefault();
					jQuery.sendFormPps({
						msgElID: 'noMessages'
					,	data: {mod: 'supsystic_promo', action: 'closeTour', tourId: tourId, pointId: pointId}
					});
					t.element.pointer('close');
					g_ppsAdminTourDissmissed = true;
				});
			}
			return $btnsShell;
		}
	});
	jQuery(pointer.target).pointer( options ).pointer('open');
	var minTop = 10
	,	pointerTop = parseInt(g_ppsCurrTour.pointer.css('top'));
	if(!isNaN(pointerTop) && pointerTop < minTop) {
		g_ppsCurrTour.pointer.css('top', minTop+ 'px');
	}
}
function _ppsTourSendNext(tourId, pointId) {
	jQuery.sendFormPps({
		msgElID: 'noMessages'
	,	data: {mod: 'supsystic_promo', action: 'addTourStep', tourId: tourId, pointId: pointId}
	});
}