window.addEventListener( 'load', function() {
	document.querySelectorAll( '.kdnaform-settings-tabs__navigation a' ).forEach( function( tab ) {
		tab.addEventListener( 'click', function ( e ) {

			e.preventDefault();

			// Get selected tab.
			var selectedTab = e.target.dataset.tab;

			// Hide active tab.
			document.querySelectorAll( '.kdnaform-settings-tabs__navigation .active, .kdnaform-settings-tabs__container.active' ).forEach( function( item ) {
				item.classList.remove( 'active' );
			} );

			// Set selected tab to active tab input.
			document.querySelector( 'input[name="kdnaform_settings_tab"]' ).value = selectedTab;

			// Show selected tab.
			e.target.classList.add( 'active' );
			document.querySelector( '.kdnaform-settings-tabs__container[data-tab="' + selectedTab + '"]' ).classList.add( 'active' );

		} );
	} );
} );