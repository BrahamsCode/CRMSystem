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
	
	$(".view_img img").each(function(){
		if ( $(this).attr("src").indexOf("blank") != -1  ) {
			$(this).attr("src", "/signage/img/blank1920x1080.jpg");
			$(this).parent("a").attr("onclick", "window.open('/signage/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');");
		}
	});
	
	if ( v.val() == 1 ) {
		
		$(".col_padd_1").attr("width", "12.5%");
		$(".col_padd_2").attr("width", "12.5%");
		$(".col_padd_3").attr("width", "12.5%");
		$(".col_padd_4").attr("width", "12.5%");
		$(".col_padd_5").attr("width", "12.5%");
		$(".col_padd_6").attr("width", "12.5%");
		$(".col_padd_7").attr("width", "12.5%");
		$(".col_padd_8").attr("width", "12.5%");
		
		$("option[data-contents_category_cd=1]").show();
		
	} else if ( v.val() >= 2 && v.val() <= 11 ) {
		
		$(".col_padd_1").attr("width", "12.5%");
		$(".col_padd_2").attr("width", "12.5%");
		$(".col_padd_3").attr("width", "12.5%");
		$(".col_padd_4").attr("width", "12.5%");
		$(".col_padd_5").attr("width", "12.5%");
		$(".col_padd_6").attr("width", "12.5%");
		$(".col_padd_7").attr("width", "12.5%");
		
		$(".tr_Rebase").find("option[data-contents_category_cd=1]").show();
		
		if ( v.val() == 2 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 3 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 4 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 5 ) {
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 6 ) {
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 7 ) {
			$(".tr_G").find("option[data-contents_category_cd=1]").hide();
			$(".tr_G").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 8 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 9 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 10 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 11 ) {
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=5]").show();
		}
		
	} else if ( v.val() >= 12 && v.val() <= 42 ) {
		
		$(".col_padd_1").attr("width", "12.5%");
		$(".col_padd_2").attr("width", "12.5%");
		$(".col_padd_3").attr("width", "12.5%");
		$(".col_padd_4").attr("width", "12.5%");
		$(".col_padd_5").attr("width", "12.5%");
		$(".col_padd_6").attr("width", "12.5%");
		
		$(".tr_Rebase").find("option[data-contents_category_cd=1]").show();
		
		if ( v.val() == 12 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 13 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 14 ) {
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 15 ) {
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 16 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 17 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 18 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 19 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 20 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 21 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 22 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 23 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 24 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 25 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 26 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 27 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 28 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 28 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 29 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 30 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 31 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 32 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 33 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 34 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 35 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 36 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 37 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 38 ) {
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 39 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 40 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 41 ) {
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 42 ) {
			$(".tr_F").find("option[data-contents_category_cd=1]").hide();
			$(".tr_F").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=5]").show();
		} else {
			
		}
		
	} else if ( v.val() >= 43 && v.val() <= 69 ) {
		
		$(".col_padd_1").attr("width", "12.5%");
		$(".col_padd_2").attr("width", "12.5%");
		$(".col_padd_3").attr("width", "12.5%");
		$(".col_padd_4").attr("width", "12.5%");
		$(".col_padd_5").attr("width", "12.5%");
		
		$(".tr_Rebase").find("option[data-contents_category_cd=1]").show();
		
		if ( v.val() == 43 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
		} else if ( v.val() == 44 ) {
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=4]").show();
		} else if ( v.val() == 45 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
		} else if ( v.val() == 46 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=6]").show();
		} else if ( v.val() == 47 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=6]").show();
		} else if ( v.val() == 48 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 49 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 50 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 51 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 52 ) {
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=3]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 53 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 54 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 55 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 56 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 57 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 58 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 59 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 60 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 61 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 62 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 63 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 64 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 65 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 66 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_E").find("option[data-contents_category_cd=1]").hide();
			$(".tr_E").find("option[data-contents_category_cd=2]").show();
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 67 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 68 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 69 ) {
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
		} else {
			
		}
		
	} else if ( v.val() >= 70 && v.val() <= 97 ) {
		
		$(".col_padd_1").attr("width", "12.5%");
		$(".col_padd_2").attr("width", "12.5%");
		$(".col_padd_3").attr("width", "12.5%");
		$(".col_padd_4").attr("width", "12.5%");
		
		$(".tr_Rebase").find("option[data-contents_category_cd=1]").show();
		
		if ( v.val() == 70 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 71 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 72 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 73 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 74 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 75 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 76 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 77 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 78 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 79 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 80 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 81 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 82 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 83 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 84 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=6]").show();
		} else if ( v.val() == 85 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 86 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 87 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=6]").show();
		} else if ( v.val() == 88 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=6]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 89 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 90 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 91 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 92 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 93 ) {
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=4]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 94 ) {
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=4]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 95 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=4]").show();
		} else if ( v.val() == 96 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 97 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
			$(".tr_D").find("option[data-contents_category_cd=1]").hide();
			$(".tr_D").find("option[data-contents_category_cd=2]").show();
		} else {
			
		}
		
	} else if ( v.val() >= 98 && v.val() <= 112 ) {
		
		$(".col_padd_1").attr("width", "12.5%");
		$(".col_padd_2").attr("width", "12.5%");
		$(".col_padd_3").attr("width", "12.5%");
		
		$(".tr_Rebase").find("option[data-contents_category_cd=1]").show();
		
		if ( v.val() == 98 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=4]").show();
		} else if ( v.val() == 99 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 100 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 101 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 102 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 103 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 104 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=3]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 105 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=2]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 106 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=7]").show();
		} else if ( v.val() == 107 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=3]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=3]").show();
		} else if ( v.val() == 108 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=2]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=6]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=2]").show();
		} else if ( v.val() == 109 ) {
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=7]").show();
		} else if ( v.val() == 110 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=6]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 111 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=5]").show();
		} else if ( v.val() == 112 ) {
			$(".tr_A").find("option[data-contents_category_cd=1]").hide();
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=1]").hide();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
			$(".tr_C").find("option[data-contents_category_cd=1]").hide();
			$(".tr_C").find("option[data-contents_category_cd=6]").show();
		} else {
			
		}
		
	} else if ( v.val() >= 113 && v.val() <= 116 ) {
		
		$(".col_padd_1").attr("width", "12.5%");
		$(".col_padd_2").attr("width", "12.5%");
		
		if ( v.val() == 113 ) {
			$(".tr_A").find("option[data-contents_category_cd=4]").show();
			$(".tr_B").find("option[data-contents_category_cd=4]").show();
		} else if ( v.val() == 114 ) {
			$(".tr_A").find("option[data-contents_category_cd=5]").show();
			$(".tr_B").find("option[data-contents_category_cd=7]").show();
		} else if ( v.val() == 115 ) {
			$(".tr_A").find("option[data-contents_category_cd=6]").show();
			$(".tr_B").find("option[data-contents_category_cd=6]").show();
		} else if ( v.val() == 116 ) {
			$(".tr_A").find("option[data-contents_category_cd=7]").show();
			$(".tr_B").find("option[data-contents_category_cd=5]").show();
		}
		
	} else { 
		
		$(".col_padd_1").attr("width", "12.5%");
		
		$(".tr_Rebase").find("option[data-contents_category_cd=8]").show();
		
	}
	
	$(".tr_Rebase").hide();
	
	if ( v.val() == 1 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
		$(".tr_E").show();
		$(".tr_F").show();
		$(".tr_G").show();
		$(".tr_H").show();
	} else if ( v.val() >= 2 && v.val() <= 11 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
		$(".tr_E").show();
		$(".tr_F").show();
		$(".tr_G").show();
	} else if ( v.val() >= 12 && v.val() <= 42 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
		$(".tr_E").show();
		$(".tr_F").show();
	} else if ( v.val() >= 43 && v.val() <= 69 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
		$(".tr_E").show();
	} else if ( v.val() >= 70 && v.val() <= 97 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
		$(".tr_D").show();
	} else if ( v.val() >= 98 && v.val() <= 112 ) {
		$(".tr_A").show();
		$(".tr_B").show();
		$(".tr_C").show();
	} else if ( v.val() >= 113 && v.val() <= 116 ) {
		$(".tr_A").show();
		$(".tr_B").show();
	} else { 
		$(".tr_A").show();
	}
}

var init_check = false;

function reloadFrameTable()
{
	
	var r = $("select[name=split_number]").val();
	$("tr[class^=split_]").hide();
	$(".split_" + r).show();
	
	if ( init_check ) { 
		$(".split_" + r + ":eq(0)").find("input[name=flame_cd]").prop("checked", true);
	} else { 
		setTimeout(function(){
			$(".frame_table_wrap").scrollTop($(".checkColor")[1].offsetTop);
		}, 10);
	}
	
	init_check = true;
	
	flameCdReload();
}

$(function(){
	
	$("select[name=split_number]").change(function(){
		
		reloadFrameTable();
		
	});
	
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
		} else if ( c_cd == 5 ) { 
			f_path = "/signage/upload/1920x2160/" + img_path;
		} else if ( c_cd == 6 ) { 
			f_path = "/signage/upload/3840x2160/" + img_path;
		} else if ( c_cd == 7 ) { 
			f_path = "/signage/upload/5760x2160/" + img_path;
		} else if ( c_cd == 8 ) { 
			f_path = "/signage/upload/7680x2160/" + img_path;
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
			
			f_path = "/signage/img/blank1920x1080.jpg";
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
	
	reloadFrameTable()
	
});
</script>