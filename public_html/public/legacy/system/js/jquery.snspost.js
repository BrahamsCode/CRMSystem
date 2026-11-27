/*! jquery.snspost | (c) 2015 tetracommand.y |  */
(function( $ ){

	$.fn.snsPost = function() {

		this.on("change", "input[type='checkbox']", function(){ snsPostCheck( this ); });

		function snsPostCheck(obj){
			var v = $(obj).prop('checked');
			if(v){
				$(obj).parent().css({
					"border":"3px solid #55acee",
					"border-radius":"2px",
					"color":"#3B579D"
				});
			}else{
				$(obj).parent().css({
					"border":"3px solid #999999",
					"border-radius":"2px",
					"color":"#777777"
				});
			}
		}

		this.find("input[type='checkbox']").each(function(i, elm){
			snsPostCheck( elm );
			$(elm).parent().css({
				"margin-bottom":"10px",
				"padding":"2px 10px",
				"display":"inline-block",
				"cursor":"pointer"
			});
			// $(elm).hide();
			$(elm).css({
				"width":"30px",
				"height":"30px",
				"vertical-align":"middle",
				"-moz-transform": "scale( 2 , 2 )"
			});
		});

	};
})( jQuery );
