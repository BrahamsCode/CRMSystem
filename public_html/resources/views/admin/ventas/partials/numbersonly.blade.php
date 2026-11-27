{{-- Legacy: bloque 「数字のみを入力するファンクション」 repetido en karute_menu.php, karute_waribiki.php, keihi.php y karute_new_frm.php. Sin variables. --}}
// #################### 数字のみを入力するファンクション 始 ####################
// 使用方法：<input name="price" type="text" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"/>

var no_digit = /\D/g;

function replace_value(data) {
	data.value = data.value.replace(no_digit, '');
}

function numbersonly(myfield, e, dec)
{
	var key;
	var keychar;

	if (window.event)
	   key = window.event.keyCode;
	else if (e)
	   key = e.which;
	else
	   return true;
	keychar = String.fromCharCode(key);

	// control keys
	if ((key==null) || (key==0) || (key==8) ||
	    (key==9) || (key==13) || (key==27) )
	   return true;

	// numbers
	else if ((("0123456789").indexOf(keychar) > -1))
	   return true;

	// decimal point jump
	else if (dec && (keychar == "."))
	   {
	   myfield.form.elements['dec'].focus();
	   return false;
	   }
	else
	   return false;
}
// #################### 数字のみを入力するファンクション 終 ####################
