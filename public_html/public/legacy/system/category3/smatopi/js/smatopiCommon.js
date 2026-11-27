
// =============================================================================
// 処理などの案内ウインドー
function mAlert(inner) {
	if ( $("#mDialog").length > 0 ) $("#mDialog").remove();

	if ( typeof(inner) == "undefined" || inner == '' ) return;

	try {

		$("body").append('<div id="mDialog"><span id="message" style="display:block;text-align:center;"></span></div>');

		$('#mDialog').dialog({
			open: function(event, ui){
				$("#mDialog").parent().find(".ui-dialog-titlebar, .ui-dialog-buttonpane").hide()
			},
			autoOpen: false,
			width: 300,
			height: "auto",
			closeOnEscape: false,
			modal: true,
			resizable: false,
			draggable: false
		});

		$('#mDialog').children('span').html(inner);
		$('#mDialog').dialog('open');
	} catch(e) {
	}
}

var alertDialog = function(_title, _message, _function_c) {
	var dialogName = "alertDialog";
	var callback_func = _function_c ? _function_c : "";

	if ($('#' + dialogName).length == 0) {
		$('body').append('<div id="' + dialogName + '" style="display: none;"><div class="center">' + _message + '</div></div>');
	}

	$('#' + dialogName).empty().html(_message);
	$('#' + dialogName).dialog({
		resizable: false
		, draggable: false
		, closeOnEscape: false
		, modal: true
		, autoOpen: false
		, width: 300
		, height: 'auto'
		, zIndex: 9999
		, open: function(event, ui) {
			$(".ui-dialog-titlebar-close").hide();
		}
		, close: function(event, ui) {
			// $(".ui-dialog-titlebar-close").show();
		}
		, title: _title
		, buttons: [
			{
				text: '閉じる'
				, id: 'alertDialog_check'
				, class: 'btn-base btn-panel btn-dialog btn-cancel fl-right'
				, click: function() {
					if(callback_func != "") {
						callback_func();
					}

					$('#' + dialogName).dialog('close');
				}
			}
		]
	});

	$('#' + dialogName).dialog('open');
}

//確認ダイアログ
var confirmDialog = function(_title, _message, _function_y, _function_c) {

	var dialogName = "confirmDialog";
	var yes_func    = _function_y ? _function_y : "";
	var cancel_func = _function_c ? _function_c : "";

	if ($('#' + dialogName).length == 0) {
		$('body').append('<div id="' + dialogName + '" style="display: none;"><div class="center">' + _message + '</div></div>');
	}

	$('#' + dialogName).empty().html(_message);
	$('#' + dialogName).dialog({
		resizable     : false,
		draggable     : false,
		closeOnEscape : false,
		modal         : true,
		autoOpen      : false,
		width         : 300,
		height        : 'auto',
		zIndex        : 9999,
		open          : function(event, ui) { $(".ui-dialog-titlebar-close").hide(); },
		title         : _title,
		buttons       : [
			{
				text  : 'はい',
				id    : 'confirmDialog_check',
				class : 'btn-base btn-panel btn-dialog btn-regist fl-right',
				click : function() {

					if(yes_func != "") {
						yes_func();
					}

					$(this).dialog('close');
				}
			},
			{
				text  : 'キャンセル',
				id    : 'editDialogBtn_cancel',
				class : 'btn-base btn-panel btn-dialog btn-cancel fl-right',
				click : function() {

					if(cancel_func != "") {
						cancel_func();
					}

					$(this).dialog('close');
				}
			},
		]
	});

	$('#' + dialogName).dialog('open');
}
// =============================================================================
