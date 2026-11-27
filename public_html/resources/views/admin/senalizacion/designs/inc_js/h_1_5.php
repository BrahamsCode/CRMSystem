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
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "20%");
		$(".col_padd_3").attr("width", "20%");
		$(".col_padd_4").attr("width", "20%");
		$(".col_padd_5").attr("width", "20%");
		
		$("option[data-contents_category_cd=1]").show();
		
	} else if ( v.val() == 2 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B, .tr_C, .tr_D").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "40%");
		$(".col_padd_2").attr("width", "20%");
		$(".col_padd_3").attr("width", "20%");
		$(".col_padd_4").attr("width", "20%");
		
		$(".tr_A").find("option[data-contents_category_cd=5]").show();
		$(".tr_B, .tr_C, .tr_D").find("option[data-contents_category_cd=1]").show();
	} else if ( v.val() == 3 ) {
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A, .tr_C, .tr_D").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "40%");
		$(".col_padd_3").attr("width", "20%");
		$(".col_padd_4").attr("width", "20%");
		
		$(".tr_B").find("option[data-contents_category_cd=5]").show();
		$(".tr_A, .tr_C, .tr_D").find("option[data-contents_category_cd=1]").show();
		
	} else if ( v.val() == 4 ) {
		
		$(".tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A, .tr_B, .tr_D").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "20%");
		$(".col_padd_3").attr("width", "40%");
		$(".col_padd_4").attr("width", "20%");
		
		$(".tr_C").find("option[data-contents_category_cd=5]").show();
		$(".tr_A, .tr_B, .tr_D").find("option[data-contents_category_cd=1]").show();
	} else if ( v.val() == 5 ) {
		
		$(".tr_D").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A, .tr_B, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "20%");
		$(".col_padd_3").attr("width", "20%");
		$(".col_padd_4").attr("width", "40%");
		
		$(".tr_D").find("option[data-contents_category_cd=5]").show();
		$(".tr_A, .tr_B, .tr_C").find("option[data-contents_category_cd=1]").show();
		
	} else if ( v.val() == 6 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x3240.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x3240.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "60%");
		$(".col_padd_2").attr("width", "20%");
		$(".col_padd_3").attr("width", "20%");
		
		$(".tr_A").find("option[data-contents_category_cd=13]").show();
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=1]").show();
		
	} else if ( v.val() == 7 ) {
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x3240.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x3240.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "60%");
		$(".col_padd_3").attr("width", "20%");
		
		$(".tr_B").find("option[data-contents_category_cd=13]").show();
		$(".tr_A, .tr_C").find("option[data-contents_category_cd=1]").show();
		
	} else if ( v.val() == 8 ) {
		
		$(".tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x3240.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x3240.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A, .tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "20%");
		$(".col_padd_3").attr("width", "60%");
		
		$(".tr_C").find("option[data-contents_category_cd=13]").show();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=1]").show();
	} else if ( v.val() == 9 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x3240.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "40%");
		$(".col_padd_3").attr("width", "40%");
		
		$(".tr_A").find("option[data-contents_category_cd=5]").show();
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=1]").show();
	} else if ( v.val() == 10 ) {
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "40%");
		$(".col_padd_2").attr("width", "20%");
		$(".col_padd_3").attr("width", "40%");
		
		$(".tr_B").find("option[data-contents_category_cd=1]").show();
		$(".tr_A, .tr_C").find("option[data-contents_category_cd=5]").show();
	} else if ( v.val() == 11 ) {
		
		$(".tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A, .tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "40%");
		$(".col_padd_2").attr("width", "40%");
		$(".col_padd_3").attr("width", "20%");
		
		$(".tr_C").find("option[data-contents_category_cd=1]").show();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=5]").show();
	} else if ( v.val() == 12 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x4320.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x4320.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "20%");
		$(".col_padd_2").attr("width", "80%");
		
		$(".tr_A").find("option[data-contents_category_cd=1]").show();
		$(".tr_B").find("option[data-contents_category_cd=14]").show();
	} else if ( v.val() == 13 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x3240.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x3240.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "40%");
		$(".col_padd_2").attr("width", "60%");
		
		$(".tr_A").find("option[data-contents_category_cd=5]").show();
		$(".tr_B").find("option[data-contents_category_cd=13]").show();
	} else if ( v.val() == 14 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x3240.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x3240.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x2160.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x2160.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "60%");
		$(".col_padd_2").attr("width", "40%");
		
		$(".tr_A").find("option[data-contents_category_cd=13]").show();
		$(".tr_B").find("option[data-contents_category_cd=5]").show();
	} else if ( v.val() == 15 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x4320.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x4320.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "80%");
		$(".col_padd_2").attr("width", "20%");
		
		$(".tr_A").find("option[data-contents_category_cd=14]").show();
		$(".tr_B").find("option[data-contents_category_cd=1]").show();
	} else if( v.val() == 16 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x5400.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x5400.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "100%");
		
		$("option[data-contents_category_cd=15]").show();
	}
	
	if ( v.val() == 1 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
		$(".tr_E").show();
	} else if( v.val() == 2 || v.val() == 3 ||  v.val() == 4 || v.val() == 5 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
	} else if ( v.val() == 6 || v.val() == 7 || v.val() == 8  || v.val() == 9  || v.val() == 10  || v.val() == 11 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
	} else if( v.val() == 12 || v.val() == 13 || v.val() == 14  || v.val() == 15 ) {
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
			f_path = "/signage/upload/1920x1080/" + img_path;
		} else if (c_cd == 2  ) { 
			f_path = "/signage/upload/3840x1080/" + img_path;
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