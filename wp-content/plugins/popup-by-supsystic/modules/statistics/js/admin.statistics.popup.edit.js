var g_ppsCurrentPlot = null
,	g_ppsCurrentChartType = ''
,	g_ppsPieAllActionDone = false
,	g_ppsPieAllShareDone = false
,	g_ppsCurrentStats = []
,	g_ppsCurrentStatGroup = 'day'
,	g_tblDataToDate = {};
jQuery(document).ready(function(){
	jQuery('.ppsPopupStatChartTypeBtn').click(function(){
		ppsUpdatePopupStatsGraph( jQuery(this).data('type') );
		return false;
	});
	jQuery('#ppsPopupStatClear').click(function(){
		if(confirm(toeLangPps('Are you sure want to clear all PopUp Statistics?'))) {
			jQuery.sendFormPps({
				btn: this
			,	data: {mod: 'statistics', action: 'clearForPopUp', id: jQuery(this).data('id')}
			,	onSuccess: function(res) {
					if(!res.error) {
						toeReload();
					}
				}
			});
		}
		return false;
	});
	jQuery('#ppsPopupEditForm').find('input[name=stat_from_txt],input[name=stat_to_txt]').datepicker({
		onSelect: function() {
			jQuery('#ppsPopupStatClearDateBtn').show();
			ppsPopupStatUpdateGraphRange();
		}
	});
	jQuery('#ppsPopupStatClearDateBtn').click(function(){
		jQuery('#ppsPopupEditForm').find('input[name=stat_from_txt],input[name=stat_to_txt]').val('');
		ppsPopupStatUpdateGraphRange();
		jQuery(this).hide();
		return false;
	});
	ppsPopupStatSetGoup( ppsPopupStatGetGoup() );
	jQuery('[data-stat-group]').click(function(){
		ppsPopupStatChangeGroup( jQuery(this).data('stat-group'), this );
		return false;
	});
	jQuery('.ppsPopupStatGraphZoomReset').click(function(){
		if(g_ppsCurrentPlot) {
			g_ppsCurrentPlot.resetZoom();
			jQuery(this).hide();
		}
		return false;
	});
	jQuery('#ppsPopupStatExportCsv').click(function(){
		var baseUrl = '';
		if(jQuery(this).data('base-url')) {
			baseUrl = jQuery(this).data('base-url');
		} else {
			baseUrl = jQuery(this).attr('href');
			jQuery(this).data('base-url', baseUrl);
		}
		
		jQuery(this).attr('href', baseUrl+ '&group='+ ppsPopupStatGetGoup());
	});
});
function ppsPopupStatChangeGroup(newGroup, btn) {
	var btnName = jQuery(btn).html();
	jQuery(btn).html('<i class="fa fa-spinner fa-spin"></i>');
	jQuery.sendFormPps({
		btn: btn
	,	data: {mod: 'statistics', action: 'getUpdatedStats', group: newGroup, id: ppsPopup.id}
	,	onSuccess: function(res) {
			jQuery(btn).html( btnName );	
			if(!res.error) {
				ppsPopupStatSetGoup( newGroup );
				ppsPopupAllStats = res.data.stats;
				ppsUpdatePopupStatsGraph(false, {force: true});
			}
		}
	});
}
function ppsPopupStatGetGoup() {
	var savedValue = getCookiePps('pps_stat_group');
	return savedValue && savedValue != '' ? savedValue : 'day';
}
function ppsPopupStatSetGoup(group) {
	jQuery('[data-stat-group]').removeClass('focus');
	jQuery('[data-stat-group="'+ group+ '"]').addClass('focus');
	setCookiePps('pps_stat_group', group);
}
function ppsPopupStatUpdateGraphRange() {
	if(g_ppsCurrentPlot) {
		ppsUpdatePopupStatsGraph(false, {force: true});
	}
}
function ppsPopupStatGetChartType() {
	var savedValue = getCookiePps('pps_chart_coockie');
	return savedValue && savedValue != '' ? savedValue : 'line';
}
function ppsPopupStatSetChartType(type) {
	jQuery('.ppsPopupStatChartTypeBtn').removeClass('focus');
	jQuery('.ppsPopupStatChartTypeBtn[data-type="'+ type+ '"]').addClass('focus');
	setCookiePps('pps_chart_coockie', type);
}
function ppsDrawPopupCharts() {
	ppsUpdatePopupStatsGraph();
	ppsUpdateAllActionChart();
	ppsUpdateAllShareChart();
}
function ppsUpdateAllActionChart() {
	if(!g_ppsPieAllActionDone) {
		if(typeof(ppsPopupAllStats) != 'undefined') {
			var	plotDataByCode = {}
			,	haveData = false;
			for(var i = 0; i < ppsPopupAllStats.length; i++) {
				if(ppsPopupAllStats[i]['points'] 
					&& ppsPopupAllStats[i]['points'].length 
					&& !toeInArrayPps(ppsPopupAllStats[i]['code'], ['show'])	// make sure - this was exactly action, not like just display
				) {
					var labelCode = ppsPopupAllStats[i].label.replace(/\W+/g, "_");
						plotDataByCode[ labelCode ] = {label: ppsPopupAllStats[i].label, total: 0};
					for(var j = 0; j < ppsPopupAllStats[i]['points'].length; j++) {
						plotDataByCode[ labelCode ].total += parseInt(ppsPopupAllStats[ i ]['points'][ j ]['total_requests']);
					}
					haveData = true;
				}
			}
			if(haveData) {
				var plotData = [];
				for(var code in plotDataByCode) {
					plotData.push([ plotDataByCode[code].label, plotDataByCode[code].total ]);
				}
				jQuery.jqplot ('ppsPopupStatAllActionsPie', [ plotData ], {
					seriesDefaults: {
						renderer: jQuery.jqplot.PieRenderer
					,	rendererOptions: {
							showDataLabels: true
						}
					}
				,	legend: { show:	true, location: 'e' }
				});
			} else {
				jQuery('#ppsPopupStatAllActionsNoData').show();
			}
		}
		g_ppsPieAllActionDone = true;
	}
}
function ppsUpdateAllShareChart() {
	if(!g_ppsPieAllShareDone) {
		if(typeof(ppsPopupAllShareStats) != 'undefined') {
			var plotData = [];
			for(var i = 0; i < ppsPopupAllShareStats.length; i++) {
				if(ppsPopupAllShareStats[i].sm_type) {
					plotData.push([ ppsPopupAllShareStats[i].sm_type.label, parseInt(ppsPopupAllShareStats[i].total_requests) ]);
				}
			}
			if(plotData.length) {
				jQuery.jqplot ('ppsPopupStatAllSharePie', [ plotData ], {
					seriesDefaults: {
						renderer: jQuery.jqplot.PieRenderer
					,	rendererOptions: {
							showDataLabels: true
						}
					}
					,	legend: { show:	true, location: 'e' }
				});
			} else {
				jQuery('#ppsPopupStatAllShareNoData').show();
			}
		} else
			jQuery('#ppsPopupStatAllShareNoData').show();
		g_ppsPieAllShareDone = true;
	}
}
function ppsUpdatePopupStatsGraph(chartType, params) {
	if(typeof(ppsPopupAllStats) != 'undefined') {
		params = params || {};
		chartType = chartType ? chartType : ppsPopupStatGetChartType();
		if(g_ppsCurrentChartType == chartType && !params.force) {
			// Just switching tabs - no need to redraw if it is already drawn
			return;
		}
		ppsPopupStatSetChartType( chartType );
		g_ppsCurrentChartType = chartType;
		var plotData = []
		,	seriesKeys = {}
		,	series = []
		,	plotParams = {}
		,	dateFrom = false
		,	dateTo = false
		,	firstInit = false
		,	group = ppsPopupStatGetGoup();	// Hour, Day, Week, Month
		
		if(g_ppsCurrentPlot) {
			dateFrom = jQuery('#ppsPopupEditForm').find('input[name=stat_from_txt]').val()
		,	dateTo = jQuery('#ppsPopupEditForm').find('input[name=stat_to_txt]').val();
			
			dateFrom = dateFrom && dateFrom != '' ? ppsStrToMs(dateFrom) : false;
			dateTo = dateTo && dateTo != '' ? ppsStrToMs(dateTo) : false;
		}
		var hourMs = 60 * 60 * 1000
		,	dayMs = 24 * hourMs
		,	weekMs = 7 * dayMs
		,	monthMs = 30 * dayMs;
		g_ppsCurrentStats = [];
		var hasPoints = (dateFrom || dateTo) ? false : true;	// If date not set by user - points will be available in any case
		for(var i = 0; i < ppsPopupAllStats.length; i++) {
			var currentData = jQuery.extend( true, {}, ppsPopupAllStats[ i ] );
			if(ppsPopupAllStats[i]['points'] && ppsPopupAllStats[i]['points'].length && (dateFrom || dateTo)) {
				currentData['points'] = [];
				for(var j = 0; j < ppsPopupAllStats[i]['points'].length; j++) {
					var currentDate = ppsStrToMs( ppsPopupAllStats[i]['points'][j]['date'] );
					if((dateFrom 
						&& (currentDate < dateFrom 
							&& !(group == 'week' && currentDate + weekMs > dateFrom)
							&& !(group == 'month' && currentDate + monthMs > dateFrom)
							))
						|| (dateTo 
						&& (currentDate > dateTo))
					) {
						continue;
					}
					currentData['points'].push( ppsPopupAllStats[i]['points'][j] );
					hasPoints = true;
				}
			}
			g_ppsCurrentStats[ i ] = currentData;
		}
		// We re-calculated data - so rebuild txt table
		ppsPopupStatRebuildTbl();
		if(g_ppsCurrentPlot) {
			g_ppsCurrentPlot.destroy();
		} else {
			firstInit = true;
		}
		if(!hasPoints)
			return;
		switch(chartType) {
			case 'bar':
				var ticksKeys = {}
				,	ticks = []
				,	tickId = 0
				,	sortByDateClb = function(a, b) {
					var aTime = ( new Date( str_replace((typeof(a) === 'string' ? a : a.date), '-', '/') ) ).getTime()	// should be no "-" as ff make it Date.parse() in incorrect way
					,	bTime = ( new Date( str_replace((typeof(b) === 'string' ? b : b.date), '-', '/') ) ).getTime();
					if(aTime > bTime)
						return 1;
					if(aTime < bTime)
						return -1;
					return 0;
				}
				,	plotDataToDate = [];
				for(var i = 0; i < g_ppsCurrentStats.length; i++) {
					if(g_ppsCurrentStats[i]['points'] && g_ppsCurrentStats[i]['points'].length) {
						plotDataToDate.push({});
						for(var j = g_ppsCurrentStats[i]['points'].length - 1; j >= 0; j--) {
							ticksKeys[ g_ppsCurrentStats[ i ]['points'][ j ]['date'] ] = 1;
							plotDataToDate[ tickId ][ g_ppsCurrentStats[ i ]['points'][ j ]['date'] ] = parseInt(g_ppsCurrentStats[ i ]['points'][ j ]['total_requests']);
						}
						seriesKeys[ tickId ] = g_ppsCurrentStats[i].label;
						tickId++;
					}
				}
				for(var key in ticksKeys) {
					ticks.push( key );
				}
				ticks.sort( sortByDateClb );
				tickId = 0;
				for(var i = 0; i < plotDataToDate.length; i++) {
					plotData.push([]);
					for(var j in ticks) {
						var dateStr = ticks[ j ];
						plotData[ tickId ].push( typeof(plotDataToDate[i][dateStr]) === 'undefined' ? 0 : plotDataToDate[i][dateStr] );
					}
					tickId++;
				}
				for(var i in seriesKeys) {
					series.push({label: seriesKeys[ i ]});
				}
				var tickFormat = ppsPopupStatGetDateFormat();
				for(var i in ticks) {
					var tickDate = new Date(ppsStrToMs(ticks[i]));
					ticks[ i ] = tickDate.format( tickFormat );	// Format ticks date
				}
				plotParams = {
					seriesDefaults:{
						renderer: jQuery.jqplot.BarRenderer
					,	rendererOptions: {fillToZero: true}
					,	pointLabels: { 
							show: true 
						}
					}
				,	series: series
				,	legend: { show:	true, location: 'ne', placement : 'outsideGrid' }
				,	axes: {
						xaxis: {
							renderer: jQuery.jqplot.CategoryAxisRenderer
						,	ticks: ticks
						},
						yaxis: {
							pad: 1.05
						,	tickOptions: {
								formatString: '%d'
							}
						}
					}
				,	highlighter: {
						show: true
					,	sizeAdjust: 3
					,	tooltipLocation: 'n'
					,	tooltipContentEditor: function(str, seriesIndex, pointIndex, jqPlot) {
							if(seriesKeys[ seriesIndex ]) {
								if(strpos(str, ',')) {
									str = str.split(',');
									str = str[1] ? str[1] : str[0];
									str = jQuery.trim(str);
								}
								return seriesKeys[ seriesIndex ]+ ' ['+ str+ ']';
							}
							return str;
						}
					}
				,	cursor: {
						show: true
					,	zoom: true
					}
				};
				g_ppsCurrentPlot = jQuery.jqplot('ppsPopupStatGraph', plotData, plotParams);
				break;
			case 'line':
			default:
				var tickId = 0;
				for(var i = 0; i < g_ppsCurrentStats.length; i++) {
					if(g_ppsCurrentStats[i]['points'] && g_ppsCurrentStats[i]['points'].length) {
						plotData.push([]);
						for(var j = 0; j < g_ppsCurrentStats[i]['points'].length; j++) {
							plotData[ tickId ].push([g_ppsCurrentStats[ i ]['points'][ j ]['date'], parseInt(g_ppsCurrentStats[ i ]['points'][ j ]['total_requests'])]);
						}
						seriesKeys[ tickId ] = g_ppsCurrentStats[i].label;
						tickId++;
					}
				}
				for(var i in seriesKeys) {
					series.push({label: seriesKeys[ i ]});
				}
				var tickFormat = ppsPopupStatGetDateFormat(true);
				plotParams = {
					axes: {
						xaxis: {
							label: toeLangPps('Date')
						,	labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer
						,	renderer:	jQuery.jqplot.DateAxisRenderer
						,	tickOptions:{formatString: tickFormat}
						}
					,	yaxis: {
							label: toeLangPps('Requests')
						,	labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer
						}
					}
				,	series: series
				,	legend: { show:	true, location: 'ne', placement : 'outsideGrid'}
				,	highlighter: {
						show: true
					,	sizeAdjust: 7.5
					,	tooltipContentEditor: function(str, seriesIndex, pointIndex, jqPlot) {
							if(seriesKeys[ seriesIndex ]) {
								return seriesKeys[ seriesIndex ]+ ' ['+ str+ ']';
							}
							return str;
						}
					}
				,	cursor: {
						show: true
					,	zoom: true
					}
				};
				g_ppsCurrentPlot = jQuery.jqplot('ppsPopupStatGraph', plotData, plotParams);
				break;
		}
		if(firstInit) {
			g_ppsCurrentPlot.target.bind('jqplotZoom', function(ev, gridpos, datapos, plot, cursor){
				jQuery('.ppsPopupStatGraphZoomReset').show();
			});
			// Double click jqplotDblClick didn't worked for me here - don't know - why..........
			// So, let's check it as in old times :)
			var lastClick = 0;
			jQuery('#ppsPopupStatGraph').bind('jqplotClick', function (ev, seriesIndex, pointIndex, data) {
				var currTime = (new Date()).getTime();
				if(currTime - lastClick <= 400) {
					jQuery('.ppsPopupStatGraphZoomReset').hide();
				}
				lastClick = currTime;
			});
		}
	}
}
function ppsPopupStatGetDateFormat(forPlot) {
	var tickFormat = '';
	switch(ppsPopupStatGetGoup()) {
		case 'hour':
			tickFormat = forPlot ? '%H, %#d' : 'H, d';
			break;
		case 'month':
			tickFormat = forPlot ? '%b %Y' : 'M Y';
			break;
		case 'week':
		case 'day':
		default:
			tickFormat = forPlot ? '%b %#d, %Y' : 'M d, Y';
			break;
	}
	return tickFormat;
}
function ppsPopupStatRebuildTbl() {
	var tblId = 'ppsPopupStatTbl';
	if(jQuery('#'+ tblId).jqGrid) {
		jQuery('#'+ tblId).jqGrid('GridUnload');
	}
	jQuery('#'+ tblId).jqGrid({ 
		datatype: 'local'
	,	autowidth: true
	,	shrinkToFit: true
	,	colNames:[toeLangPps('Date'), toeLangPps('Views'), toeLangPps('Unique Views'), toeLangPps('Actions'), toeLangPps('Conversion')]
	,	colModel:[
			{name: 'date', index: 'date', searchoptions: {sopt: ['eq']}, align: 'center', sorttype: 'date'}
		,	{name: 'views', index: 'views', searchoptions: {sopt: ['eq']}, align: 'center'}
		,	{name: 'unique_requests', index: 'unique_requests', searchoptions: {sopt: ['eq']}, align: 'center'}
		,	{name: 'actions', index: 'actions', searchoptions: {sopt: ['eq']}, align: 'center'}
		,	{name: 'conversion', index: 'conversion', searchoptions: {sopt: ['eq']}, align: 'center'}
		]
	,	rowNum: 10
	,	rowList: [10, 20, 30, 1000]
	,	pager: '#'+ tblId+ 'Nav'
	,	sortname: 'date'
	,	viewrecords: true
	,	sortorder: 'desc'
	,	jsonReader: { repeatitems : false, id: '0' }
	,	caption: toeLangPps('Current PopUp')
	,	height: '100%' 
	,	emptyrecords: toeLangPps('You have no statistics to display here.')
	});
	
	if(g_ppsCurrentStats && g_ppsCurrentStats.length) {
		g_tblDataToDate = {};
		var	hasData = false;
		for(var i = 0; i < g_ppsCurrentStats.length; i++) {
			if(g_ppsCurrentStats[i]['points'] && g_ppsCurrentStats[i]['points'].length) {
				for(var j = 0; j < g_ppsCurrentStats[i]['points'].length; j++) {
					var date = g_ppsCurrentStats[ i ]['points'][ j ]['date'];
					var currentData = {
						date: date
					,	views: 0
					,	unique_requests: 0
					,	actions: 0
					,	conversion: 0
					};
					if(toeInArrayPps(g_ppsCurrentStats[i]['code'], ['show'])) {
						currentData['views'] = parseInt( g_ppsCurrentStats[ i ]['points'][ j ]['total_requests'] );
					} else if(!toeInArrayPps(g_ppsCurrentStats[i]['code'], ['close', 'subscribe_error'])) {
						currentData['actions'] = parseInt( g_ppsCurrentStats[ i ]['points'][ j ]['total_requests'] );
					}
					var uniqueRequests = parseInt( g_ppsCurrentStats[ i ]['points'][ j ]['unique_requests'] );
					if(uniqueRequests) {
						currentData['unique_requests'] = uniqueRequests;
					}
					if(g_tblDataToDate[ date ]) {
						currentData['views'] += g_tblDataToDate[ date ]['views'];
						currentData['actions'] += g_tblDataToDate[ date ]['actions'];
						currentData['unique_requests'] += g_tblDataToDate[ date ]['unique_requests'];
					}
					g_tblDataToDate[ date ] = currentData;
					hasData = true;
				}
			}
		}
		if(hasData) {
			var i = 1;
			for(var date in g_tblDataToDate) {	// Calculate conversion
				if(g_tblDataToDate[date]['unique_requests'])
					g_tblDataToDate[date]['conversion'] = g_tblDataToDate[date]['actions'] / g_tblDataToDate[date]['unique_requests'];
				g_tblDataToDate[date]['conversion'] = g_tblDataToDate[date]['conversion'].toFixed(3);
			}
			for(var date in g_tblDataToDate) {
				jQuery('#'+ tblId).jqGrid('addRowData', i, g_tblDataToDate[ date ]);
				i++;
			}
		}
	}
}