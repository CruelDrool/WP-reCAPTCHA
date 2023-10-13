( function () {
	var toggleElements, status, input, icon;
	
	toggleElements = document.querySelectorAll( '.wp-hide-pw' );
	
	console.log(toggleElements);
	toggleElements.forEach( function (toggle) {
		console.log("heeey");
		toggle.classList.remove( 'hide-if-no-js' );
		toggle.addEventListener( 'click', togglePassword );
	} );
	
	function togglePassword() {
		status = this.getAttribute( 'data-toggle' );
		input = this.parentElement.getElementsByTagName('input')[0]
		icon = this.getElementsByTagName('span')[0]

		if ( 0 === parseInt( status, 10 ) ) {
			this.setAttribute( 'data-toggle', 1 );
			input.setAttribute( 'type', 'text' );
			icon.classList.remove( 'dashicons-visibility' );
			icon.classList.add( 'dashicons-hidden' );
		} else {
			this.setAttribute( 'data-toggle', 0 );
			input.setAttribute( 'type', 'password' );
			icon.classList.remove( 'dashicons-hidden' );
			icon.classList.add( 'dashicons-visibility' );
		}
	}
} )();
