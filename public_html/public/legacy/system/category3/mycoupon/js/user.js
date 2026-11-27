
window.onload = function(){

	// チェックボタン選択可否
	$("#coupon_kubun:select").change(function () {
		if($("#coupon_kubun:select option#coupon_kubun2:selected").attr("selected")){
			$("#shouhi_point").removeAttr("disabled");
		}else{
			$("#shouhi_point").attr("disabled","disabled");
		}
	}).change();


}