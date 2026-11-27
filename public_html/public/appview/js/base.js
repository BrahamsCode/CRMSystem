$(function(){
	
	$("input[type=file]").change(function(){
		
		if ( $(this).val().length > 0 ) {
			$("input[type=file] + i").addClass("active");
		} else { 
			$("input[type=file] + i").removeClass("active");
		}
		
	});
	
});