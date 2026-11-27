/*
toast.js

Martin Takayama
2012.01.25
*/

(function($){  
	$.toast = function(msg, options) {
		var ops = $.extend($.toast.default_options, options);
		var html = '';
		html += '<div id="toast" style="position:absolute;left:10px;right:10px;margin:0 auto;text-align:center;display:none;">';
		html += '<span style="padding:3px 30px;margin:auto;border-radius:5px;font-weight:bold;">'+msg+'</span>';
		html += '</div>';
		$("body").append(html);
		$("#toast").css("z-index","3000");

		if (typeof(ops.bottom) == "undefined") $("#toast").css("top", ops.top);
		else $("#toast").css("bottom", ops.bottom);

		for (var x in ops) $("#toast span").css(""+x, ops[x]);

		$("#toast").show();
		setTimeout('$("#toast").fadeOut("slow", function(){$(this).remove()});', ops.time);
	}

	$.toast.default_options = {
		top: 0,
		fontSize: "16px",
		color: "#FFFFFF",
		backgroundColor: "#FF0000",
		time: 2000
		/*
		top: 0,
		fontSize: "16px",
		color: "#FFFF00",
		backgroundColor: "#555555",
		border: "3px solid #B7B7A8",
		time: 2000
		*/
	}
})(jQuery);
