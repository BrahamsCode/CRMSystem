
var touchsupport = ('ontouchstart' in window);
if(touchsupport){
	touchstart = 'touchstart';
	touchmove = 'touchmove';
	touchend = 'touchend';
}else{
	touchstart = 'mousedown';
	touchmove = 'mousemove';
	touchend = 'mouseup';
}


