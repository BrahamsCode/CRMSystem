

function loadEvent(){

	var eventTarget1 = document.getElementById("step1_1");
	var eventTarget2 = document.getElementById("step1_2");

	var displayTarget1 = document.getElementById("month_set");
	var displayTarget2 = document.getElementById("week_set");




	function usrDistinction(){

		if(eventTarget1.checked){
			displayTarget1.style.display = "block";
			displayTarget2.style.display = "none";
		}else if(eventTarget2.checked){
			displayTarget1.style.display = "none";
			displayTarget2.style.display = "block";
		}else{
			displayTarget1.style.display = "none";
			displayTarget2.style.display = "none";
		}
	}



	// リスナーの追加
	for(i=1; i<=2; i++){
		if(eval("eventTarget" + i + ".addEventListener")){
			eval("eventTarget" + i + ".addEventListener(\"click\", usrDistinction, false)");
		}else if(eval("eventTarget" + i + ".attachEvent")){
			eval("eventTarget" + i + ".attachEvent(\"onclick\", usrDistinction)");
		}
	}
}




window.onload = function(){
	loadEvent();

	//firefoxなどでリロードしたときにも判別するように
	var eventTarget1 = document.getElementById("step1_1");
	var eventTarget2 = document.getElementById("step1_2");

	var displayTarget1 = document.getElementById("month_set");
	var displayTarget2 = document.getElementById("week_set");

	if(eventTarget1.checked){
		displayTarget1.style.display = "block";
		displayTarget2.style.display = "none";
	}else if(eventTarget2.checked){
		displayTarget1.style.display = "none";
		displayTarget2.style.display = "block";
	}else{
		displayTarget1.style.display = "none";
		displayTarget2.style.display = "none";
	}

}
