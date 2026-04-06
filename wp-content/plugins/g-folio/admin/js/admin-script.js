/* =============================================================
   G Folio — Admin JavaScript
   Handles: tabs, media uploader, sliders, toggles, color picker
   ============================================================= */

/* global jQuery, wp, gfolioAdmin */
( function ( $ ) {
	'use strict';

	/* =========================================================
	   DOCUMENT READY
	   ========================================================= */
	$( document ).ready( function () {
		gfolioInitTabs();
		gfolioInitSliders();
		gfolioInitToggles();
		gfolioInitRadioHighlight();
		gfolioInitMediaUploader();
		gfolioInitColorPickers();
		gfolioInitBtnStylePicker();
		gfolioInitSegmentedControl();
		gfolioInitPortfolioAccordion();
		gfolioInitPortfolioBoxTabs();
		gfolioInitPortfolioBoxColors();
		gfolioInitPortfolioBoxSliders();
		gfolioInitPortfolioBoxHelpers();
		gfolioInitAspectRatioControls();
	} );

	/* =========================================================
	   TABS (Settings Page)
	   ========================================================= */
	function gfolioInitTabs() {
		var $tabBtns   = $( '.gfolio-tab-btn' );
		var $tabPanels = $( '.gfolio-tab-panel' );

		if ( ! $tabBtns.length ) return;

		// Restore active tab from localStorage
		var savedTab = localStorage.getItem( 'gfolio_active_tab' );
		if ( savedTab && $( '#' + savedTab ).length ) {
			$tabBtns.removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
			$tabPanels.removeClass( 'is-active' );
			$( '[data-tab="' + savedTab + '"]' ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );
			$( '#' + savedTab ).addClass( 'is-active' );
		}

		$tabBtns.on( 'click', function () {
			var targetId = $( this ).data( 'tab' );

			$tabBtns.removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
			$tabPanels.removeClass( 'is-active' );

			$( this ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );
			$( '#' + targetId ).addClass( 'is-active' );

			localStorage.setItem( 'gfolio_active_tab', targetId );
		} );
	}

	/* =========================================================
	   SLIDERS (Column count, Opacity)
	   ========================================================= */
	function gfolioInitSliders() {
		$( '.gfolio-slider' ).each( function () {
			var $slider     = $( this );
			var $valueLabel = $slider.siblings( '.gfolio-slider-value' );

			$valueLabel.text( $slider.val() );

			$slider.on( 'input change', function () {
				$valueLabel.text( $( this ).val() );
			} );
		} );
	}

	/* =========================================================
	   MASTER TOGGLES (show/hide dependent fields)
	   ========================================================= */
	function gfolioInitToggles() {
		$( '.gfolio-master-toggle' ).each( function () {
			applyToggleVisibility( $( this ) );
		} );

		$( document ).on( 'change', '.gfolio-master-toggle', function () {
			applyToggleVisibility( $( this ) );
		} );
	}

	function applyToggleVisibility( $checkbox ) {
		var targetSelector = $checkbox.data( 'target' );
		if ( ! targetSelector ) return;

		var $targets = $( targetSelector );
		if ( $checkbox.is( ':checked' ) ) {
			$targets.removeClass( 'hidden' ).slideDown( 200, function () {
				// Re-initialise any TinyMCE editors that were hidden on page load.
				// wp_editor() inside display:none fails to init — this fixes it.
				$targets.find( '.wp-editor-wrap' ).each( function () {
					var $wrap   = $( this );
					var editorId = $wrap.find( 'textarea.wp-editor-area' ).attr( 'id' );
					if ( ! editorId ) return;

					// If TinyMCE is loaded and the editor exists but has no iframe content
					if ( typeof tinymce !== 'undefined' ) {
						var ed = tinymce.get( editorId );
						if ( ed ) {
							// Editor initialised — just make it visible
							ed.show();
						} else {
							// Editor never initialised — set it up now
							if ( typeof tinyMCEPreInit !== 'undefined' && tinyMCEPreInit.mceInit && tinyMCEPreInit.mceInit[ editorId ] ) {
								tinymce.init( tinyMCEPreInit.mceInit[ editorId ] );
							}
						}
					}

					// Also re-init quicktags if present
					if ( typeof QTags !== 'undefined' ) {
						var qtSettings = ( typeof tinyMCEPreInit !== 'undefined' && tinyMCEPreInit.qtInit )
							? tinyMCEPreInit.qtInit[ editorId ]
							: null;
						if ( qtSettings && ! QTags.getInstance( editorId ) ) {
							QTags( qtSettings );
							QTags._buttonsInit();
						}
					}
				} );
			} );
		} else {
			$targets.slideUp( 200, function () {
				$( this ).addClass( 'hidden' );
			} );
		}
	}

	/* =========================================================
	   RADIO OPTION HIGHLIGHT
	   ========================================================= */
	function gfolioInitRadioHighlight() {
		$( document ).on( 'change', '.gfolio-radio-option input[type="radio"]', function () {
			var $group = $( this ).closest( '.gfolio-radio-group' );
			$group.find( '.gfolio-radio-option' ).removeClass( 'is-active' );
			$( this ).closest( '.gfolio-radio-option' ).addClass( 'is-active' );

			// Show/hide sub-fields for click behavior meta box
			gfolioClickTypeHandler( $( this ) );
		} );
	}

	function gfolioClickTypeHandler( $radio ) {
		var val = $radio.val();

		$( '.gfolio-field-page' ).toggleClass( 'hidden', val !== 'page' );
		$( '.gfolio-field-custom-url' ).toggleClass( 'hidden', val !== 'custom_url' );
		$( '.gfolio-field-blank' ).toggleClass( 'hidden', val === 'popup' );
	}

	/* =========================================================
	   MEDIA UPLOADER (Thumbnail Image Selector)
	   ========================================================= */
	function gfolioInitMediaUploader() {
		var mediaFrame;

		$( document ).on( 'click', '#gfolio-upload-thumb-btn', function ( e ) {
			e.preventDefault();

			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media( {
				title:    gfolioAdmin.mediaTitle,
				button:   { text: gfolioAdmin.mediaButton },
				library:  { type: 'image' },
				multiple: false,
			} );

			mediaFrame.on( 'select', function () {
				var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();

				var url = attachment.sizes && attachment.sizes.medium
					? attachment.sizes.medium.url
					: attachment.url;

				$( '#gfolio_thumbnail_id' ).val( attachment.id );
				$( '#gfolio-thumb-preview-img' ).attr( 'src', url ).show();
				$( '#gfolio-thumb-placeholder' ).hide();
				$( '#gfolio-remove-thumb-btn' ).removeClass( 'hidden' );
			} );

			mediaFrame.open();
		} );

		$( document ).on( 'click', '#gfolio-remove-thumb-btn', function ( e ) {
			e.preventDefault();
			$( '#gfolio_thumbnail_id' ).val( '' );
			$( '#gfolio-thumb-preview-img' ).attr( 'src', '' ).hide();
			$( '#gfolio-thumb-placeholder' ).show();
			$( this ).addClass( 'hidden' );
		} );
	}

	/* =========================================================
	   WORDPRESS COLOR PICKER
	   ========================================================= */
	function gfolioInitColorPickers() {
		// Skip .gfp-picker-display — those are managed by gfolioInitPortfolioBoxColors()
		$( '.gfolio-color-picker' ).not( '.gfp-picker-display' ).each( function () {
			var defaultColor = $( this ).data( 'default-color' ) || '#000000';

			$( this ).wpColorPicker( {
				defaultColor: defaultColor,
				change: function () {
					// Trigger change for live preview hooks (future use)
					$( this ).trigger( 'gfolio:color-change' );
				},
			} );
		} );
	}

	/* =========================================================
	   BUTTON STYLE PICKER
	   ========================================================= */
	function gfolioInitBtnStylePicker() {
		$( document ).on( 'change', '.gfolio-btn-style-picker input[type="radio"]', function () {
			$( this ).closest( '.gfolio-btn-style-picker' )
				.find( '.gfolio-style-option' )
				.removeClass( 'is-active' );
			$( this ).closest( '.gfolio-style-option' ).addClass( 'is-active' );
		} );
	}

	/* =========================================================
	   SEGMENTED CONTROL
	   ========================================================= */
	function gfolioInitSegmentedControl() {
		$( document ).on( 'change', '.gfolio-segmented-control input[type="radio"]', function () {
			$( this ).closest( '.gfolio-segmented-control' )
				.find( '.gfolio-segment' )
				.removeClass( 'is-active' );
			$( this ).closest( '.gfolio-segment' ).addClass( 'is-active' );
		} );
	}

	/* =========================================================
	   PORTFOLIO ACCORDION (Assign to Portfolio meta box)
	   ========================================================= */
	function gfolioInitPortfolioAccordion() {

		// Toggle arrow button independently opens/closes the category section
		$( document ).on( 'click', '.gfolio-accordion-toggle', function () {
			var $btn  = $( this );
			var $cats = $btn.closest( '.gfolio-portfolio-row' ).find( '.gfolio-inline-cats' );
			var isOpen = $btn.hasClass( 'is-open' );

			if ( isOpen ) {
				$cats.slideUp( 200 );
				$btn.removeClass( 'is-open' ).attr( 'aria-expanded', 'false' );
			} else {
				$cats.slideDown( 200 );
				$btn.addClass( 'is-open' ).attr( 'aria-expanded', 'true' );
			}
		} );

		// Checking a portfolio also opens its category section
		$( document ).on( 'change', '.gfolio-portfolio-checkbox', function () {
			var $cb   = $( this );
			var $row  = $cb.closest( '.gfolio-portfolio-row' );
			var $btn  = $row.find( '.gfolio-accordion-toggle' );
			var $cats = $row.find( '.gfolio-inline-cats' );

			if ( $cb.is( ':checked' ) && ! $btn.hasClass( 'is-open' ) ) {
				$cats.slideDown( 200 );
				$btn.addClass( 'is-open' ).attr( 'aria-expanded', 'true' );
			}
			// Note: unchecking does NOT auto-close so users can still see/adjust categories
		} );

		// Sync duplicate category checkboxes (same term_id can appear in multiple accordions)
		$( document ).on( 'change', '.gfolio-category-checkbox', function () {
			var val     = $( this ).val();
			var checked = $( this ).is( ':checked' );
			// Update all other checkboxes with the same value so they stay in sync
			$( '.gfolio-category-checkbox[value="' + val + '"]' ).not( this ).prop( 'checked', checked );
		} );
	}

	/* =========================================================
	   PORTFOLIO SETTINGS BOX — TABS
	   ========================================================= */
	function gfolioInitPortfolioBoxTabs() {
		$( document ).on( 'click', '.gfp-tab-btn', function () {
			var $btn     = $( this );
			var $pbox    = $btn.closest( '.gfolio-pbox' );
			var targetId = $btn.data( 'tab' );

			$pbox.find( '.gfp-tab-btn' ).removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
			$pbox.find( '.gfp-tab-panel' ).removeClass( 'is-active' );

			$btn.addClass( 'is-active' ).attr( 'aria-selected', 'true' );
			$pbox.find( '#' + targetId ).addClass( 'is-active' );
		} );
	}

	/* =========================================================
	   PORTFOLIO SETTINGS BOX — COLOR FIELDS
	   ========================================================= */
	function gfolioInitPortfolioBoxColors() {
		// Initialise WP color pickers in the portfolio box
		$( '.gfp-picker-display' ).each( function () {
			var $picker = $( this );
			var $field  = $picker.closest( '.gfp-color-field' );
			var $actual = $field.find( '.gfp-color-actual' );
			var defaultColor = $picker.data( 'default-color' ) || '#000000';

			$picker.wpColorPicker( {
				defaultColor: defaultColor,
				change: function ( event, ui ) {
					var color = ui.color.toString();
					$actual.val( color );
					$field.removeClass( 'is-global' ).addClass( 'is-custom' );
					$field.find( '.gfp-global-badge' ).addClass( 'hidden' );
					$field.find( '.gfp-reset-color' ).removeClass( 'hidden' );
				},
			} );
		} );

		// "Use Global" button — clear the custom value
		$( document ).on( 'click', '.gfp-reset-color', function () {
			var $field      = $( this ).closest( '.gfp-color-field' );
			var $actual     = $field.find( '.gfp-color-actual' );
			var globalColor = $field.data( 'global' );

			$actual.val( '' );
			$field.removeClass( 'is-custom' ).addClass( 'is-global' );
			$field.find( '.gfp-global-badge' ).removeClass( 'hidden' );
			$( this ).addClass( 'hidden' );

			// Reset the picker display to the global color
			$field.find( '.gfp-picker-display' ).iris( 'color', globalColor );
		} );
	}

	/* =========================================================
	   PORTFOLIO SETTINGS BOX — OPACITY SLIDER
	   ========================================================= */
	function gfolioInitPortfolioBoxSliders() {
		// "Customize" button — show the slider
		$( document ).on( 'click', '.gfp-customize-slider', function () {
			var $field    = $( this ).closest( '.gfp-slider-field' );
			var globalVal = $field.data( 'global' );

			$field.find( '.gfp-slider-global' ).addClass( 'hidden' );
			$field.find( '.gfp-slider-custom' ).removeClass( 'hidden' );

			// Initialise slider at the global value
			$field.find( '.gfp-slider-input' ).val( globalVal );
			$field.find( '.gfolio-slider-value' ).text( globalVal );
			$field.find( '.gfp-slider-actual' ).val( globalVal );
		} );

		// Slider movement — sync to hidden input
		$( document ).on( 'input change', '.gfp-slider-input', function () {
			var val    = $( this ).val();
			var $field = $( this ).closest( '.gfp-slider-field' );
			$field.find( '.gfp-slider-actual' ).val( val );
			$( this ).closest( '.gfolio-slider-wrap' ).find( '.gfolio-slider-value' ).text( val );
		} );

		// "Use Global" button — revert slider to global
		$( document ).on( 'click', '.gfp-reset-slider', function () {
			var $field = $( this ).closest( '.gfp-slider-field' );
			$field.find( '.gfp-slider-actual' ).val( '' );
			$field.find( '.gfp-slider-global' ).removeClass( 'hidden' );
			$field.find( '.gfp-slider-custom' ).addClass( 'hidden' );
		} );
	}

	/* =========================================================
	   PORTFOLIO SETTINGS BOX — MISC HELPERS
	   ========================================================= */
	function gfolioInitPortfolioBoxHelpers() {
		// Show/hide gap-size input based on the thumbnail-padding 3-way control
		$( document ).on( 'change', '[name="gfoliop_thumbnail_padding"]', function () {
			var $pbox = $( this ).closest( '.gfolio-pbox' );
			var $wrap = $pbox.find( '.gfp-padding-size-wrap' );
			if ( '1' === $( this ).val() ) {
				$wrap.removeClass( 'hidden' );
			} else {
				$wrap.addClass( 'hidden' );
			}
		} );
	}

	/* =========================================================
	   SHORTCODE CHIP — CLICK TO COPY
	   ========================================================= */
	$( document ).on( 'click', '.gfolio-shortcode-chip', function () {
		var $chip = $( this );
		var text  = $chip.data( 'shortcode' ) || $chip.text();

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function () {
				$chip.addClass( 'is-copied' );
				setTimeout( function () { $chip.removeClass( 'is-copied' ); }, 1500 );
			} );
		} else {
			// Fallback: select the text so the user can Cmd/Ctrl+C manually
			var range = document.createRange();
			range.selectNode( $chip[ 0 ] );
			window.getSelection().removeAllRanges();
			window.getSelection().addRange( range );
		}
	} );

	/* =========================================================
	   ASPECT RATIO CONTROLS
	   ========================================================= */
	function gfolioInitAspectRatioControls() {

		// Preset definitions — used for label formatting and dropdown matching
		var PRESETS = [
			{ v: 0.25,   l: '1:4'  },
			{ v: 0.5625, l: '9:16' },
			{ v: 0.75,   l: '3:4'  },
			{ v: 1.0,    l: '1:1'  },
			{ v: 1.3333, l: '4:3'  },
			{ v: 1.7778, l: '16:9' },
			{ v: 4.0,    l: '4:1'  },
		];

		// Format a numeric ratio as a readable label (e.g. 1.7778 → "16:9")
		function formatLabel( r ) {
			r = parseFloat( r );
			if ( isNaN( r ) ) { return ''; }
			for ( var i = 0; i < PRESETS.length; i++ ) {
				if ( Math.abs( r - PRESETS[ i ].v ) < 0.005 ) {
					return PRESETS[ i ].l;
				}
			}
			return parseFloat( r ).toFixed( 2 );
		}

		// Update the morphing rectangle and ratio label inside a .gfp-aspect-control
		function updatePreview( $control, r ) {
			r = parseFloat( r );
			if ( isNaN( r ) || r <= 0 ) { return; }
			var size = 44; // px — must match CSS stage inner area
			var w, h;
			if ( r >= 1 ) {
				w = size;
				h = Math.round( size / r );
			} else {
				h = size;
				w = Math.round( size * r );
			}
			$control.find( '.gfp-aspect-rect' ).css( { width: w + 'px', height: h + 'px' } );
			$control.find( '.gfp-aspect-label' ).text( formatLabel( r ) );
		}

		// Snap the preset <select> to a matching option, or "" for Custom
		function syncDropdown( $control, r ) {
			r = parseFloat( r );
			var matched = '';
			$control.find( '.gfp-aspect-preset option' ).each( function () {
				var val = parseFloat( $( this ).val() );
				if ( val && Math.abs( val - r ) < 0.005 ) {
					matched = $( this ).val();
				}
			} );
			$control.find( '.gfp-aspect-preset' ).val( matched );
		}

		// Write the ratio to the hidden actual input (looks inside control, then parent field)
		function writeActual( $control, r ) {
			var $actual = $control.find( '.gfp-aspect-actual' );
			if ( ! $actual.length ) {
				$actual = $control.closest( '.gfp-aspect-field, .gfp-slider-field' ).find( '.gfp-aspect-actual' );
			}
			if ( $actual.length ) {
				$actual.val( parseFloat( r ).toFixed( 4 ) );
			}
		}

		// Read the current actual value for a control
		function readActual( $control ) {
			var $actual = $control.find( '.gfp-aspect-actual' );
			if ( ! $actual.length ) {
				$actual = $control.closest( '.gfp-aspect-field, .gfp-slider-field' ).find( '.gfp-aspect-actual' );
			}
			return $actual.length ? parseFloat( $actual.val() ) : NaN;
		}

		// Full init of a single .gfp-aspect-control from its current actual value
		function initControl( $control ) {
			var r = readActual( $control );
			if ( isNaN( r ) || r <= 0 ) {
				// Fall back to the range slider's current value
				r = parseFloat( $control.find( '.gfp-aspect-range' ).val() ) || 1.7778;
			}
			$control.find( '.gfp-aspect-range' ).val( r );
			syncDropdown( $control, r );
			updatePreview( $control, r );
		}

		// ---- Initialize all controls on page load ----
		$( '.gfp-aspect-control' ).each( function () {
			initControl( $( this ) );
		} );

		// ---- Preset dropdown changed ----
		$( document ).on( 'change', '.gfp-aspect-preset', function () {
			var val = $( this ).val();
			if ( ! val ) { return; } // "Custom" — no snap
			var $control = $( this ).closest( '.gfp-aspect-control' );
			var r = parseFloat( val );
			$control.find( '.gfp-aspect-range' ).val( r );
			writeActual( $control, r );
			updatePreview( $control, r );
		} );

		// ---- Range slider moved ----
		$( document ).on( 'input change', '.gfp-aspect-range', function () {
			var r = parseFloat( $( this ).val() );
			var $control = $( this ).closest( '.gfp-aspect-control' );
			writeActual( $control, r );
			syncDropdown( $control, r );
			updatePreview( $control, r );
		} );

		// ---- "Customize" clicked on an aspect field ----
		// (existing handler shows the custom section; we additionally init the control)
		$( document ).on( 'click', '.gfp-aspect-field .gfp-customize-slider', function () {
			var $field      = $( this ).closest( '.gfp-aspect-field' );
			var globalVal   = parseFloat( $field.data( 'global' ) ) || 1.7778;
			var $control    = $field.find( '.gfp-aspect-control' );
			$control.find( '.gfp-aspect-range' ).val( globalVal );
			syncDropdown( $control, globalVal );
			updatePreview( $control, globalVal );
			// Note: writeActual is handled by the existing gfp-customize-slider handler
			// which sets .gfp-slider-actual (same element via dual class)
		} );
	}

} )( jQuery );
