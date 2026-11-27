$1113(document).ready(function() {
	var startX, startY, newElem, target, source, dest;

	// ============================================================================================================
	// Mover Reserva
	/***** Nota *****
	Usamos on o delegate para registrar los eventos de las reservas (.reserve).
	No se usa live porque con live stopPropagation() no funciona, lo que hace que
	se dispare el evento mousedown de .timeTable
	*****************/
	//$1113(".timeTable").on("mousedown", ".reserve", function(e) {
	$1113(".timeTable").delegate(".reserve", "mousedown", function(e) { // <== tiene el mismo efecto que la linea anterior
		target = $1113(this);
		var startLeft = target.position().left;

		target.css({
			opacity: 0.5,
			backgroundColor: "#EE0000"
		});
		source = dest = target.parent();
		
		startX = e.pageX;
		$1113("#results").empty();
		$1113("#results").append(".reserve:delegate<br/>");
		
		$1113(document).bind("mouseup", function(e) {
			
			set_color = "#A0D468";
			if ( staffKindColorAry[ target.attr("data-kubun") ] !== undefined ) {
				set_color = staffKindColorAry[ target.attr("data-kubun") ];
			}
			
			target.css({
				opacity: 0.7,
				backgroundColor: set_color
			});
			
			//target.remove();
			$1113("#results").append(e.type + "<br/>");
			$1113(".timeTable").unbind("mousemove mouseenter mouseleave");
			$1113(document).unbind("mouseup");
			$1113("#results").append("unbind(mouseenter mouseleave)<br/>unbind(mouseup)<br/>");
		});
		$1113(".timeTable").bind("mouseenter", function(e) {
			$1113("#results").append(e.type + "<br/>");
			dest = $1113(this);
			var temp = target.clone();
			dest.append(temp);
			target.remove();
			target = temp;
		});
		$1113(".timeTable").bind("mouseleave", function(e) {
			$1113("#results").append(e.type + "<br/>");
			dest = null
		});
		$1113(".timeTable").bind("mousemove", function(e) {
			$1113("#results").append(e.type + " ");
			//var x = e.pageX;
			var diff = e.pageX - startX;
			var rx = parseInt(diff / INTERVAL) * INTERVAL;
			var w = target.width();
			var x = startLeft + rx;
			//if (x < 0 || x + w > $1113(this).width()) return;
			if (x < 0) x = 0;
			if (x + w > $1113(this).width()) x = $1113(this).width() - w;
			
			setCellData( x, w, target, target.attr("data-key"), target.attr("data-kubun"), target.attr("data-komment") );
			
			target.css({
				left: x
			});
		});
		return false;
	});
	// ============================================================================================================


	// ============================================================================================================
	// Cambiar Tamaño
	/***** Nota *****
	Usamos delegate registrado en .timeTable (no en .reserve que es padre de .resize), para evitar conflictos con los eventos de Mover Reserva.
	*****************/
	$1113(".timeTable").delegate(".resize", "mousedown", function(e) {
		e.stopPropagation();
		target = $1113(this).parent(); // $1113(this).parent() se refiere al .reserve que es padre del .resize donde se ha presionado
		var startLeft = target.position().left;
		var startWidth = target.width();

		//target.css({opacity:0.5, backgroundColor: "#EE0000"});
		//source = dest = target.parent();

		startX = e.pageX;
		$1113("#results").empty();
		$1113("#results").append(".resize:delegate<br/>");

		$1113(document).bind("mouseup", function(e) {
			//target.css({opacity:0.7, backgroundColor: "#00EE00"});
			//target.remove();
			$1113("#results").append(e.type + "<br/>");
			$1113(".timeTable").unbind("mousemove");
			$1113(document).unbind("mouseup");
			$1113("#results").append("unbind(mousemove)<br/>unbind(mouseup)<br/>");
		});
		$1113(".timeTable").bind("mousemove", function(e) {
			$1113("#results").append(e.type + " ");
			//var x = e.pageX;
			var diff = e.pageX - startX;
			var rx = (parseInt(diff / INTERVAL) + 1) * INTERVAL;
			var w = startWidth + rx;
			$1113("#results").append("target.width():" + target.width() + ", rx:" + rx + " , startX:" + startX + ", e.pageX:" + e.pageX + " ,diff:" + diff + "<br/>");
			//var x = startLeft + rx;
			if (w < INTERVAL || startLeft + w > target.parent().width()) return; // target.parent() hace referencia al .timeTable padre de target (.reserve)
			
			setCellData( startLeft, w, target, target.attr("data-key"), target.attr("data-kubun"), target.attr("data-komment") );
			
			target.width(w);
		});
		return false;
	});
	// ============================================================================================================


	// ============================================================================================================
	// Crear Reserva
	$1113(".timeTable").bind("mousedown", function(e) {
		$1113("#results").empty();
		$1113("#results").append($1113(this).attr("class") + "<br/>");
		//newElem = null;
		//var target = $1113(e.target);
		target = $1113(this);
		var realStartX = e.pageX - $1113(this).offset().left;
		var realStartY = e.pageY - $1113(this).offset().top;
		var rx = parseInt(realStartX / INTERVAL) * INTERVAL;
		startX = rx;
		$1113("#results").append(e.type + ": " + realStartX + ", " + realStartY + "<br/>");
		$1113(this).bind("mousemove.newElem", function(e) {
			$1113("#results").append(e.type + " ");
			target = $1113(this);
			var x = e.pageX - target.offset().left;
			var y = e.pageY - target.offset().top;

			//if ( x >= target.width() ) $1113(document).trigger("mouseup.newElem");
			if (x >= target.width()) return;

			//var rx = (parseInt(x / 5) + 1) * 5;
			var rx = (parseInt(x / INTERVAL) + 1) * INTERVAL;
			w = rx - startX;
			if (w <= 0 || x <= realStartX) return;
			
			
			h = target.height();
			var newReserve = $1113(".newElem").length > 0 ? $1113(".newElem:first") : null;
			if (!newReserve) {
				newReserve = $1113('<div class="newElem"></div>');
				//newReserve.css({position:'absolute', top: 1, left:startX, width:w, height:h-2, opacity:0.5, backgroundColor: "#EE0000"});
				newReserve.css({
					position: 'absolute',
					top: 1,
					left: startX,
					width: w,
					height: h - 2,
					opacity: 0.5,
					backgroundColor: "#d36f68"
				});
				$1113(this).append(newReserve);
				//$1113("#results").append(e.type+"<br/>");
			} else {
				newReserve.css({
					width: w
				});
				setCellData( x, w, newReserve, newReserve.attr("data-key"), newReserve.attr("data-kubun"), newReserve.attr("data-komment") );
			}
			//$1113("#results").append(e.type+": "+x+", "+y+"<br/>");
			test = e;
		});

		$1113(document).bind("mouseup.newElem", function(e) {
			$1113("#results").append("mouseup.newElem<br>");
			if (target) {
				target.unbind("mousemove.newElem");
				$1113(document).unbind("mouseup.newElem");
				$1113("#results").append("unbind(mousemove)<br/>unbind(mouseup.newElem)<br/>");
				var newReserve = $1113(".newElem").length > 0 ? $1113(".newElem:first") : null
				if (newReserve) {
					var data_length = 0;
					for(var j in data){ data_length++; }
					
					var w = newReserve.width();
					var inner = 
						'<div class="test viewStr" style="width:100%;overflow:hidden;white-space:nowrap;">' + 
							'<span style="line-height: 16px;margin-top: 11px;display: inline-block;"></span>' + 
							'<div class="edit" style="position: absolute;top: 0px;left: 4px;;font-size: 15px;line-height: 20px;height: 20px;font-weight: bold;">' + 
								'<a href="#modalEdit" class="modalEdit" data-key="' + data_length + '" data-toggle="modal" style="color: #FFF;" >編</a>' + 
							'</div>' + 
							'<div class="delete" style="position: absolute;top: 0px;right: 2px;font-size: 30px;line-height: 20px;height: 20px;">×</div>' + 
						'</div>' + 
						'<div class="resize" style="position:absolute;right:-6px;top:0;width:12px;height:100%;cursor:e-resize;"></div>';
						
					newReserve.css({
						top: 0,
						opacity: 0.7,
						backgroundColor: "#a0d468",
						border: "2px solid #85bb4c"
					});
					
					var startLeft = newReserve.position().left;
					var startWidth = newReserve.width();
					
					newReserve
						.removeClass("newElem")
						.addClass("reserve")
						.addClass("cell")
						.addClass('btn')
						.addClass('btn-primary')
						.attr("data-key", data_length)
						.attr("data-kubun", 0)
						.attr("data-komment", "")
						.html(inner);
					// delete Elements
					newReserve.find(".delete").click(function(){
						deleteCell( $(this).closest(".cell") );
					});
					setCellData( startLeft, startWidth, newReserve, data_length, newReserve.attr("data-kubun"), newReserve.attr("data-komment") );
				}
				newElem = null;
				target = null;
				
			}
		});
	});
	// ============================================================================================================
	
	// ============================================================================================================
	// init
	// Elementsの共通化しない
	$1113.each(data, function(key, val) {
		setCellElement( this.cd, this.start, this.end, this.kubun, decodeURI( this.komment ), key );
	});
	// ============================================================================================================
	
	$1113("#pTop").delegate(".setShift", "click", function(e) {
		var data_length = 0;
		for(var j in data){ data_length++; }
		
		setCellElement( 
			$(this).attr("data-staff_cd"), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".sk_select").val(), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".ek_select").val(), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".kubun_select").val(), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".komment_textarea").val(), 
			data_length 
		);
	});
	
	$1113("#pTop").delegate(".setShiftEdit", "click", function(e) {
		$(".reserve[data-key=" + $(this).closest(".modal").find("*[name=key]").val() + "]").remove();
		setCellElement( 
			$(this).closest(".modal").find("*[name=staff_cd]").val(), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".sk_select").val(), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".ek_select").val(), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".kubun_select").val(), 
			$(this).closest(".modal-footer").prev(".modal-body").find(".komment_textarea").val(), 
			$(this).closest(".modal").find("*[name=key]").val()
		);
	});
	
	$1113(".setTimeYM").click(function(){
		$(".d").val( $(this).attr("data-value") );
	});
	
	$1113(".shopSelect").change(function(){
		$(this).closest("form").submit();
	});
	
	var step = parseInt( timeStrData[ shopWorkTime[0] + ":" + shopWorkTime[1] ] ) * 34;
	$1113("div.center-block").animate({scrollLeft: step }, 0);
	
	var thCnt = 0;
	var heithCnt = 0;
	
	$1113(".timeTableBase > tbody > tr > th:first-child").each(function(){
		if ( thCnt % 2 == 0 ) {
			$(this).css("top", 86 * heithCnt );
		} else {
			$(this).css("top", 86 * heithCnt + 24 );
			heithCnt++;
		}
		thCnt++;
	});
	
	$1113(".timeTableBase").delegate(".modalEdit", "click", function(e) {
		var sliceData = $(".sch" + $(this).attr("data-key")).val().split("_");
		var u = inverseObject( unixData  );
		var staff_cd  = sliceData[0];
		var sk      = u[ sliceData[1] ];
		var stime   = houeData[sk] + ":" + minuData[sk];
		var ek      = u[ sliceData[2] ];
		var etime   = houeData[ek] + ":" + minuData[ek];
		var kubun   = sliceData[3];
		var komment = sliceData[4];
		
		$("#modalEdit textarea, #modalEdit hidden, #modalEdit select").each(function(){
			$(this).val("");
		});
		
		$("#modalEdit").find("*[name=key]").val( $(this).attr("data-key") );
		$("#modalEdit").find("*[name=staff_cd]").val( staff_cd );
		$("#modalEdit").find(".sk_select").val( stime );
		$("#modalEdit").find("*[name=ek]").val( etime );
		$("#modalEdit").find("*[name=kubun]").val( kubun );
		$("#modalEdit").find("*[name=komment]").val( komment );
		
	});
	
	var $scrollTarget = $1113("div.center-block");
	$scrollTarget.on('scroll', scroll);
	
	function scroll(){
		$1113(".timeTableBase > tbody > tr > th:first-child").each(function(){
			$(this).css("left", $scrollTarget.scrollLeft() );
		});
	}
	
	scroll();
});

function setCellElement( cd, start, end, kubun, komment, key ){
	
	$parent = $("#timeTable" + cd).find(".timeTable");
	newReserve = $1113('<div class="newElem"></div>');
	newReserve.css({
		position: 'absolute',
		top: 1,
		left: timeStrData[ start ] * INTERVAL,
		width: ( timeStrData[ end ] - timeStrData[ start ] ) * INTERVAL,
		height: $parent.height() - 2,
		opacity: 0.5,
		backgroundColor: "#d36f68"
	});
	$parent.append(newReserve);
	
	kubunName = "未設定";
	if ( staffKindAry[ kubun ] !== undefined ) { 
		kubunName = staffKindAry[ kubun ];
	}
	
	var inner = 
		'<div class="test viewStr" style="width:100%;overflow:hidden;white-space:nowrap;">' + 
			'<span style="line-height: 16px;margin-top: 11px;display: inline-block;">' + start + '～' + end +  '<br>' + kubunName + '</span>' + 
			'<div class="edit" style="position: absolute;top: 0px;left: 4px;;font-size: 15px;line-height: 20px;height: 20px;font-weight: bold;">' + 
				'<a href="#modalEdit" class="modalEdit" data-key="' + key + '" data-toggle="modal" style="color: #FFF;" >編</a>' + 
			'</div>' + 
			'<div class="delete" style="position: absolute;top: 0px;right: 2px;font-size: 30px;line-height: 20px;height: 20px;">×</div>' + 
		'</div>' + 
		'<div class="resize" style="position:absolute;right:-6px;top:0;width:12px;height:100%;cursor:e-resize;"></div>';
	
	set_color = "#A0D468";
	if ( staffKindColorAry[ kubun ] !== undefined ) {
		set_color = staffKindColorAry[ kubun ];
	}
	
	newReserve.css({
		top: 0,
		opacity: 0.7,
		backgroundColor: set_color,
		border: "2px solid #85bb4c"
	});
	newReserve
		.removeClass("newElem")
		.addClass("reserve")
		.addClass("cell")
		.addClass('btn')
		.addClass('btn-primary')
		.attr("data-key", key)
		.attr("data-kubun", kubun)
		.attr("data-komment", komment)
		.html(inner);
	newReserve.find(".delete").click(function(){
		deleteCell( $(this).closest(".cell") );
	});
	setCellData( timeStrData[ start ] * INTERVAL, ( timeStrData[ end ] - timeStrData[ start ] ) * INTERVAL, newReserve, key, kubun, komment );
}

function setCellData( x, w, $_this, key, kubun, komment ) {
	// getScheduleTime
	var sk = Math.ceil( x / INTERVAL );
	var ek = Math.ceil( (x + w) / INTERVAL );
	
	if ($_this) {
		kubunName = "未設定";
		if ( staffKindAry[ kubun ] !== undefined ) { 
			kubunName = staffKindAry[ kubun ];
		}
		$_this.find(".viewStr span").html( houeData[sk] + ":" + minuData[sk] + "～" + houeData[ek] + ":" + minuData[ek] + "<br>" + kubunName );
	}
	
	if ( key !== undefined ) { 
		
		data[key] = {
			cd    : $_this.closest(".staffCD").attr("data-staff_cd"),
			start : houeData[ sk ] + ":" + minuData[ ek ],
			end   : houeData[ sk ] + ":" + minuData[ ek ]
		};
		if ( $_this.closest(".staffCD").attr("data-day") > 0 ) {
			var ss = parseInt( unixData[ sk ] ) + ( parseInt( $_this.closest(".staffCD").attr("data-day") ) - 1 ) * 86400;
			var ee = parseInt( unixData[ ek ] ) + ( parseInt( $_this.closest(".staffCD").attr("data-day") ) - 1 ) * 86400;
			var setVal = $_this.closest(".staffCD").attr("data-staff_cd") + "_" + ss + "_" + ee + "_" + kubun + "_" + komment;
		} else { 
			var setVal = $_this.closest(".staffCD").attr("data-staff_cd") + "_" + unixData[ sk ] + "_" + unixData[ ek ] + "_" + kubun + "_" + komment;
		}
		$("#sche_body").find(".sch" + key).remove();
		$("#sche_body").append("<input type='hidden' class='sch" + key + "' name='sch[" + key + "]' value='" + setVal + "'>");
	}
	
	return [sk, ek];
}

function deleteCell( $_this ) {
	$_this.remove();
	$("#sche_body").find(".sch" + $_this.attr("data-key")).remove();
}