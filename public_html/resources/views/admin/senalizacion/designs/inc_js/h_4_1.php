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
	
	$("option[data-contents_category_cd=5]").hide();
	$("option[data-contents_category_cd=6]").hide();
	$("option[data-contents_category_cd=7]").hide();
	$("option[data-contents_category_cd=8]").hide();
	
	$(".col_padd_5").attr("width", "0%");
	$(".col_padd_6").attr("width", "0%");
	$(".col_padd_7").attr("width", "0%");
	$(".col_padd_8").attr("width", "0%");
	
	$("select[name^=img_path_ary] option").hide();
	$("select[name^=img_path_ary] option[value=blank]").show();
	
	$(".tr_E").hide();
	$(".tr_F").hide();
	$(".tr_G").hide();
	$(".tr_H").hide();
	
	if ( v.val() == 1 ) {
		
		$(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "25%");
		$(".col_padd_4").attr("width", "25%");
		
		$("option[data-contents_category_cd=1]").show();
		$("option[data-contents_category_cd=2]").hide();
		$("option[data-contents_category_cd=3]").hide();
		$("option[data-contents_category_cd=4]").hide();
		$("option[data-contents_category_cd=5]").hide();
		$("option[data-contents_category_cd=6]").hide();
		$("option[data-contents_category_cd=7]").hide();
		
	} else if ( v.val() == 2 ) {
		
		$(".tr_A, .tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank3840x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank3840x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "46.9%");
		$(".col_padd_4").attr("width", "0%");
		
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=1]").show();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=2]").hide();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=3]").hide();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=4]").hide();
		
		$(".tr_C").find("option[data-contents_category_cd=1]").hide();
		$(".tr_C").find("option[data-contents_category_cd=2]").show();
		$(".tr_C").find("option[data-contents_category_cd=3]").hide();
		$(".tr_C").find("option[data-contents_category_cd=4]").hide();
	} else if ( v.val() == 3 ) {
		
		$(".tr_A, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank3840x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank3840x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "46.9%");
		$(".col_padd_3").attr("width", "25%");
		$(".col_padd_4").attr("width", "0%");
		
		$(".tr_A, .tr_C").find("option[data-contents_category_cd=1]").show();
		$(".tr_A, .tr_C").find("option[data-contents_category_cd=2]").hide();
		$(".tr_A, .tr_C").find("option[data-contents_category_cd=3]").hide();
		$(".tr_A, .tr_C").find("option[data-contents_category_cd=4]").hide();
		
		$(".tr_B").find("option[data-contents_category_cd=1]").hide();
		$(".tr_B").find("option[data-contents_category_cd=2]").show();
		$(".tr_B").find("option[data-contents_category_cd=3]").hide();
		$(".tr_B").find("option[data-contents_category_cd=4]").hide();
	} else if ( v.val() == 4 ) {
		
		$(".tr_B, .tr_C").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank3840x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank3840x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "46.9%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "25%");
		$(".col_padd_4").attr("width", "0%");
		
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=1]").show();
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=2]").hide();
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=3]").hide();
		$(".tr_B, .tr_C").find("option[data-contents_category_cd=4]").hide();
		
		$(".tr_A").find("option[data-contents_category_cd=1]").hide();
		$(".tr_A").find("option[data-contents_category_cd=2]").show();
		$(".tr_A").find("option[data-contents_category_cd=3]").hide();
		$(".tr_A").find("option[data-contents_category_cd=4]").hide();
	} else if ( v.val() == 5 ) {
		
		$(".tr_A, .tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank3840x1080.jpg");
			}
		});
		
		$(".col_padd_1").attr("width", "50%");
		$(".col_padd_2").attr("width", "50%");
		$(".col_padd_3").attr("width", "0%");
		$(".col_padd_4").attr("width", "0%");
		
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=1]").hide();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=2]").show();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=3]").hide();
		$(".tr_A, .tr_B").find("option[data-contents_category_cd=4]").hide();
	} else if ( v.val() == 6 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank5760x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank5760x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "71%");
		$(".col_padd_2").attr("width", "25%");
		$(".col_padd_3").attr("width", "0%");
		$(".col_padd_4").attr("width", "0%");
		
		$(".tr_A").find("option[data-contents_category_cd=1]").hide();
		$(".tr_A").find("option[data-contents_category_cd=2]").hide();
		$(".tr_A").find("option[data-contents_category_cd=3]").show();
		$(".tr_A").find("option[data-contents_category_cd=4]").hide();
		
		$(".tr_B").find("option[data-contents_category_cd=1]").show();
		$(".tr_B").find("option[data-contents_category_cd=2]").hide();
		$(".tr_B").find("option[data-contents_category_cd=3]").hide();
		$(".tr_B").find("option[data-contents_category_cd=4]").hide();
	} else if ( v.val() == 7 ) {
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank1920x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".tr_B").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank5760x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank5760x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "25%");
		$(".col_padd_2").attr("width", "71%");
		$(".col_padd_3").attr("width", "0%");
		$(".col_padd_4").attr("width", "0%");
		
		$(".tr_B").find("option[data-contents_category_cd=1]").hide();
		$(".tr_B").find("option[data-contents_category_cd=2]").hide();
		$(".tr_B").find("option[data-contents_category_cd=3]").show();
		$(".tr_B").find("option[data-contents_category_cd=4]").hide();
		
		$(".tr_A").find("option[data-contents_category_cd=1]").show();
		$(".tr_A").find("option[data-contents_category_cd=2]").hide();
		$(".tr_A").find("option[data-contents_category_cd=3]").hide();
		$(".tr_A").find("option[data-contents_category_cd=4]").hide();
	} else { 
		
		$(".tr_A").find(".view_img img").each(function(){
			if ( $(this).attr("src").indexOf("blank") != -1  ) {
				$(this).attr("src", "/signage/img/blank7680x1080.jpg");
				$(this).parent("a").attr("onclick", "window.open('/signage/img/blank7680x1080.jpg', '_blank', 'width=800,height=600');");
			}
		});
		
		$(".col_padd_1").attr("width", "100%");
		$(".col_padd_2").attr("width", "0%");
		$(".col_padd_3").attr("width", "0%");
		$(".col_padd_4").attr("width", "0%");
		
		$("option[data-contents_category_cd=1]").hide();
		$("option[data-contents_category_cd=2]").hide();
		$("option[data-contents_category_cd=3]").hide();
		$("option[data-contents_category_cd=4]").show();
	}
	
	if ( v.val() == 1 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
	} else if ( v.val() == 2 || v.val() == 3 || v.val() == 4 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").hide();
	} else if ( v.val() == 5 || v.val() == 6 || v.val() == 7 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").hide();
		$(".tr_D").hide();
	} else { 
		$(".tr_A").show();
		$(".tr_B").hide();
		$(".tr_C").hide();
		$(".tr_D").hide();
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
		} else if ( c_cd == 3 ) { 
			f_path = "/signage/upload/5760x1080/" + img_path;
		} else if ( c_cd == 4 ) { 
			f_path = "/signage/upload/7680x1080/" + img_path;
		} else { 
			
			var v = $("input[name=flame_cd]:checked");
			var c = $(this).closest(".tr_Rebase").attr("class");
			
			if ( v.val() == 1 ) {
				f_path = "/signage/img/blank1920x1080.jpg";
			} else if ( v.val() == 2 ) {
				if ( $(this).closest(".tr_Rebase").hasClass("tr_C") ) {
					f_path = "/signage/img/blank3840x1080.jpg";
				} else { 
					f_path = "/signage/img/blank1920x1080.jpg";
				}
			} else if ( v.val() == 3 ) {
				if ( $(this).closest(".tr_Rebase").hasClass("tr_B") ) {
					f_path = "/signage/img/blank3840x1080.jpg";
				} else { 
					f_path = "/signage/img/blank1920x1080.jpg";
				}
			} else if ( v.val() == 4 ) {
				if ( $(this).closest(".tr_Rebase").hasClass("tr_A") ) {
					f_path = "/signage/img/blank3840x1080.jpg";
				} else { 
					f_path = "/signage/img/blank1920x1080.jpg";
				}
			} else if ( v.val() == 5 ) {
				f_path = "/signage/img/blank3840x1080.jpg";
			} else if ( v.val() == 6 ) {
				if ( $(this).closest(".tr_Rebase").hasClass("tr_A") ) {
					f_path = "/signage/img/blank5760x1080.jpg";
				} else { 
					f_path = "/signage/img/blank1920x1080.jpg";
				}
			} else if ( v.val() == 7 ) {
				if ( $(this).closest(".tr_Rebase").hasClass("tr_C") ) {
					f_path = "/signage/img/blank5760x1080.jpg";
				} else { 
					f_path = "/signage/img/blank1920x1080.jpg";
				}
			} else {
				f_path = "/signage/img/blank7680x1080.jpg";
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