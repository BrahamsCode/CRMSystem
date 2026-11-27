<script>

function scheduleRepeaTypeReload() {
	
	var v = $("input[name=schedule_repeat_type]:checked");
	
	if ( v.val() == 1 ) {
		$(".active_view").hide();
		$(".none_view").show();
	} else { 
		if ( v.val() == 2 ) {
			$("select[name=start_y], .start_y").hide();
			$("select[name=start_m], .start_m").hide();
			$("select[name=start_d], .start_d").hide();
			$("select[name=end_y], .end_y").hide();
			$("select[name=end_m], .end_m").hide();
			$("select[name=end_d], .end_d").hide();
		} else { 
			$("select[name=start_y], .start_y").show();
			$("select[name=start_m], .start_m").show();
			$("select[name=start_d], .start_d").show();
			$("select[name=end_y], .end_y").show();
			$("select[name=end_m], .end_m").show();
			$("select[name=end_d], .end_d").show();
		}
		$(".active_view").show();
		$(".none_view").hide();
	}
	
}


function flameCdReload() {
	
	var v = $("input[name=flame_cd]:checked");
	
	v.closest("table").find("tr").each(function(){
		$(this).removeClass("checkColor");
	});
	
	v.closest("tr").addClass("checkColor");
	$(".col_padd_base").attr("width", "0%");
	$("select[name^=img_path_ary] option").hide();
	$("select[name^=img_path_ary] option[value=blank]").show();
	
	$(".tr_Rebase").hide();
	
	
	
	$(".tr_A").find(".view_img img").each(function(){
		if ( $(this).attr("src").indexOf("blank") != -1  ) {
			$(this).attr("src", "/signage/img/blank7680x1080.jpg");
			$(this).parent("a").attr("onclick", "window.open('/signage/img/blank7680x1080.jpg', '_blank', 'width=800,height=600');");
		}
	});
	
	$(".col_padd_base").attr("width", "0%");
	$(".col_padd_1").attr("width", "100%");
	
	$("option[data-contents_category_cd=1]").show();
	$("option[data-contents_category_cd=2]").hide();
	$("option[data-contents_category_cd=3]").hide();
	$("option[data-contents_category_cd=4]").hide();
	$("option[data-contents_category_cd=5]").hide();
	$("option[data-contents_category_cd=6]").hide();
	$("option[data-contents_category_cd=7]").hide();
	$("option[data-contents_category_cd=8]").hide();
	
	$(".tr_Rebase").hide();
	
	$(".tr_A").show();
	
}

$(function(){
	
	$("select[name^=img_path_ary]").change(function(){
		
		var c_cd     = $(this).find('option:selected').attr("data-contents_category_cd");
		var img_path = $(this).find('option:selected').attr("img_path");
		var f_path = "";
		if ( c_cd == 1 ) {
			f_path = "/signage/upload/1920x1080/" + img_path;
		} else { 
			
			var v = $("input[name=flame_cd]:checked");
			var c = $(this).closest(".tr_Rebase").attr("class");
			
			if ( v.val() == 1 ) {
				f_path = "/signage/img/blank1920x1080.jpg";
			}
		}
		
		console.log( f_path );
		
		$(this).prev().prev().find("img").attr("src", f_path);
		$(this).prev().prev().attr("onclick", "window.open('" + f_path + "', '_blank', 'width=800,height=600');");
		
	});
	
	scheduleRepeaTypeReload();
	
	$("input[name=schedule_repeat_type]").click(function(){
		scheduleRepeaTypeReload();
	});
	
	flameCdReload();
	
	$("input[name=flame_cd]").click(function(){
		flameCdReload();
	});
	
});
</script>