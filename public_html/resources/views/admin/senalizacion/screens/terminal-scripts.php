<script>

var init_check = false;

function reloladDdisplayBase()
{
	var display_direction = $("select[name=display_direction]").val();
	var display_number = $("select[name=display_number]").val();
	
	$(".display_base").each(function(){
		$(this).hide();
	});
	
	if ( display_direction.indexOf("縦") != -1 ) { 
		$(".display_number_v_" + display_number).show();
		if ( init_check ) { 
			if(display_number == 4)
			{
				$("input[name=display_type][value=v_" + "2" + "_2]").prop("checked", true);
			}
			else 
			{
				$("input[name=display_type][value=v_" + display_number + "_1]").prop("checked", true);
			}
		}
	} else { 
		$(".display_number_h_" + display_number).show();
		if ( init_check ) { 
			if(display_number == 4)
			{
				$("input[name=display_type][value=h_" + "2" + "_2]").prop("checked", true);
			}
			else 
			{
				$("input[name=display_type][value=h_" + display_number + "_1]").prop("checked", true);
			}
		} else { 
			if ( location.href.indexOf("cd=") == -1 ) { 
				$("input[name=display_type][value=h_1_1]").prop("checked", true);
			}
		}
	}
	
	init_check = true;
	
	reloadReviewFrame();
}

function reloadReviewFrame()
{
	var $check = $("input[type=radio][name=display_type]:checked").val();
	
	var d = $check.split("_");
	if ( d[0].indexOf("h") != -1 ) {
		$("#review_frame").width( d[1] * 160 );
		$("#review_frame").height( d[2] * 90 );
	} else { 
		$("#review_frame").width( d[1] * 90 );
		$("#review_frame").height( d[2] * 160 );
	}
	
	$('#review_frame')[0].contentDocument.location.reload(true);
}

$(function(){
	
	$("select[name=display_direction], select[name=display_number]").change(function(){
		reloladDdisplayBase();
	});
	
	$("#packages_cd").change(function(){
		
		if ( $(this).val() ) { 
			$("#review_frame").attr("src", "<?=route('admin.senalizacion.packages.preview')?>?cd=" + $(this).val() );
			$("#review_frame").show();
		} else { 
			$("#review_frame").hide();
		}
		
	});
	
	$("input[type=radio][name=display_type]").change(function(){
		reloadReviewFrame();
	});
	
	reloladDdisplayBase();
	
});
</script>
