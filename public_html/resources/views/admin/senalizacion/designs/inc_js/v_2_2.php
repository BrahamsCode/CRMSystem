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
	
	if ( v.val() == 1 ) {
		
		$(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "25%");
		$(".col_padd_4").attr("width", "25%");
		
		$("option[data-contents_category_cd=18]").show();
		
	} else if ( v.val() == 2 ) {
		
		$(".tr_A, .tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x3840.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank2160x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "50%");
		
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=18]").show();
		$(".tr_C").find("option[data-contents_category_cd=20]").show();
		
	} else if ( v.val() == 3 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x3840.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank2160x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".tr_B, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "50%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "25%");
		
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=18]").show();
		$(".tr_A").find("option[data-contents_category_cd=20]").show();
		
	} else if ( v.val() == 4 ) {
		
		$(".tr_A, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x3840.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "25%");
		
		$(".tr_A, .tr_C").find("option[data-contents_category_cd=18]").show();
		$(".tr_B").find("option[data-contents_category_cd=19]").show();
		
	} else if ( v.val() == 5 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x3840.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".tr_B, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "25%");
		
		$(".tr_A").find("option[data-contents_category_cd=19]").show();
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=18]").show();
		
	} else if ( v.val() == 6 ) {
		
		$(".tr_A, .tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x3840.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank2160x1920.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "50%");
		$(".col_padd_2").attr("width", "50%");
		
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=20]").show();
		
	} else if ( v.val() == 7 ) {
		
		$(".tr_A, .tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x1920.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1080x3840.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "50%");
		$(".col_padd_2").attr("width", "50%");
		
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=19]").show();
		
	} else if ( v.val() == 8 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1080x3840.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank2160x3840.jpg', '_blank', 'width=600,height=800');");
			}
		});
		
		$(".col_padd_1").attr("width", "100%");
		
		$(".tr_A").find("option[data-contents_category_cd=21]").show();
		
	}
	
	if ( v.val() == 1 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
	} else if ( v.val() == 2 || v.val() == 3 || v.val() == 4 || v.val() == 5 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
	} else if( v.val() == 6 || v.val() == 7 ) {
		$(".tr_A").show();
		$(".tr_B").show();
	} else {
		$(".tr_A").show();
	}
}


$(function(){
	
	$("select[name^=img_path_ary]").change(function(){
		
		var c_cd     = $(this).find('option:selected').attr("data-contents_category_cd");
		var img_path = $(this).find('option:selected').attr("img_path");
		var f_path = "";
		if ( c_cd == 1 ) {
			f_path = "/signage/upload/1080x1920/" + img_path;
		} else if (c_cd == 2  ) { 
			f_path = "/signage/upload/1080x3840/" + img_path;
		}
		
		console.log( f_path );
		
		$(this).prev().prev().find("img").attr("src", f_path);
		$(this).prev().prev().attr("onclick", "window.open('" + f_path + "', '_blank', 'width=600,height=800');");
		
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