/**
 * Display Post Metadata — Admin Settings JS
 *
 * - Tab switching
 * - wp-color-picker initialisation + live preview sync
 * - Range slider sync (label + hidden input + CSS var)
 * - Design variation preview sync (radio → .dpm-design-variation class)
 * - Copy-to-clipboard for shortcode reference
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

	function applyVar( prop, value ) {
		if ( $preview.length ) {
			if ( value ) {
				$preview[ 0 ].style.setProperty( prop, value );
			} else {
				$preview[ 0 ].style.removeProperty( prop );
			}
		}
	}

	function applyDesign( variation ) {
		if ( ! $preview.length ) return;
		var classes = $preview[ 0 ].className.replace( /dpm-design-\S+/g, '' ).trim();
		$preview[ 0 ].className = classes + ' dpm-design-' + variation;
	}

	// -----------------------------------------------------------------------
	// Color pickers
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
	// Range sliders
	// Syncs: range input → value badge → hidden input → CSS var on preview
	// -----------------------------------------------------------------------
	$( '.dpm-range-control' ).on( 'input change', function () {
		var $range  = $( this );
		var cssVar  = $range.data( 'var' );
		var unit    = $range.data( 'unit' ) || 'px';
		var target  = $range.data( 'target' );
		var val     = parseInt( $range.val(), 10 );

		if ( isNaN( val ) ) return;

		// Update the badge (e.g. #dpm_font_size_val)
		$( '#' + target + '_val' ).text( val + unit );

		// Update the hidden input that gets submitted
		$( '#' + target ).val( val );

		// Update CSS var on preview
		if ( cssVar ) {
			applyVar( cssVar, val + unit );
		}
	} );

	// -----------------------------------------------------------------------
	// Design variation radio cards → live preview
	// -----------------------------------------------------------------------
	$( 'input[name="dpm_settings[design_variation]"]' ).on( 'change', function () {
		applyDesign( $( this ).val() );
	} );

	// -----------------------------------------------------------------------
	// Copy shortcode to clipboard
	// -----------------------------------------------------------------------
	$( '#dpm-copy-sc' ).on( 'click', function () {
		var $btn  = $( this );
		var text  = $( '#dpm-sc-code' ).text();

		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( text ).then( function () {
				onCopied( $btn );
			} );
		} else {
			// Fallback
			var $tmp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
			document.execCommand( 'copy' );
			$tmp.remove();
			onCopied( $btn );
		}
	} );

	function onCopied( $btn ) {
		var orig = $btn.html();
		$btn.addClass( 'copied' )
			.html( '<span class="dashicons dashicons-yes"></span> Copied!' );

		setTimeout( function () {
			$btn.removeClass( 'copied' ).html( orig );
		}, 2000 );
	}

} )( jQuery );
