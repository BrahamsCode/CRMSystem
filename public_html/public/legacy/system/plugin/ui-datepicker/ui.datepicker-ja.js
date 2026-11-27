/* Japanese initialisation for the jQuery UI date picker plugin. */
/* Written by Kentaro SATO (kentaro@ranvis.com). */
jQuery(function($){
	$.datepicker.regional['ja'] = {
		closeText: '閉じる',
		prevText: '<前', // texto plano: jQuery UI 1.13 ya no interpreta HTML aquí (corrección de XSS)
		nextText: '次>',
		currentText: '今日',
		monthNames: ['年1月','年2月','年3月','年4月','年5月','年6月',
		'年7月','年8月','年9月','年10月','年11月','年12月'],
		monthNamesShort: ['年1月','年2月','年3月','年4月','年5月','年6月',
		'年7月','年8月','年9月','年10月','年11月','年12月'],
		dayNames: ['日曜日','月曜日','火曜日','水曜日','木曜日','金曜日','土曜日'],
		dayNamesShort: ['日','月','火','水','木','金','土'],
		dayNamesMin: ['日','月','火','水','木','金','土'],
		dateFormat: 'yy/mm/dd', firstDay: 0,
		isRTL: false,
		showMonthAfterYear: true};
	$.datepicker.setDefaults($.datepicker.regional['ja']);
});