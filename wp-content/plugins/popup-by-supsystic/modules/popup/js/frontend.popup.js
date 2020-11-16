var g_ppsWindowLoaded = false
    ,	g_ppsIsPageCached = false
    ,	g_ppsIsPageCachedChecked = false
    ,	g_ppsShowCallbacks = {}
    ,	g_ppsClks = {};
jQuery(document).ready(function(){
    if(typeof(ppsPopupsFromFooter) !== 'undefined' && ppsPopupsFromFooter && ppsPopupsFromFooter.length) {
        ppsPopups = typeof(ppsPopups) === 'undefined' ? [] : ppsPopups;
        ppsPopups = ppsPopups.concat( ppsPopupsFromFooter );
    }
    jQuery(document).trigger('ppsBeforePopupsStartInit', ppsPopups);
    if(typeof(ppsPopups) !== 'undefined' && ppsPopups && ppsPopups.length) {
        ppsInitBgOverlay();
        jQuery(document).trigger('ppsBeforePopupsInit', ppsPopups);
        for(var i = 0; i < ppsPopups.length; i++) {
            // If in popups lists exist groups, we need combine it to one wrapper for owl-carousel
            if ( ppsPopups[ i ].params.main.groups && ( ppsPopups[ i ].params.main.groups.length > 0 ) ) {
              for (var y = 0; y < ppsPopups[ i ].params.main.groups.length; y++) {
                var newGroupDiv = 'ppsGroupWrapper-' + ppsPopups[ i ].params.main.groups[y];
                var className = newGroupDiv;
                if ( jQuery('.'+className).length > 0 ) {
                  jQuery('.'+className).append( ppsPopups[ i ].rendered_html );
                  jQuery('#ppsPopupShell_'+ppsPopups[ i ].view_id).find('.ppsPopupClose').remove();
                } else {
                  newGroupDiv = '<div class="owl-carousel ppsGroupWrappers ' + className + '"/>';
                  jQuery('body').append( newGroupDiv );
                  jQuery('.'+className).append( ppsPopups[ i ].rendered_html );
                  jQuery('#ppsPopupShell_'+ppsPopups[ i ].view_id).find('.ppsPopupClose').remove();
                  jQuery('.'+className).wrapAll('<div class="ppsGroupOverlay"></div>').wrapAll('<div class="ppsGroupSubOverlay"></div>');
                  jQuery('.ppsGroupSubOverlay').append('<div class="ppsGroupOverlayClose"></div>');
                  jQuery('body').on('click', '.ppsGroupOverlayClose', function() {
                    jQuery(this).closest('.ppsGroupOverlay').fadeOut();
                  });
                }
              }
              // Bind our events
              ppsMovePopupStyles( ppsPopups[ i ] );	/* Move back from replaced style tags - to normal style tag */
              ppsBindPopupLove( ppsPopups[ i ] );
              ppsBindPopupLoad( ppsPopups[ i ] );
              // If one pop-up window is displayed, then others from this group should be displayed in owl-carousel.
              ppsBindPopupShow( ppsPopups[ i ] );
              ppsBindPopupActions( ppsPopups[ i ] );
              // In this case close button - secondary option
              ppsBindPopupClose( ppsPopups[ i ] );
              ppsBindPopupSubscribers( ppsPopups[ i ] );
              ppsBindPopupForceShow( ppsPopups[ i ] );
              ppsCheckPopupGetNotices( ppsPopups[ i ] );
            } else {
              // If we don't use groups
              jQuery('body').append( ppsPopups[ i ].rendered_html );
              ppsMovePopupStyles( ppsPopups[ i ] );	/* Move back from replaced style tags - to normal style tag */
              ppsBindPopupLove( ppsPopups[ i ] );
              ppsBindPopupLoad( ppsPopups[ i ] );
              ppsBindPopupShow( ppsPopups[ i ] );
              ppsBindPopupActions( ppsPopups[ i ] );
              ppsBindPopupClose( ppsPopups[ i ] );
              ppsBindPopupSubscribers( ppsPopups[ i ] );
              ppsBindPopupForceShow( ppsPopups[ i ] );
              ppsCheckPopupGetNotices( ppsPopups[ i ] );
            }
        }

        jQuery('.ppsGroupWrappers').find('link, img.ppsPopupPreloadImg')
        .each(function() {
            var newCloneItem = jQuery(this).clone();
            jQuery(this).remove();
            jQuery('body').append(newCloneItem);
        });
        jQuery('.ppsGroupWrappers').find('.ppsPopupShell').each(function(index) {
            jQuery(this).css({'display':'block','position':'relative','left':'inherit','right':'inherit','top':'inherit','bottom':'inherit'});
        });
        //Init owl-carousel for all created groups
        jQuery('.ppsGroupWrappers').each(function(index) {
          var owlCarousel = jQuery(this).owlCarousel({
            responsiveClass:true,
            loop:false,
            rewind: true,
            responsive:{
                0:{
                    items:1,
                    nav:true,
                    loop:false
                },
            }
          });
          owlCarousel.each(function(index) {
              jQuery(this).find('.owl-nav, .owl-dots').wrapAll("<div class='owl-controls'></div>");
          });
        });

        _ppsBindOnElementClickPopups();
        _ppsBindClickHrefSaving();
        /* To make sure - that we checked all elements, even those who loaded later then current code run */
        setTimeout(_ppsBindOnElementClickPopups, 300);
        jQuery(document).trigger('ppsAfterPopupsInit', ppsPopups);
        jQuery(window).resize(function(){
            for(var i = 0; i < ppsPopups.length; i++) {
                if(ppsPopups[ i ].is_visible) {
                    _ppsPositionPopup({popup: ppsPopups[ i ]});
                    _ppsCloseBtnListenersSafari({popup: ppsPopups[ i ]})
                }
            }
        });
        /* For case when for some reason jQuery(window).load() will not trigger -
        make it work correctly with re-position and re-sizing in any case */
        setTimeout(function(){
            g_ppsWindowLoaded = true;
        }, 5000);
    }
});
jQuery(window).load(function(){
    g_ppsWindowLoaded = true;
    for(var i = 0; i < ppsPopups.length; i++) {
        if(ppsPopups[ i ].is_visible) {
            _ppsPositionPopup({popup: ppsPopups[ i ]});
        }
    }
});
function _ppsAddPopUpClick( popupId, btn ) {
    g_ppsClks[ popupId ] = btn;
}
function _ppsGetPopUpClick( popupId ) {
    return g_ppsClks[ popupId ];
}
function _ppsCheckPopupOnFollowClk( lnk ) {
    var href = lnk.href
        ,	hash = lnk.hash
        ,	wndLocation = window.location.href
        ,	wndHash = window.location.hash;
    if(hash) {
        if(hash && hash != '') {
            href = str_replace(href, hash, '');
        }
        if(wndHash && wndHash != '') {
            wndLocation = str_replace(wndLocation, wndHash, '');
        }
        if(strpos(href, '/', href.length - 1) !== false) {
            href = href.substr(0, href.length - 1);
        }
        if(strpos(wndLocation, '/', wndLocation.length - 1) !== false) {
            wndLocation = wndLocation.substr(0, wndLocation.length - 1);
        }
        if(wndLocation == href) {
            setTimeout(function(){
                toeReload();
            }, 100);
        }
    }
}
function _ppsBindOnElementClickPopups() {
    var $clickOnLinks = jQuery('[href*="#ppsShowPopUp_"]:not(.ppsClickBinded)');
    if($clickOnLinks && $clickOnLinks.length) {
        $clickOnLinks.each(function(){
            jQuery(this).click(function(){
                var popupId = jQuery(this).attr('href');
                if(popupId && popupId != '') {
                    popupId = popupId.split('_');
                    popupId = popupId[1] ? parseInt(popupId[1]) : 0;
                    if(popupId) {
                        var popup = ppsGetPopupById( popupId );
                        if(popup && popup.params.main.show_on == 'link_follow') {
                            _ppsCheckPopupOnFollowClk( this );
                            return true;
                        }
                        _ppsAddPopUpClick( popupId, this );
                        if(!_ppsPopupBindDelay(popupId, 'show_on_click_on_el_delay', 'show_on_click_on_el_enb_delay')) {
                            ppsShowPopup( popupId );
                        }
                    } else {
                        _ppsCheckPopupOnFollowClk( this );
                        return true;
                    }
                }
                return false;
            });
        }).addClass('ppsClickBinded');
    }
    var $clickOnMenuItems = jQuery('[title*="#ppsShowPopUp_"]:not(.ppsClickBinded)');	/* You can also set this in title - for menu items for example */
    if($clickOnMenuItems && $clickOnMenuItems.length) {
        $clickOnMenuItems.each(function(){
            var title = jQuery(this).attr('title')
                ,	matched = title.match(/#ppsShowPopUp_(\d+)/);
            if(matched && matched.length == 2) {
                var popupId = parseInt(matched[1]);
                if(popupId) {
                    jQuery(this)
                        .data('popup-id', popupId)
                        .attr('title', str_replace(title, matched[0], ''))
                        .click(function(){
                            var popupId = jQuery(this).data('popup-id');
                            _ppsAddPopUpClick( popupId, this );
                            if(!_ppsPopupBindDelay(popupId, 'show_on_click_on_el_delay', 'show_on_click_on_el_enb_delay')) {
                                ppsShowPopup( popupId );
                            }
                            return false;
                        });
                }
            }
        }).addClass('ppsClickBinded');
    }
}
function ppsMovePopupStyles( popup ) {
    var $style = jQuery('<style type="text/css" />')
        ,	$replacerTag = jQuery('#ppsPopupStylesHidden_'+ popup.view_id);
    $style.appendTo('body').html( $replacerTag.html() );
    $replacerTag.remove();
}
function ppsBindPopupLove( popup ) {
    if(parseInt(toeOptionPps('add_love_link'))) {
        var $shell = ppsGetPopupShell( popup );
        $shell.append( toeOptionPps('love_link_html') );
    }
}
function ppsBindPopupLoad( popup ) {
    var preloadImgs = jQuery('.ppsPopupPreloadImg_'+ popup.view_id);
    popup._imgsCount = preloadImgs.length;
    if(popup._imgsCount) {
        popup._imgsLoaded = false;
        popup._imgsLoadedCount = 0;
        preloadImgs.bind('load error', function(){
            popup._imgsLoadedCount++;
            if(popup._imgsLoadedCount >= popup._imgsCount) {
                popup._imgsLoaded = true;
                var $shell = ppsGetPopupShell( popup );
                $shell.trigger('ppsShowPopupAfterAllImgs', popup);
            }
        });
    } else {
        if(toeInArrayPps(popup.type, ['iframe', 'pdf'])) {
            popup._imgsLoaded = false;
            var $shell = ppsGetPopupShell( popup );
            $shell.find('.ppsMainFrame').bind('load error', function(){
                var wasTriggeredBefore = popup._imgsLoaded;
                popup._imgsLoaded = true;
                if(!wasTriggeredBefore) {
                    setTimeout(function(){
                        $shell.trigger('ppsShowPopupAfterAllImgs', popup);
                    }, 1000);	/* This timeout need to be here - to wait until loaded frame will be correctly rendered */
                }
            });
        } else {
            popup._imgsLoaded = true;
        }
    }
    /* Additional re-calculation for case if there are too much images */
    var $shell = ppsGetPopupShell( popup )
        ,	$imgs = $shell.find('img')
        ,	imgsCnt = $imgs ? $imgs.length : 0;
    if(imgsCnt) {
        if( popup._imgsCount ) {
            var substracted = false;
            preloadImgs.each(function(){
                var $preload = jQuery(this)
                    ,	preloadSrc = $preload.attr('src');
                $imgs.each(function(){
                    var $img = jQuery( this )
                        ,	imgSrc = $img.attr('src');
                    if(imgSrc == preloadSrc) {	/*Preload is same as image - don't count it*/
                        $imgs = $imgs.filter( this );
                        substracted = true;
                        return false;
                    }
                });
            });
            if( substracted ) {
                imgsCnt = $imgs.length;
            }
        }
        if( imgsCnt ) {
            $imgs.bind('load error', function(){
                imgsCnt--;
                if(imgsCnt <= 0 && popup.is_visible) {
                    _ppsPositionPopup({shell: $shell, popup: popup, recalc: true});
                }
            });
        }
    }
}
function ppsBindPopupShow( popup ) {
    _ppsCheckBindVideo({popup: popup});
    switch(popup.params.main.show_on) {
        case 'page_load':
            if(!_ppsPopupBindDelay(popup, 'show_on_page_load_delay', 'show_on_page_load_enb_delay')) {
                if(popup.type == 'fb_like') {	/* FB Like will be rendered right after all widget content - will be loaded */
                    popup.render_with_fb_load = true;
                } else {
                    ppsCheckShowPopup( popup );
                }
            }
            break;
        case 'click_on_page':
            jQuery(document).click(function(){
                if(!popup.click_on_page_displayed) {
                    ppsCheckShowPopup( popup );
                    popup.click_on_page_displayed = true;
                }
            });
            break;
        case 'click_on_element':
            /* @see _ppsBindOnElementClickPopups() */
            break;
        case 'scroll_window':
            jQuery(window).scroll(function(){
                if(parseInt(popup.params.main.show_on_scroll_window_enb_perc_scroll)) {
                    var percScroll = parseInt( popup.params.main.show_on_scroll_window_perc_scroll );
                    if(percScroll) {
                        var docHt = jQuery(document).height()
                            ,	wndHt = jQuery(window).height()
                            ,	wndScrollPos = jQuery(window).scrollTop()
                            ,	wndScrollHt = docHt - wndHt
                            ,	currScrollPerc = wndScrollPos * 100 / wndScrollHt;
                        if(wndScrollHt > 0 && currScrollPerc < percScroll) {
                            return;
                        }
                    }
                }
                if(!popup.scroll_window_displayed) {
                    var delay = 0;
                    if(popup.params.main.show_on_scroll_window_enb_delay && parseInt(popup.params.main.show_on_scroll_window_enb_delay)) {
                        popup.params.main.show_on_scroll_window_delay = parseInt( popup.params.main.show_on_scroll_window_delay );
                        if(popup.params.main.show_on_scroll_window_delay) {
                            delay = popup.params.main.show_on_scroll_window_delay * 1000;
                        }
                    }
                    if(delay) {
                        setTimeout(function(){
                            ppsCheckShowPopup( popup );
                        }, delay);
                    } else {
                        ppsCheckShowPopup( popup );
                    }
                    popup.scroll_window_displayed = true;
                }
            });
            break;
    }
}
function ppsBindPopupClose( popup ) {
    /* For now - only one method - click on close btn */
    var $shell = ppsGetPopupShell( popup );
    $shell.find('.ppsPopupClose').click(function(){
        ppsClosePopup( popup );
        return false;
    });
}
function ppsBindPopupSubscribers(popup) {
    if(popup.params.tpl.enb_subscribe) {
        var $shell = ppsGetPopupShell( popup )
            ,	$form = $shell.find('.ppsSubscribeForm')
            ,	$inFormCloseBtn = $form.find('.ppsPopupClose');
        switch(popup.params.tpl.sub_dest) {
            case 'aweber':
                /* No ajax action here */
                break;
            case 'wordpress': case 'mailchimp': case 'mailpoet': default:
            $form.submit(function(){
                var submitBtn = jQuery(this).find('input[type=submit]')
                    ,	self = this
                    ,	msgEl = jQuery(this).find('.ppsSubMsg');
                submitBtn.attr('disabled', 'disabled');
                jQuery(this).sendFormPps({
                    msgElID: msgEl
                    ,	onSuccess: function(res){
                        jQuery(self).find('input[type=submit]').removeAttr('disabled');
                        if(!res.error) {
                            var parentShell = jQuery(self).parents('.ppsSubscribeShell')
                                ,	closeInsideBtn = jQuery(self).find('.ppsPopupClose');	/* Close button can be inside form - we can't remove it, because in this case user will not be able to close PopUp */
                            if(closeInsideBtn && closeInsideBtn.length) {
                                closeInsideBtn.appendTo( parentShell );
                            }
                            msgEl.appendTo( parentShell );
                            jQuery(self).animateRemovePps(300, function(){
                                _ppsPositionPopup({shell: $shell, popup: popup, recalc: true});
                            });
                            var $hideAfterSubscribe = $shell.find('.ppsHideAfterSubscribe');
                            if($hideAfterSubscribe && $hideAfterSubscribe.length) {
                                $hideAfterSubscribe.animateRemovePps( 300 );
                            }
                            ppsPopupSubscribeSuccess( popup );
                            var redirectTo = popup.params.sub_redirect_to_btn_url_href ? popup.params.sub_redirect_to_btn_url_href : false;
                            if(!redirectTo && res.data && res.data.redirect) {
                                redirectTo = res.data.redirect;
                            }
                            if(redirectTo) {
                                toeRedirect(redirectTo, parseInt(popup.params.tpl.sub_redirect_new_wnd));
                            }

                        } else {
                            _ppsPopupAddStat( popup, 'subscribe_error' );	/* Save close popup statistics */
                            if(res.data && res.data.emailExistsRedirect) {
                                /* Simulate here client side subscribe success if email already exists */
                                ppsPopupSubscribeSuccess(popup, {ignoreSendStat: true});
                                if(res.data.emailExistsRedirect.indexOf('http') !== 0) {
                                    res.data.emailExistsRedirect = window.location.href+ res.data.emailExistsRedirect;
                                }
                                toeRedirect( res.data.emailExistsRedirect );
                            }
                        }
                    }
                });
                return false;
            });
            break;
        }
        /* If user even press Enter on exit btn in form -let it still submit it.
         Close only if user click it using mouse*/
        if($inFormCloseBtn && $inFormCloseBtn.length) {
            $inFormCloseBtn.keydown(function (e) {
                if (e.keyCode == 13) {	/* Enter */
                    $form.submit();
                    return false;
                }
            });
        }
        /* Required fields validation for Safary browser */
        if(navigator.userAgent.indexOf('Safari') > -1 && !(navigator.userAgent.indexOf('Chrome') > -1)) {
            var forms = $shell.get(0).getElementsByTagName('form');
            if(forms && forms.length) {
                for (var i = 0; i < forms.length; i++) {
                    forms[i].noValidate = true;
                    forms[i].addEventListener('submit', function(event) {
                        if (!event.target.checkValidity()) {
                            event.preventDefault();
                            jQuery(this).find('.ppsSubMsg').addClass('ppsErrorMsg').html('Please fill-in all fields');
                        }
                    }, false);
                }
            }
        }
    }
}
/**
 * Will check - was popup shown before and it's setting, and deside - should it be shown now or not
 * @param {mixed} popup Popup object or it's ID
 */
function ppsCheckShowPopup( popup, params ) {
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    var showKey = 'pps_show_'+ popup.id
        ,	prevShow = getCookiePps( showKey )
        ,	countTimes = popup.params.main.show_to == 'count_times'
        ,	timesShowedKey = 'pps_times_showed_'+ popup.id
        ,	timesShowed = parseInt(getCookiePps(timesShowedKey));
    if(popup.params.main.show_to == 'first_time_visit' && prevShow)
        return;
    if(countTimes && prevShow && prevShow != '1') {
        var prevShowStamp = (new Date( prevShow )).getTime()
            ,	currStamp = (new Date()).getTime();
        if(prevShowStamp) {
            var diff = (currStamp - prevShowStamp) / 1000	// Miliseconds to seconds
                ,	hour = 3600
                ,	needShowTimes = parseInt( popup.params.main.count_times_num );
            if(timesShowed && needShowTimes && timesShowed >= needShowTimes) {
                if((popup.params.main.count_times_mes == 'hour' && diff < hour)
                    || (popup.params.main.count_times_mes == 'day' && diff < 24 * hour)
                    || (popup.params.main.count_times_mes == 'week' && diff < 7 * 24 * hour)
                    || (popup.params.main.count_times_mes == 'month' && diff < 30 * 24 * hour)
                ) {
                    return;
                }
                if(timesShowed >= needShowTimes) {
                    timesShowed = 0;
                }
            }
        }
    }
    if(!prevShow || countTimes) {
        var saveCookieTime = parseInt(popup.params.main.show_to_first_time_visit_days);
        saveCookieTime = isNaN(saveCookieTime) || countTimes ? 30 : saveCookieTime;
        if(!saveCookieTime)
            saveCookieTime = null;	// Save for current session only
        var saveMark = countTimes ? (new Date()).toString() : '1';
        setCookiePps('pps_show_'+ popup.id, saveMark, saveCookieTime);	// Save just flag only in cookie for now
        if(countTimes) {
            setCookiePps(timesShowedKey, (timesShowed ? timesShowed + 1 : 1), 30);
        }
    }
    var actionDone = _ppsPopupGetActionDone( popup );
    if(popup.params.main.show_to == 'until_make_action' && actionDone)
        return;
    if(_ppsCheckDisplayTime( popup ))
        return;
    if(popup.params.main.show_to == 'until_email_confirm'
        && typeof(ppsCheckEmailConfirmed) === 'function'
        && ppsCheckEmailConfirmed(popup)
    ) {
        return;
    }
    params = params || {};
    params.isUnique = prevShow ? 0 : 1;
    ppsShowPopup(popup, params);
    return false;
}
/**
 * Check popup show time - "Time display settings" in admin area
 * @param {mixed} popup Popup object or it's ID
 */
function _ppsCheckDisplayTime( popup ) {
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    if(popup.params.main.enb_show_time
        && popup.params.main.show_time_from
        && popup.params.main.show_time_to
        && popup.params.main.show_time_from != popup.params.main.show_time_to
    ) {
        var timeToNum = function(timeStr) {
            var add = strpos(timeStr, 'pm') !== false ? 12 : 0;
            var time = parseFloat(str_replace(str_replace(str_replace(timeStr, 'am', ''), 'pm', ''), ':', '.'));
            if(toeInArray(time, [12, 12.3]) === -1) {
                time += add;
            } else if(!add) {
                time -= 12;
            }
            return time;
        };
        var timeFrom = timeToNum(popup.params.main.show_time_from)
            ,	timeTo = timeToNum(popup.params.main.show_time_to)
            ,	currDate = new Date()
            ,	currTime = currDate.getHours() + (currDate.getMinutes() / 100);

        if(currTime < timeFrom || currTime > timeTo) {
            return true;
        }
    }
    return false;
}
/**
 * Check - was action done in this popup or not (any action will be checked)
 * @param {mixed} popup Popup object or it's ID
 */
function _ppsPopupGetActionDone( popup ) {
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    var actionsKey = 'pps_actions_'+ popup.id
        ,	actions = getCookiePps( actionsKey );
    if(actions) {
        if(popup.type == 'age_verify' && actions.age_verify && popup.params.opts_attrs.btns_number) {
            // Count action done for Age Berify types only if primary was clicked
            // if there are button, marked as primary in admin are.
            // If there are no such - it will be counted as Done for any button
            var primaryBtnExists = false;
            for(var i = 0; i < popup.params.opts_attrs.btns_number; i++) {
                if(popup.params.tpl['is_btn_primary_'+ i]) {
                    if(i == actions.age_verify) {
                        return true;
                    }
                    primaryBtnExists = true;
                }
            }
            // Do not break anything in current users workflow
            if(primaryBtnExists)
                return false;
        }
        // TODO: make priority check here - if subscribe enabled and user just shared popup - return false
        return true;
    }
    return false;
}
/**
 * Set done action in popup
 * @param {mixed} popup Popup object or it's ID
 * @param {type} action Action that was done
 */
function _ppsPopupSetActionDone( popup, action, smType, params ) {
    params = params || {};
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    smType = smType !== null ? smType : '';
    var actionsKey = 'pps_actions_'+ popup.id
        ,	actions = getCookiePps( actionsKey );
    if(!actions)
        actions = {};
    // Save btn ID for age verify popups
    if(action == 'age_verify') {
        actions[ action ] = smType;
    } else
        actions[ action ] = 1;	// Save only flags for now
    var saveCookieTime = parseInt(popup.params.main.show_to_until_make_action_days);
    saveCookieTime = isNaN(saveCookieTime) ? 30 : saveCookieTime;
    if(!saveCookieTime)
        saveCookieTime = null;	// Save for current session only
    setCookiePps(actionsKey, actions, saveCookieTime);
    if(!params.ignoreSendStat) {
        _ppsPopupAddStat( popup, action, smType );
    }
    jQuery(document).trigger('ppsAfterPopupsActionDone', {popup: popup, action: action, smType: smType});
}
function _ppsPopupAddStat( popup, action, smType, isUnique ) {
    if(popup && popup.params && popup.params.tpl.dsbl_stats)	// Stats is disabled
        return;
    jQuery.sendFormPps({
        msgElID: 'noMessages'
        ,	data: {mod: 'statistics', action: 'add', id: popup.id, type: action, sm_type: smType, is_unique: isUnique, 'connect_hash': popup.connect_hash}
    });
    jQuery(document).trigger('ppsAfterPopupsStatAdded', {popup: popup, action: action, smType: smType, is_unique: isUnique});
}
function ppsShowPopUpOnClick( popup, element ) {
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    _ppsSaveClickHref(popup, jQuery(element));
    ppsShowPopup( popup );
}
function __ppsDisplayShell(params) {
    params.shell.show();
    _ppsTriggerIframeScrollByHash(params);
    setTimeout(function(){
        if(navigator.userAgent.indexOf("Safari") > -1 && navigator.userAgent.indexOf('Chrome') == -1 && params.shell.find('.ppsMainFrame').length) {
            params.shell.css({
                'overflow-y': 'auto',
                '-webkit-overflow-scrolling': 'touch',
                'border': params.shell.find('.ppsMainFrame').css('border'),
                'border-radius': params.shell.find('.ppsMainFrame').css('border-radius')
            });
            params.shell.find('.ppsMainFrame').css({
                'width': params.shell.width() + 'px',
                'max-width':  params.shell.width() + 'px',
                'height': params.shell.height(),
                'border': 'none',
                'border-radius': '0'
            });
            _ppsCloseBtnPositionSafari(params);
        }
    }, 10);
    // For FB PopUp only
    if(params.shell.find('.fb-page iframe:first').length > 0) {
        setTimeout(function(){
            _ppsPositionPopup(params);
        }, 500);
    }
}
function __ppsHideShell(params) {
    params.shell.hide();
}
function _ppsTriggerIframeScrollByHash(params) {
    var iframe = params.shell.find('.ppsMainFrame');

    if(iframe.length) {
        iframe.each(function() {
            var frame = jQuery(this)
                ,	frameSrc = frame.attr('src')
                ,	hashArr = frameSrc.split('#');
            if(hashArr.length && hashArr[1]) {
                setTimeout(function() {
                    iframe.get(0).contentWindow.location.href = frameSrc;
                }, 200);
            }
        });
    }
}
function _ppsCloseBtnListenersSafari(params) {
    params.shell = ppsGetPopupShell(params.popup);
    if(navigator.userAgent.indexOf("Safari") > -1 && navigator.userAgent.indexOf('Chrome') == -1 && params.shell.find('.ppsMainFrame').length) {
        jQuery(window).on('resize', function() {
            _ppsCloseBtnPositionSafari(params);
        });
        jQuery(window).on('orientationchange', function() {
            _ppsCloseBtnPositionSafari(params);
        });
        jQuery('#ppsPopupBgOverlay').click(function(){
            if(ppsPopups && ppsPopups.length) {
                for(var i = 0; i < ppsPopups.length; i++) {
                    if(ppsPopups[ i ]
                        && ppsPopups[ i ].params
                        && ppsPopups[ i ].params.main
                        && ppsPopups[ i ].params.main.close_on
                        && ppsPopups[ i ].params.main.close_on == 'user_close'
                        && ppsPopups[ i].view_id == popup.view_id
                    ) {
                        ppsClosePopup( ppsPopups[ i ] );
                    }
                }
            }
        });
    }
}
function _ppsCloseBtnPositionSafari(params) {
    var $shell = jQuery('#ppsPopupShell_'+ params.popup.view_id);
    $shell.find('.ppsPopupClose').css({
        'position': 'fixed',
        'top': parseInt($shell.css('top') + 20) + 'px',
        'left': (parseInt($shell.css('left')) + $shell.width() - $shell.find('.ppsPopupClose').width() - 10) + 'px'
    });
}
/**
 * Show popup
 * @param {mixed} popup Popup object or it's ID
 * @param {objext} params Additional parameters to display
 */
//var owlAllreadyInit = false;
function ppsShowPopup( popup, params ) {
   if(isNumericPps( popup ))
      popup = ppsGetPopupById( popup );

     if (popup && popup.params.main.hide_other) {
         jQuery('.ppsPopupShell').hide();
         jQuery('.ppsPopupBgOverlay').hide();
     }

    if (popup && popup.params.main.groups && popup.params.main.groups.length > 0) {
      //Show group overlay
      jQuery('#ppsPopupShell_'+popup.view_id).closest('.ppsGroupOverlay').show();
      //Show all popup from one group
      jQuery('.ppsGroupWrappers').find('.ppsPopupShell').each(function(index) {
          jQuery(this).css({'display':'none','position':'relative','left':'inherit','right':'inherit','top':'inherit','bottom':'inherit'});
      });
      setTimeout(function(){
        jQuery('.ppsGroupWrappers').find('.ppsPopupShell').each(function(index) {
            jQuery(this).css({'display':'block','position':'relative','left':'inherit','right':'inherit','top':'inherit','bottom':'inherit'});
        });
      }, 100);
      jQuery('.ppsGroupWrappers').trigger('to.owl.carousel', jQuery('#ppsPopupShell_'+popup.view_id).parent().index());
    }

    if(!ppsCorrectJqueryUsed()) {
        ppsReloadCoreJs(ppsShowPopup, [popup, params]);
        return;
    }
    params = params || {};
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    var $shell = ppsGetPopupShell( popup );
    if(!popup._imgsLoaded) {
        $shell.bind('ppsShowPopupAfterAllImgs', function(){
            ppsShowPopup( popup, params );
        });
        return;
    }
    $shell.data('view-id', popup.view_id);
    if(_ppsCheckIsPageCached()) {
        _ppsUpdatePopupNonces( popup );
    }
    _ppsPopupAddStat( popup, 'show', 0, params.isUnique );	// Save show popup statistics
    if(!params.ignoreBgOverlay) {
        ppsShowBgOverlay( popup );
    }
    if(g_ppsWindowLoaded && !params.ignorePosition) {
        _ppsPositionPopup({shell: $shell, popup: popup});
    }
    if(popup.params.tpl.anim && !popup.resized_for_wnd) {
        _ppsHandlePopupAnimationShow( popup, $shell );
    } else {
        __ppsDisplayShell({shell: $shell, popup: popup});
    }
    // For iFrames - we need to reset it's width - to make sure that it fit correct
    if(toeInArrayPps(popup.type, ['iframe'])) {
        var $frame = $shell.find('.ppsMainFrame');
        if($frame && $frame.length) {
            $frame.css('width', 'auto');
            $frame.css('width', '100%');
        }
    }
    _ppsCheckPlayVideo({popup: popup, shell: $shell});
    _ppsIframesForReload({popup: popup, shell: $shell});
    _ppsCheckInnerScripts({popup: popup, shell: $shell});
    // timeout is to make sure that fronted.gmap.js loaded for this time - because exactly there we have defined maps data
    setTimeout(function(){
        _ppsCheckMap({popup: popup, shell: $shell});
        _ppsSocialIcons({popup: popup, shell: $shell});
        _ppsCheckContactForm({popup: popup, shell: $shell});
        _ppsCheckVideos({popup: popup, shell: $shell});
    }, 100);
    // we not use timeout because we need to hide publication reinit
    _ppsCheckPublication({popup: popup, shell: $shell});
    // Check disable wnd scrolling
    if(popup.params.tpl.dsbl_wnd_scroll) {
        disableScrollPps('html');
        disableScrollPps('body');
    }
    popup.is_visible = true;
    popup.is_rendered = true;	// Rendered at least one time
    jQuery(document).trigger('ppsAfterPopupsActionShow', popup);
    runShowClb( popup, $shell );	// Run all additional added show callback functions if such was added

    if(toeInArrayPps(popup.type, ['iframe'])) {
        if(popup.params.tpl.iframe_display_only
            && popup.params.tpl.iframe_display_only != ''
            && typeof(_ppsIFrameDisplayOnly) == 'function'
            && !popup.params.tpl._iframeDisplayOnlyBinded
        ) {
            _ppsIFrameDisplayOnly( popup );
        }
    }
    if(popup.type == 'video') {
      var $iframe = $shell.find('iframe:first');
      $iframe.attr('style', '');
    }
}
function _ppsCheckVideos( params ) {
    if(params.popup.type == 'video'
        && params.popup.params.tpl.video_type == 'youtube'
        && params.popup.params.tpl.video_autoplay
    ) {
        // I know about youtube API, but it just didn't worked:
        // player.playVideo() was undefined all time
        var $iframe = params.shell.find('iframe:first');
        $iframe.attr('src', $iframe.attr('src')+ '&autoplay=1');
    }
}
function _ppsHandlePopupAnimationShow( popup, $shell ) {
    var preAnimClass = popup.params.tpl.anim.old ? 'magictime' : 'animated';
    $shell.animationDuration( popup.params.tpl.anim_duration, true );
    var hideClass = popup.params.tpl.anim_close
        ? popup.params.tpl.anim_close.hide_class
        : popup.params.tpl.anim.hide_class;
    $shell.removeClass(hideClass);
    $shell.addClass(preAnimClass+ ' '+ popup.params.tpl.anim.show_class);
    __ppsDisplayShell({shell: $shell, popup: popup});
    // This need to make properly work responsivness
    setTimeout(function(){
        $shell.removeClass(preAnimClass+ ' '+ popup.params.tpl.anim.show_class);
    }, parseInt(popup.params.tpl.anim_duration));
}
function _ppsHandlePopupAnimationHide( popup, $shell ) {
    var preAnimClass = popup.params.tpl.anim.old ? 'magictime' : 'animated';
    var hideClass = popup.params.tpl.anim_close
        ? popup.params.tpl.anim_close.hide_class
        : popup.params.tpl.anim.hide_class;
    var cloaseAnimDuration = popup.params.tpl.anim_close_duration;
    if(cloaseAnimDuration && cloaseAnimDuration > 0) {
        $shell.animationDuration( cloaseAnimDuration, true );
    } else {
        cloaseAnimDuration = popup.params.tpl.anim_duration;
    }
    $shell.removeClass(popup.params.tpl.anim.show_class).addClass(hideClass);
    setTimeout(function(){
        $shell.removeClass( preAnimClass );
        __ppsHideShell({shell: $shell, popup: popup});
        ppsHideBgOverlay( popup );
    }, cloaseAnimDuration );
}
function _ppsIframesForReload(params) {
    var popup = params.popup
        ,	shell = params.shell ? params.shell : ppsGetPopupShell( popup );
    if(shell.find('iframe')) {
        shell.find('iframe').each(function(){
            var src = jQuery(this).attr('src');
            if(src) {
                if(src.indexOf('www.google.com/maps/embed') !== -1) {
                    this.src = this.src;	// Reoad iframe
                }
            }
        });
    }
}
/**
 * Is this iframe with video for example
 * @param {object} params object with PopUp and PopUp shell (not mandatory): {popup: popup, shell: shell}
 * @returns {bool} true if PopIp contains any video, else - false
 */
function _ppsIsIframeForHide( params ) {
    params = params || {};
    if(params.popup.type == 'video')
        return true;	// First check is really simple, isn't it?:)
    var $shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
        ,	$iFrames = $shell ? $shell.find('iframe') : false
        ,	videoFound = false;
    if($iFrames && $iFrames.length) {
        var videoSources = ['youtube', 'vimeo', 'dtbaker'];
        $iFrames.each(function(){
            var originalSrc = jQuery(this).data('original-src')
                ,	src = jQuery(this).attr('src');
            if(src || originalSrc) {
                for(var i = 0; i < videoSources.length; i++) {
                    if((src && src.indexOf( videoSources[ i ] ) !== -1)
                        || (originalSrc && originalSrc.indexOf( videoSources[ i ] ) !== -1)
                    ) {
                        videoFound = true;
                        return false;
                    }
                }
            }
        });
    }
    return videoFound;
}
function _ppsCheckBindVideo(params) {
    params = params || {};
    if(_ppsIsIframeForHide( params )) {
        var $shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
            ,	$iFrames = $shell ? $shell.find('iframe,video') : false;
        if($iFrames && $iFrames.length) {
            $iFrames.each(function(){
                jQuery(this).data('original-src', jQuery(this).attr('src'));
                jQuery(this).attr('src', '');
            });
        }
    }
}
function _ppsCheckPlayVideo(params) {
    params = params || {};
    if(_ppsIsIframeForHide( params )) {
        var $shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
            ,	$iFrames = $shell ? $shell.find('iframe,video') : false;
        if($iFrames && $iFrames.length) {
            $iFrames.each(function(){
                var originalSrc = jQuery(this).data('original-src')
                    ,	src = jQuery(this).attr('src');
                if(originalSrc && originalSrc != '' && (!src || src == '')) {
                    jQuery(this).attr('src', originalSrc);
                }
            });
        }
    }
}
function _ppsCheckStopVideo(params) {
    params = params || {};
    if(_ppsIsIframeForHide( params )) {
        var $shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
            ,	$iFrames = $shell ? $shell.find('iframe,video') : false;
        if(params.popup.params.tpl.video_extra_full_screen) {
            return;
        }
        if($iFrames && $iFrames.length) {
            $iFrames.each(function(){
                jQuery(this).attr('src', '');
            });
        }
    }
}
function _ppsCheckInnerScripts(params) {
    params = params || {};
    var $shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
    // Check scripts in description, and execute them if they are there
    var $scripts = $shell.find('script');
    if($scripts && $scripts.length) {
        $scripts.each(function(){
            var scriptSrc = jQuery(this).attr('src');
            if(scriptSrc && scriptSrc != '') {
                jQuery.getScript( scriptSrc );
            }
        });
    }
}
function _ppsCheckMap(params) {
    params = params || {};
    var shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
        ,	maps = shell.find('.gmp_map_opts');
    if(maps && maps.length) {
        // For case we need to wait until gmap scripts will be loaded
        if(typeof(gmpGetMapByViewId) === 'undefined') {
            setTimeout(function(){
                _ppsCheckMap(params);
            }, 1000);
            return;
        }
        maps.each(function(){
            var viewId = jQuery(this).data('view-id')
                ,	map = gmpGetMapByViewId(viewId);
            if(map) {	// If map is already there - just refresh it after popup was shown
                map.fullRefresh ? map.fullRefresh() : map.refresh();	// For compatibilty with old methids, where there are no fullRefresh() method
            } else {	// If there are no map - but it should be there - just create it
                var mapData = gmpGetMapInfoByViewId( viewId );
                gmpInitMapOnPage( mapData );
            }
        });
    }
}
function _ppsCheckContactForm(params) {
    params = params || {};
    var $shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
        ,	$forms = $shell.find('.cfsFormShell');
    if($forms && $forms.length) {
        // For case we need to wait until gmap scripts will be loaded
        if(typeof(g_cfsForms) === 'undefined') {
            setTimeout(function(){
                _ppsCheckContactForm(params);
            }, 1000);
            return;
        }
        if(typeof(cfsCheckInitForms) !== 'undefined') {
            cfsCheckInitForms($shell);
        }
        $forms.each(function(){
            var viewHtmlId = jQuery(this).attr('id')
                ,	form = g_cfsForms.getByViewHtmlId( viewHtmlId );
            if(form) {
                form.refresh();
            } else {	// If there are no form - but it should be there - just create it
                g_cfsForms.add( g_cfsForms.getFormDataByViewHtmlId( viewHtmlId ) );
            }
        });
    }
}
/**
 * Check social icons from Social Share Buttons by Supsystic plugin
 * @param {object} params contain popup and popup shell html objects
 */
function _ppsSocialIcons(params) {
    params = params || {};
    var shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
        ,	icons = shell.find('.supsystic-social-sharing:not(.supsystic-social-sharing-loaded)');
    if(icons && icons.length && typeof(window.initSupsysticSocialSharing) !== 'undefined') {
        icons.each(function(){
            window.initSupsysticSocialSharing(this);
        });
    }
}
function _ppsCheckPublication(params) {
    params = params || {};
    var shell = params.shell ? params.shell : ppsGetPopupShell( params.popup )
        ,	publications = shell.find('.dpsBookStageShell');

    if(publications && publications.length) {
        publications.each(function(){
            if(typeof(dpsBookMng) == 'undefined') {
                dpsBookMng = new dpsBookManager();
            }
            var id = jQuery(this).find('.dpsBook').data('bookid')
                ,	publication = dpsBookMng.getById(id);
            if(publication) {	// If publication is already there - just reinit it after popup was shown
                publication.getHtml().turn('destroy');
                publication.getStage().parent().html( publication._baseHtml );
                publication._init(true);
            } else {
                dpsInitBookOnPage(dpsBookInfoByTermId(id));
                dpsBindBookActions(id);
            }
        });
    }
}
function _ppsPositionPopup( params ) {
    params = params || {};
    params.popup = params.popup && typeof(params.popup) !== 'object' ? ppsGetPopupById( params.popup ) : params.popup;
    if (params.popup.params.main.groups) return;
    var $shell = params.shell ? params.shell : ppsGetPopupShell( params.popup );
    if($shell) {
        var wndWidthOffset = 10, // at least 10px;
            wndHeightOffset = 10, // at least 10px;
            leftOffset,
            btnClose;

        btnClose = $shell.find('.ppsPopupClose');
        if(btnClose.css('position') == 'absolute') {
            wndWidthOffset = +wndWidthOffset - parseFloat(btnClose.css('right'));
        }
        leftOffset = wndWidthOffset / 2;
        var	wndWidth = params.wndWidth ? params.wndWidth : jQuery(window).width()
            ,	wndHeight = params.wndHeight ? params.wndHeight : jQuery(window).height()
            ,	shellWidth = $shell.outerWidth(true)
            ,	shellHeight = $shell.outerHeight()
            ,	resized = false
            ,	resizedW = false
            ,	resizedH = false
            ,	compareWidth = wndWidth - wndWidthOffset
            ,	compareHeight = wndHeight - wndHeightOffset
            ,	resizeDivision = 1
            ,	responsiveInside = $shell.hasClass('ppsResponsiveInside')
            ,	isResponsedByWidthOnly = false;

        if(shellHeight >= compareHeight && !responsiveInside && !(params.popup && params.popup._notResizeHeight)) {
            var initialHeight = params.recalc ? false : parseInt($shell.data('init-height'));
            if(!initialHeight) {
                initialHeight = shellHeight;
                $shell.data('init-height', initialHeight);
            }
            resizeDivision = compareHeight / initialHeight;
            resized = resizedH = true;
        }
        if(shellWidth >= compareWidth && !responsiveInside) {
            var initialWidth = params.recalc ? false : parseInt($shell.data('init-width'));
            if(!initialWidth) {
                initialWidth = shellWidth;
                $shell.data('init-width', initialWidth);
            }
            var widthDivision = compareWidth / initialWidth;
            if(widthDivision < resizeDivision) {
                resizeDivision = widthDivision;
            }
            resized = resizedW = true;
        }
        if(resized) {
            if(params.popup.params.tpl.responsive_mode == 'width_only') {
                if(resizedW) {
                    var totalPadding = shellWidth - $shell.width();	// outer width - just width == paddings
                    isResponsedByWidthOnly = shellWidth >= wndWidth;
                    $shell.css({
                        'width': 'calc(100% - '+ (totalPadding + 20)+ 'px)'
                    });
                } else {
                    var initialWidth = parseInt($shell.data('init-width'));
                    if(initialWidth && initialWidth < compareWidth) {
                        $shell.css({
                            'width': initialWidth
                        });
                    }
                }
                if(resizedH) {
                    $shell.css({
                        'position': 'absolute'
                    });
                } else {
                    $shell.css({
                        'position': 'fixed'
                    });
                }
            } else {
                var zoomOrigin = {left: 'center', top: 'center'};
                $shell.ppsZoom( resizeDivision, zoomOrigin.left+ ' '+ zoomOrigin.top );
            }
            $shell.data('resized', 1);
            shellWidth = $shell.outerWidth();
            shellHeight = $shell.outerHeight();
        } else if($shell.data('resized')) {
            if(params.popup.params.tpl.responsive_mode == 'width_only') {
                var initialWidth = parseInt($shell.data('init-width'));
                if(initialWidth && initialWidth < compareWidth) {
                    $shell.css({
                        'width': initialWidth
                    });
                }
                $shell.css({
                    'position': 'fixed'
                });
            }
        }
        if(params.popup.params.tpl.responsive_mode != 'width_only'){
            $shell.attr('data-responsive', 'true');
        } else {
            $shell.removeAttr('data-responsive');
        }
        params.popup.resized_for_wnd = resized;
        jQuery(document).trigger('ppsResize', {popup: params.popup, shell: $shell, wndWidth: wndWidth, wndHeight: wndHeight, isResponsedByWidthOnly: isResponsedByWidthOnly});
        if(!$shell.positioned_outside) {	// Make available - re-position popup from outside modules
            var left = (wndWidth - shellWidth) / 2
                ,	top = (wndHeight - shellHeight) / 2;
            left = left < 0 ? 0 : left;
            top = top < 0 ? 0 : top;
            if(resizedW) {
                left -= ((initialWidth - initialWidth * widthDivision) / 2) - leftOffset;
            }
            if(resizedH && params.popup.params.tpl.responsive_mode != 'width_only') {
                top -= (initialHeight - initialHeight * resizeDivision) / 2;
            }
			$shell[0].removeAttribute('left');
			$shell[0].removeAttribute('top');
			$shell[0].style.setProperty('left', left+'px', 'important');
			$shell[0].style.setProperty('top', top+'px', 'important');
			$shell[0].style.setProperty('bottom', 'inherit', 'important');
			$shell[0].style.setProperty('right', 'inherit', 'important');
            // $shell.css({
            //     'left': left,
			// 	'top': top
            // });
            if(isResponsedByWidthOnly) {
                $shell.css({
                    'transform': 'translate(-50%, 0)',
                    'left': '0'
                });
                setTimeout(function() {
                    $shell.css({
                        'left': '50%'
                    });
                }, params.popup.params.tpl.anim_duration - 20);
            }
        }
    } else {
        console.log('CAN NOT FIND POPUP SHELL TO RESIZE!');
    }
}
function ppsClosePopup(popup) {
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    var $shell = ppsGetPopupShell( popup );
    if(popup.params.tpl.anim) {
        _ppsHandlePopupAnimationHide( popup, $shell );
    } else {
        __ppsHideShell({shell: $shell, popup: popup});
        ppsHideBgOverlay( popup );
    }
    _ppsCheckStopVideo({shell: $shell, popup: popup});
    // Check disable wnd scrolling
    if(popup.params.tpl.dsbl_wnd_scroll) {
        enableScrollPps('html');
        enableScrollPps('body');
    }
    // Check redirect after close option
    if(parseInt(popup.params.tpl.close_redirect_to_btn_url) && popup.params.close_redirect_to_btn_url_href) {
        toeRedirect(popup.params.close_redirect_to_btn_url_href, parseInt(popup.params.tpl.reidrect_on_close_new_wnd));
    } else if(popup.params.tpl.reidrect_on_close && popup.params.tpl.reidrect_on_close != '') {
        toeRedirect(popup.params.tpl.reidrect_on_close, parseInt(popup.params.tpl.reidrect_on_close_new_wnd));
    }
    _ppsPopupAddStat( popup, 'close' );	// Save close popup statistics
    popup.is_visible = false;
}
function ppsGetPopupShell(popup) {
    if(isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    return jQuery('#ppsPopupShell_'+ popup.view_id);;
}
function ppsGetPopupById( id ) {
    for(var i = 0; i < ppsPopups.length; i++) {
        if(ppsPopups[ i ].id == id)
            return ppsPopups[ i ];
    }
    return false;
}
function ppsGetPopupByViewId( viewId ) {
    for(var i = 0; i < ppsPopups.length; i++) {
        if(ppsPopups[ i ].view_id == viewId)
            return ppsPopups[ i ];
    }
    return false;
}
function ppsInitBgOverlay() {
    jQuery('body').append('<div id="ppsPopupBgOverlay" />');
    jQuery('#ppsPopupBgOverlay').click(function(){
        if(ppsPopups && ppsPopups.length) {
            for(var i = 0; i < ppsPopups.length; i++) {
                if(ppsPopups[ i ]
                    && ppsPopups[ i ].params
                    && ppsPopups[ i ].params.main
                    && ppsPopups[ i ].params.main.close_on
                    && ppsPopups[ i ].params.main.close_on == 'overlay_click'
                ) {
                    ppsClosePopup( ppsPopups[ i ] );
                }
            }
        }
    });
}
function ppsShowBgOverlay(popup) {
    if (popup.params.main.groups) return;
    if(popup && isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    if(popup.ignore_background)	/* For some types - we will not be require background - so we can manipulate it using this key */
        return;
    var $overlay = jQuery('#ppsPopupBgOverlay');
    /* Clear all prev. sets - because there can be several numbers of PopUps, but overlay - is one for all */
    $overlay.css({
        'background-position': ''
        ,	'background-repeat': ''
        ,	'background-attachment': ''
        ,	'-webkit-background-size': ''
        ,	'-moz-background-size': ''
        ,	'-o-background-size': ''
        ,	'background-size': ''
        ,	'background-color': ''
        ,	'background-url': ''
    }).removeClass('ppsSnow');
    if(popup && popup.params) {
        /* Opacity check */
        if(typeof(popup.params.tpl.bg_overlay_opacity) !== 'undefined') {
            if(!popup.params.tpl.bg_overlay_opacity || popup.params.tpl.bg_overlay_opacity == '')
                popup.params.tpl.bg_overlay_opacity = 0;
            var opacity = parseFloat( popup.params.tpl.bg_overlay_opacity );
            if(!isNaN(opacity)) {
                $overlay.css({
                    'opacity': opacity
                });
            }
        }
        if(typeof(popup.params.tpl.bg_overlay_type) !== 'undefined') {
            switch(popup.params.tpl.bg_overlay_type) {
                case 'color':
                    $overlay.css({
                        'background-color': popup.params.tpl.bg_overlay_color
                    });
                    break;
                case 'img':
                    if(popup.params.tpl.bg_overlay_img) {
                        switch(popup.params.tpl.bg_overlay_img_pos) {
                            case 'stretch':
                                $overlay.css({
                                    'background-position': 'center center'
                                    ,	'background-repeat': 'no-repeat'
                                    ,	'background-attachment': 'fixed'
                                    ,	'-webkit-background-size': 'cover'
                                    ,	'-moz-background-size': 'cover'
                                    ,	'-o-background-size': 'cover'
                                    ,	'background-size': 'cover'
                                });
                                break;
                            case 'center':
                                $overlay.css({
                                    'background-position': 'center center'
                                    ,	'background-repeat': 'no-repeat'
                                    ,	'background-attachment': 'scroll'
                                    ,	'-webkit-background-size': 'auto'
                                    ,	'-moz-background-size': 'auto'
                                    ,	'-o-background-size': 'auto'
                                    ,	'background-size': 'auto'
                                });
                                break;
                            case 'tile':
                                $overlay.css({
                                    'background-position': 'left top'
                                    ,	'background-repeat': 'repeat'
                                    ,	'background-attachment': 'scroll'
                                    ,	'-webkit-background-size': 'auto'
                                    ,	'-moz-background-size': 'auto'
                                    ,	'-o-background-size': 'auto'
                                    ,	'background-size': 'auto'
                                });
                                break;
                        }
                        $overlay.css({
                            'background-image': 'url("'+ popup.params.tpl.bg_overlay_img+ '")'
                        });
                    }
                    break;
                case 'snow':	// Just snow effect for overlay
                    $overlay.addClass('ppsSnow');
                    break;
            }
        }
    }
    $overlay.show();
}
function ppsHideBgOverlay(popup) {
    if(popup && isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    if(popup.ignore_background)	// For some types - we will not be require background - so we can manipulate it using this key
        return;
    jQuery('#ppsPopupBgOverlay').hide();
}
function ppsBindPopupActions(popup) {
    var $shell = ppsGetPopupShell( popup );
    // TODO: make usage of ppsPopupSubscribeSuccess() function only after success subscribe process, not after subscribe action
    if($shell.find('.ppsSubscribeForm_aweber').length) {
        $shell.find('.ppsSubscribeForm_aweber').submit(function(){
            if(jQuery(this).find('input[name=email]').val()) {
                ppsPopupSubscribeSuccess( popup );
            }
        });
    }
    // Check build-in PopUp subscribe links
    if($shell.find('.ppsSmLink').length) {
        $shell.find('.ppsSmLink').click(function(){
            _ppsPopupSetActionDone(popup, 'share', jQuery(this).data('type'));
        });
    }
    // Check Social Share by Supsystic plugin links in PopUp
    if($shell.find('.supsystic-social-sharing').length) {
        $shell.find('.supsystic-social-sharing a').click(function(){
            var socHost = this.hostname
                ,	socType = '';	// Social network type key
            if(socHost && socHost != '') {
                switch(socHost) {
                    case 'www.facebook.com':
                        socType = 'facebook';
                        break;
                    case 'plus.google.com':
                        socType = 'googleplus';
                        break;
                    case 'twitter.com':
                        socType = 'twitter';
                        break;
                    default:
                        socType = socHost;
                        break;
                }
                _ppsPopupSetActionDone(popup, 'share', socType);
            }
        });
    }
    if($shell.find('.fb_iframe_widget').length) {
        _ppsBindFbLikeBtnAction(popup);
    }
    /*For age verification templates*/
    if(popup.type == 'age_verify') {
        var $verifyBtns = $shell.find('.ppsBtn');
        if($verifyBtns && $verifyBtns.length) {
            $verifyBtns.click(function(){
                var btnClasses = jQuery(this).attr('class').split(' ')
                    ,	btnId = 0;
                if(btnClasses && btnClasses.length) {
                    for(var i = 0; i < btnClasses.length; i++) {
                        if(btnClasses[ i ].indexOf('ppsBtn_') === 0) {
                            btnId = parseInt( btnClasses[ i ].split('_')[1] );
                            break;
                        }
                    }
                }
                _ppsPopupSetActionDone(popup, 'age_verify', btnId);
            });
        }
    }
}
function _ppsBindFbLikeBtnAction(popup) {
    if(typeof(FB) === 'undefined') {
        // recurse until FB core will not be loaded
        setTimeout(function(){
            _ppsBindFbLikeBtnAction(popup);
        }, 500);
        return;
    }
    FB.Event.subscribe('edge.create', function(response) {
        _ppsPopupSetActionDone(popup, 'fb_like');
    });
    FB.Event.subscribe('xfbml.render', function(response) {
        setTimeout(function(){
            // Just don't know what to say.....
            setTimeout(function(){
                _ppsPositionPopup({popup: popup});
            }, 1000);
            if(popup.render_with_fb_load) {	// If it need to be rendered
                ppsCheckShowPopup( popup );
                _ppsPositionPopup({popup: popup});
            } else {	// else - just re-position it
                _ppsPositionPopup({popup: popup});
            }
        }, 1000);
    });
}
function ppsPopupSubscribeSuccess(popup, params) {
    if(popup && isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    _ppsPopupSetActionDone(popup, 'subscribe', false, params);
}
function _ppsPopupBindDelay(popup, delayKey, delayEnbKey) {
    if(popup && isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    var delay = (popup.params.main[ delayEnbKey ]
        && parseInt(popup.params.main[ delayEnbKey ])
        && parseInt( popup.params.main[ delayKey ] ))
        ? (parseInt(popup.params.main[ delayKey ]) * 1000)
        : 0;
    if(delay) {
        // Check if option to make delay independed on page reload is enabled
        if(delayKey == 'show_on_page_load_delay' && parseInt(popup.params.main.enb_page_load_global_delay)) {
            popup.start_time = (new Date).getTime();
            _ppsPopupBindUnloadDelay(delayKey, popup);
            var spentTime = parseInt( getCookiePps('pps_un_'+ delayKey+ '_'+ popup.id) );	// Check prev. counted time, spent on site
            if(spentTime && spentTime > 0) {
                delay -= spentTime;	// Include already spent time on site - in current delay
                if(delay < 0) delay = 0;
            }
        }
        setTimeout(function(){
            ppsCheckShowPopup( popup );
        }, delay);
        return true;
    }
    return false;
}
/**
 * helper function for _ppsPopupBindDelay() function
 * @param {string} delayKey from _ppsPopupBindDelay() func
 * @param {object} popup PopUp object
 * @returns {undefined}
 */
function _ppsPopupBindUnloadDelay( delayKey, popup ) {
    jQuery(window).unload(function(){
        var spentTime = popup.is_rendered ? 0 : (new Date()).getTime() - popup.start_time;
        setCookiePps('pps_un_'+ delayKey+ '_'+ popup.id, spentTime);
    });
}
/**
 * Check show PopUp right after page was loaded
 * @param {object} popup PopUp object
 */
function ppsBindPopupForceShow( popup ) {
    if(popup.params.main.show_on != 'link_follow') {
        var hashParams = toeGetHashParams();
        if(hashParams && hashParams.length && toeInArray('ppsShowPopUp_'+ popup.id, hashParams) !== -1) {
            ppsCheckShowPopup( popup );
        }
    }
}
/**
 * Check errors or messages in GET paramters, if they present - show it in PopUp
 * @param {object} popup PopUp object
 */
function ppsCheckPopupGetNotices( popup ) {
    var res = {
        errors: getDataLcs('ppsErrors')
        ,	messages: getDataLcs('ppsMsgs')
    };
    if(res.errors)
        res.error = true;
    if(res.errors || res.messages) {
        var shell = ppsGetPopupShell( popup );
        toeProcessAjaxResponsePps( res, shell.find('.ppsSubMsg') );
    }
}
/**
 * Check if page was cached by any cache plugin - by checking page comments.
 * Usually they add comments in body tag.
 */
function _ppsCheckIsPageCached() {
    if(g_ppsIsPageCachedChecked)	// It was computed before - ignore one more compilation
        return g_ppsIsPageCached;
    jQuery('*:not(iframe,video,object)').contents().filter(function(){
        return this.nodeType == 8;
    }).each(function(i, e){
        if(e.nodeValue
            && (e.nodeValue.indexOf('Performance optimized by W3 Total Cache') !== -1
                || e.nodeValue.indexOf('Cached page generated by WP-Super-Cache') !== -1)
        ) {
            g_ppsIsPageCached = true;
            return false;
        }
    });
    g_ppsIsPageCachedChecked = true;
    return g_ppsIsPageCached;
}
function _ppsUpdatePopupNonces( popup ) {
    if(!popup._nonces_updated) {	// Update them only one time per session for each PopUp
        var $shell = ppsGetPopupShell( popup )
            ,	$forms = $shell.find('form')
            ,	getFor = []
            ,	tryGetFor = ['ppsSubscribeForm', 'ppsLoginForm', 'ppsRegForm'];
        if($forms && $forms.length) {
            $forms.each(function(){
                for(var i = 0; i < tryGetFor.length; i++) {
                    if(jQuery(this).hasClass( tryGetFor[ i ] )) {
                        getFor.push( tryGetFor[ i ] );
                    }
                }

            });
        }
        if(getFor && getFor.length) {
            jQuery.sendFormPps({
                msgElID: 'noMessages'
                ,	data: {mod: 'popup', action: 'updateNonce', id: popup.id, get_for: getFor}
                ,	onSuccess: function(res) {
                    if(!res.error && res.data.update_for) {
                        var $shell = ppsGetPopupShell( popup );
                        for(var className in res.data.update_for) {
                            $shell.find('.'+ className).find('input[name="_wpnonce"]').val( res.data.update_for[ className ] );
                        }
                    }
                }
            });
        }
        popup._nonces_updated = true;
    }
}
function _ppsBindClickHrefSaving() {
    for(var i = 0; i < ppsPopups.length; i++) {
        if(ppsPopups[ i ].params
            && ppsPopups[ i ].params.tpl
            && (parseInt(ppsPopups[ i ].params.tpl.sub_redirect_to_btn_url) || parseInt(ppsPopups[ i ].params.tpl.close_redirect_to_btn_url))
        ) {
            var $btn = jQuery('[onclick*="ppsShowPopup('+ ppsPopups[ i ].id+ ')"]');
            ppsPopups[ i ] = _ppsSaveClickHref(ppsPopups[ i ], $btn);
        }
    }
}
function _ppsSaveClickHref(popup, $element) {
    if(popup.params
        && popup.params.tpl
        && $element
        && $element.length
    ) {
        var href = $element.attr('href');
        if(parseInt(popup.params.tpl.sub_redirect_to_btn_url)) {
            popup.params.sub_redirect_to_btn_url_href = href;
        }
        if(parseInt(popup.params.tpl.close_redirect_to_btn_url)) {
            popup.params.close_redirect_to_btn_url_href = href;
        }
    }
    return popup;
}
function ppsAddShowClb( id, clb ) {
    if(!g_ppsShowCallbacks[ id ]) {
        g_ppsShowCallbacks[ id ] = [];
    }
    g_ppsShowCallbacks[ id ].push( clb );
}
function runShowClb( popup, shell ) {
    if(popup && isNumericPps( popup ))
        popup = ppsGetPopupById( popup );
    if(g_ppsShowCallbacks[ popup.id ] && g_ppsShowCallbacks[ popup.id ].length) {
        for(var i = 0; i < g_ppsShowCallbacks[ popup.id ].length; i++) {
            if(typeof(g_ppsShowCallbacks[ popup.id ][ i ]) === 'function') {
                g_ppsShowCallbacks[ popup.id ][ i ]( popup, shell );
            }
        }
    }
}
jQuery(document).bind('ppsAfterPopupsActionShow', function(e, popup){
	var scaleSize = 1,
	 	responsiveMode = popup.params.tpl.responsive_mode;
	if (responsiveMode === 'def') {

      var defaultPopupWidth = 0;
      var defaultPopupHeight = 0;

      window.addEventListener("resize", function() {
          setTimeout(function(){
            var popupDiv = jQuery('body').find('div[id*="ppsPopupShell_' + popup['id'] + '"]');
            window.popupDiv = popupDiv;
            popupWidth = defaultPopupWidth;
            defaultPopupHeight = defaultPopupHeight;
            window.popupDiv[0].style.setProperty('transform', '', '');
            if (popup.params.tpl.anim_key == 'none') {
              changeScaleByWidth();
            }
         }, 1000);
      }, false);

      window.addEventListener("orientationchange", function() {
          setTimeout(function(){
            var popupDiv = jQuery('body').find('div[id*="ppsPopupShell_' + popup['id'] + '"]');
            window.popupDiv = popupDiv;
            popupWidth = defaultPopupWidth;
            defaultPopupHeight = defaultPopupHeight;
            window.popupDiv[0].style.setProperty('transform', '', '');
            if (popup.params.tpl.anim_key == 'none') {
              changeScaleByWidth();
            }
          }, 1000);
      }, false);

      if (popup.params.tpl.anim_key == 'none') {
        changeScaleByWidth();
      }

			jQuery(window).resize(function() {
				setTimeout(changeScaleByWidth(), 2000);
			});

			var docHeight = jQuery(window).height();

      var doOnceGetInitHeightWidth = true;

			function changeScaleByWidth() {
				scaleSize = 1;
				var popupDiv = jQuery('body').find('div[id*="ppsPopupShell_' + popup['id'] + '"]'),
					docWidth = jQuery(document).width(),
					docHeight = jQuery(window).height(),
					popupWidth = popupDiv[0].getBoundingClientRect().width,
					popupHeight = popupDiv[0].getBoundingClientRect().height;

          if (doOnceGetInitHeightWidth) {
            defaultPopupHeight = popupHeight;
            defaultPopupWidth = popupWidth;
            doOnceGetInitHeightWidth = false;
          }

				while ( ( (popupWidth > docWidth) || (popupHeight+30 > docHeight) ) && scaleSize > 0) {
				  scaleSize = scaleSize - 0.01;
				  popupDiv[0].removeAttribute('transform');
				  popupDiv[0].style.setProperty('transform', 'scale(' + scaleSize + ')', 'important');
				  docWidth = jQuery(document).width();
				  docHeight = jQuery(window).height();
				  popupWidth = popupDiv[0].getBoundingClientRect().width;
				  popupHeight = popupDiv[0].getBoundingClientRect().height;
				}
				setTimeout(function(){
					var resultHeight = jQuery('body').find('div[id*="ppsPopupShell_' + popup['id'] + '"]')[0].getBoundingClientRect().height;
					if (resultHeight > docHeight) {
            if (popup.params.tpl.anim_key == 'none') {
              changeScaleByWidth();
            }
					}
				}, 1000);
			}
	}
});
