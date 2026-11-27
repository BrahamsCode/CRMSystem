{{-- inc/footer.php: jQuery y el menú plegable; signagePost() reemplaza los borrados por GET (?delete_cd=) del legacy --}}
<script src="/signage/js/jquery-3.1.1.js"></script>
<script>
(function (window, document) {

	var layout   = document.getElementById('layout'),
		menu	 = document.getElementById('menu'),
		menuLink = document.getElementById('menuLink');

	function toggleClass(element, className) {
		var classes = element.className.split(/\s+/),
			length = classes.length,
			i = 0;

		for(; i < length; i++) {
		  if (classes[i] === className) {
			classes.splice(i, 1);
			break;
		  }
		}
		// The className is not found
		if (length === classes.length) {
			classes.push(className);
		}

		element.className = classes.join(' ');
	}

	menuLink.onclick = function (e) {
		var active = 'active';

		e.preventDefault();
		toggleClass(layout, active);
		toggleClass(menu, active);
		toggleClass(menuLink, active);
	};

}(this, this.document));

function signagePost(url) {
	var form = document.createElement('form');
	form.method = 'POST';
	form.action = url;
	var token = document.createElement('input');
	token.type = 'hidden';
	token.name = '_token';
	token.value = document.querySelector('meta[name=csrf-token]').getAttribute('content');
	form.appendChild(token);
	document.body.appendChild(form);
	form.submit();
}
</script>
