/**
 * Conditional Logic flyout.
 *
 * The field settings sidebar carries a "Conditional Logic" accordion row.
 * Clicking its cog slides a panel out beside the sidebar holding the actual
 * conditional logic settings.
 *
 * KDNA Forms puts the settings sidebar on the LEFT, so the panel opens to the
 * RIGHT. The positioning itself lives in CSS; this file only drives the
 * open/close state and keeps the row's status badge in sync.
 */
( function ( $ ) {
	'use strict';

	var CONTAINER_ID = 'conditional_logic_flyout_container';

	/**
	 * The flyout container. Returned fresh each time because the editor
	 * re-renders parts of the sidebar as fields are selected.
	 *
	 * @returns {jQuery}
	 */
	function $container() {
		return $( '#' + CONTAINER_ID );
	}

	/**
	 * Move the panel out of the sidebar and onto the body.
	 *
	 * .editor-sidebar is position:sticky with overflow:auto and z-index:1.
	 * The overflow clips absolutely positioned descendants, and the z-index
	 * makes it a stacking context that ties with the editor preview, so a
	 * panel left inside it is both cut off and painted under the preview. No
	 * amount of z-index on the panel itself can escape either. Reparenting to
	 * the body sidesteps both, at the cost of having to position it ourselves.
	 *
	 * @returns {void}
	 */
	function detach() {
		var $c = $container();

		if ( ! $c.length || $c.parent().is( 'body' ) ) {
			return;
		}

		$c.addClass( 'conditional_logic_flyout_container--detached' ).appendTo( 'body' );
	}

	/**
	 * Pin the panel alongside the sidebar.
	 *
	 * Measured rather than derived from CSS: the sidebar is sticky and sized
	 * by flow, so its edges are not knowable from the stylesheet alone.
	 *
	 * @returns {void}
	 */
	function position() {
		var $c = $container(),
			$fly = $c.find( '.conditional_logic_flyout' ),
			sidebar = document.querySelector( '.editor-sidebar' );

		if ( ! $fly.length || ! sidebar ) {
			return;
		}

		var rect = sidebar.getBoundingClientRect(),
			gap = 10,
			// Sit on whichever side of the sidebar has more room, so this keeps
			// working if the editor layout is ever flipped back.
			toRight = ( window.innerWidth - rect.right ) >= rect.left,
			width = Math.min( 650, ( toRight ? window.innerWidth - rect.right : rect.left ) - gap * 2 ),
			top = Math.max( rect.top, 0 );

		$fly.css( {
			position: 'fixed',
			top: top + 'px',
			height: ( window.innerHeight - top ) + 'px',
			width: width + 'px',
			left: toRight ? ( rect.right + gap ) + 'px' : 'auto',
			right: toRight ? 'auto' : ( window.innerWidth - rect.left + gap ) + 'px'
		} );
	}

	/**
	 * Whether the flyout is currently open.
	 *
	 * @returns {boolean}
	 */
	function isOpen() {
		return $container().hasClass( 'anim-in-active' );
	}

	/**
	 * Slide the flyout open.
	 *
	 * The two classes have to land on separate frames or the browser collapses
	 * them into one style resolution and the transition never runs.
	 */
	function open() {
		var $c = $container();

		if ( ! $c.length || isOpen() ) {
			return;
		}

		detach();
		position();

		$c = $container();
		$c.removeClass( 'anim-out-ready anim-out-active' ).addClass( 'anim-in-ready' );

		$( 'body' ).addClass( 'kdnaform-cl-flyout-open' );

		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				$c.addClass( 'anim-in-active' );
			} );
		} );

		$( '[data-js="cl-toggle"]' ).attr( 'aria-expanded', 'true' );
	}

	/**
	 * Slide the flyout closed and clear the animation classes afterwards.
	 */
	function close() {
		var $c = $container();

		if ( ! $c.length || ! $c.hasClass( 'anim-in-ready' ) ) {
			return;
		}

		$c.removeClass( 'anim-in-ready anim-in-active' ).addClass( 'anim-out-ready' );

		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				$c.addClass( 'anim-out-active' );
			} );
		} );

		// Drop the sidebar back down only once the panel has finished sliding
		// out, otherwise it disappears behind the preview mid-animation.
		window.setTimeout( function () {
			$c.removeClass( 'anim-out-ready anim-out-active' );
			$( 'body' ).removeClass( 'kdnaform-cl-flyout-open' );
		}, 220 );

		$( '[data-js="cl-toggle"]' ).attr( 'aria-expanded', 'false' );
	}

	/**
	 * Point the accordion row's badge at whether logic is enabled on the
	 * currently selected field.
	 */
	function syncStatus() {
		var enabled = $( '#field_conditional_logic' ).is( ':checked' ),
			$status = $( '[data-js="cl-status"]' ),
			// Never assume the localized object made it onto the page. A bare
			// reference here would throw and take the whole script with it.
			strings = window.kdnaformClFlyoutStrings || {};

		if ( ! $status.length ) {
			return;
		}

		$status
			.toggleClass( 'active', enabled )
			.text( enabled ? ( strings.active || 'Active' ) : ( strings.inactive || 'Inactive' ) );
	}

	$( function () {
		var $doc = $( document );

		$doc.on( 'click', '[data-js="cl-toggle"]', function ( event ) {
			event.preventDefault();
			isOpen() ? close() : open();
		} );

		$doc.on( 'click', '[data-js="cl-close"]', function ( event ) {
			event.preventDefault();
			close();
		} );

		// Close on Escape while the flyout has focus somewhere inside it.
		$doc.on( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && isOpen() ) {
				close();
			}
		} );

		// The editor fires this when a drag starts or a field is clicked.
		// Nothing was listening for it before, so honour it here.
		if ( window.gform && window.gform.tools && window.gform.tools.addAction ) {
			window.gform.tools.addAction( 'gform/flyout/close-all', close, 10, 'kdnaformClFlyout' );
		}

		// Selecting a different field swaps which settings the panel is
		// showing, so the badge has to be recalculated.
		$doc.on( 'change', '#field_conditional_logic', syncStatus );
		$doc.on( 'click', '.gfield', function () {
			window.setTimeout( syncStatus, 0 );
		} );

		// The panel is pinned to measured coordinates, so anything that moves
		// the sidebar has to re-measure.
		$( window ).on( 'resize scroll', function () {
			if ( isOpen() ) {
				position();
			}
		} );

		detach();
		syncStatus();
	} );

} )( jQuery );
