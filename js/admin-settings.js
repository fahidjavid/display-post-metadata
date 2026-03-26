/**
 * Display Post Metadata — Admin Settings JS
 *
 * - Initialises wp-color-picker on all .dpm-color-picker inputs.
 * - Updates the live preview when any style setting changes.
 * - Drives the tab switcher.
 */
( function ( $ ) {

	'use strict';

	// -----------------------------------------------------------------------
	// Tab switching
	// -----------------------------------------------------------------------
	$( '.dpm-tab' ).on( 'click', function () {
		var target = $( this ).data( 'tab' );

		$( '.dpm-tab' ).removeClass( 'active' );
		$( this ).addClass( 'active' );

		$( '.dpm-tab-panel' ).removeClass( 'active' );
		$( '#dpm-tab-' + target ).addClass( 'active' );
	} );

	// -----------------------------------------------------------------------
	// Live preview helpers
	// -----------------------------------------------------------------------
	var $preview = $( '#dpm-preview-container' );

	/**
	 * Apply a CSS custom property to the preview container.
	 *
	 * @param {string} prop   CSS variable name, e.g. '--dpm-text-color'.
	 * @param {string} value  Value to set.
	 */
	function applyVar( prop, value ) {
		if ( $preview.length ) {
			$preview[ 0 ].style.setProperty( prop, value );
		}
	}

	/**
	 * Update the design variation class on the preview container.
	 *
	 * @param {string} variation  e.g. 'default', 'card', 'inline', 'minimal'.
	 */
	function applyDesign( variation ) {
		if ( ! $preview.length ) return;

		var designs = [ 'dpm-design-default', 'dpm-design-inline', 'dpm-design-card', 'dpm-design-minimal' ];
		designs.forEach( function ( cls ) {
			$preview.removeClass( cls );
		} );
		$preview.addClass( 'dpm-design-' + variation );
	}

	// -----------------------------------------------------------------------
	// Initialise color pickers
	// -----------------------------------------------------------------------
	$( '.dpm-color-picker' ).wpColorPicker( {
		change: function ( event, ui ) {
			var cssVar = $( this ).data( 'var' );
			if ( cssVar ) {
				applyVar( cssVar, ui.color.toString() );
			}
		},
		clear: function () {
			var cssVar = $( this ).data( 'var' );
			if ( cssVar ) {
				applyVar( cssVar, '' );
			}
		}
	} );

	// -----------------------------------------------------------------------
	// Numeric inputs — font size, icon size, border radius
	// -----------------------------------------------------------------------
	$( '.dpm-range-control' ).on( 'input change', function () {
		var cssVar = $( this ).data( 'var' );
		var unit   = $( this ).data( 'unit' ) || 'px';
		var val    = parseInt( $( this ).val(), 10 );

		if ( cssVar && ! isNaN( val ) && val > 0 ) {
			applyVar( cssVar, val + unit );
		}
	} );

	// -----------------------------------------------------------------------
	// Design variation select
	// -----------------------------------------------------------------------
	$( '#dpm_design_variation' ).on( 'change', function () {
		applyDesign( $( this ).val() );
	} );

} )( jQuery );
