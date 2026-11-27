{{-- Checkbox 「全店舗」 (índice 0) que desmarca y bloquea las tiendas del mismo hueco (cover y banner). --}}
<script>
	$(function(){
		$("input[name^=mypage_config\\[cover], input[name^=mypage_config\\[banner]").click(function(){
			if ( $(this).prop("checked") && $(this).attr("name").split("][")[2].split("]").join("") == 0 ) {
				$(this).parent().parent("div").find("input[name^=mypage_config]").each(function(){
					if ( $(this).attr("name").split("][")[2].split("]").join("") != 0 ) {
						$(this).removeAttr("checked");
						$(this).attr("disabled", "disabled")
					}
				});
			} else if( $(this).attr("name").split("][")[2].split("]").join("") == 0 ) {
				$(this).parent().parent("div").find("input[name^=mypage_config]").each(function(){
					if ( $(this).attr("name").split("][")[2].split("]").join("") != 0 ) {
						$(this).removeAttr("checked");
						$(this).removeAttr("disabled")
					}
				})
			}
		});
	});
</script>
