function myAlert(message, title, _callback) {
	//if ( typeof(message) == 'undefined' ) message = '';
	if ( typeof(title) == 'undefined' ) title = '';
	BootstrapDialog.show({
		title: title,
		message: message,
		closable: false,
		animate: false,
		buttons: [{
			label: '閉じる',
			action: function(dialogItself){
				dialogItself.close();
				if ( typeof(_callback) == "function" ) {
					try {
						_callback();
					} catch(e){}
				}
			}
		}]
	});
}

function myConfirm(message, title, _okCallback, _cancelCallback) {
	//if ( typeof(message) == 'undefined' ) message = '';
	if ( typeof(title) == 'undefined' ) title = '';
	BootstrapDialog.show({
		title: title,
		message: message,
		closable: false,
		animate: false,
		buttons: [{
			label: 'ＯＫ',
			action: function(dialogItself){
				dialogItself.close();
				if ( typeof(_okCallback) == "function" ) {
					try {
						_okCallback();
					} catch(e){}
				}
			}
		}, {
			label: 'キャンセル',
			action: function(dialogItself){
				dialogItself.close();
				if ( typeof(_cancelCallback) == "function" ) {
					try {
						_cancelCallback();
					} catch(e){}
				}
			}
		}]
	});
}
