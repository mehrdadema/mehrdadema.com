/* =============================================================
   G Folio — Public JavaScript
   Handles: Isotope grid, category filtering, lightbox, expand panel
   ============================================================= */

/* global jQuery, Isotope, gfolioPublic */
( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		$( '.gfolio-portfolio-wrap' ).each( function () {
			GFolioPortfolio.init( $( this ) );
		} );
	} );

	/* =========================================================
	   MAIN MODULE
	   ========================================================= */
	var GFolioPortfolio = {

		init: function ( $wrap ) {
			this.$wrap    = $wrap;
			this.$grid    = $wrap.find( '.gfolio-grid' );
			this.$filterBar = $wrap.find( '.gfolio-filter-bar' );
			this.$lightbox  = $wrap.find( '.gfolio-lightbox' );
			this.$expandPanel = $wrap.find( '.gfolio-expand-panel' );
			this.isMasonry  = $wrap.data( 'masonry' ) === 'true' || $wrap.data( 'masonry' ) === true;
			this.gap        = parseInt( $wrap.data( 'gap' ) ) || 0;
			this.columns    = parseInt( $wrap.data( 'columns' ) ) || 3;
			this.expandAnim = $wrap.data( 'expand-anim' ) || 'slide';
			this.$activeItem = null;
			this.iso        = null;

			// Bind 'this' for callbacks
			var self = this;
			this._handleItemClick = function () { self.onItemClick( $( this ) ); };
			this._handleFilterClick = function () { self.onFilterClick( $( this ) ); };
			this._handleExpandClose = function () { self.closeExpandPanel(); };
			this._handleLightboxClose = function () { self.closeLightbox(); };
			this._handleKeyup = function ( e ) {
				if ( e.key === 'Escape' ) {
					self.closeLightbox();
					self.closeExpandPanel();
				}
			};

			this.unclipPageBuilderAncestors();
			this.bindEvents();   // bind first — clicks must work even if Isotope is still loading
			this.initIsotope();  // async-safe; retries until Isotope is ready
		},

		/* -------------------------------------------------------
		   ISOTOPE INITIALIZATION
		   ------------------------------------------------------- */
		/* -------------------------------------------------------
		   UNCLIP PAGE-BUILDER ANCESTORS
		   Beaver Builder (and other page builders) apply overflow:hidden
		   to their column/row wrappers.  That clips both the grid height
		   and the expand panel.  Walk up the tree and force
		   overflow:visible on the known offending wrappers.
		   ------------------------------------------------------- */
		unclipPageBuilderAncestors: function () {
			/*
			 * Walk every ancestor between the portfolio wrap and <body>.
			 * Any element with overflow:hidden will clip the expand panel
			 * (and in some BB configs, the grid itself).  We can't target
			 * just known BB class names because BB uses several nested
			 * wrappers (.fl-row, .fl-row-content, .fl-col, .fl-col-content,
			 * .fl-module, .fl-module-content) and themes/plugins can add
			 * their own.  Stopping at <body> avoids removing the document
			 * scrollbar.
			 */
			this.$wrap.parentsUntil( 'body' ).each( function () {
				var $el = $( this );
				if ( $el.css( 'overflow' ) === 'hidden' || $el.css( 'overflow-y' ) === 'hidden' ) {
					$el.css( { overflow: 'visible', 'overflow-y': 'visible' } );
				}
			} );
		},

		initIsotope: function () {
			var self  = this;
			var $grid = this.$grid;

			/*
			 * Isotope (and its bundled imagesLoaded) is loaded from a CDN.
			 * On an uncached first load it may not have arrived yet when this
			 * script runs.  Rather than blocking clicks (bindEvents already ran),
			 * we retry every 100 ms until Isotope is available — at which point
			 * the grid layout kicks in.
			 */
			if ( typeof Isotope === 'undefined' ) {
				setTimeout( function () { self.initIsotope(); }, 100 );
				return;
			}

			var options = {
				itemSelector: '.gfolio-item',
				layoutMode:   this.isMasonry ? 'masonry' : 'fitRows',
				percentPosition: true,
				transitionDuration: '0.4s',
				hiddenStyle:  { opacity: 0, transform: 'scale(0.95)' },
				visibleStyle: { opacity: 1, transform: 'scale(1)' },
			};

			if ( this.isMasonry ) {
				options.masonry = { columnWidth: '.gfolio-item', gutter: this.gap };
			}

			var doInit = function () {
				self.iso = new Isotope( $grid[0], options );
			};

			// imagesLoaded is bundled with isotope.pkgd but guard defensively
			if ( $.fn.imagesLoaded ) {
				$grid.imagesLoaded( doInit );
			} else {
				doInit();
			}
		},

		/* -------------------------------------------------------
		   EVENT BINDING
		   ------------------------------------------------------- */
		bindEvents: function () {
			var self = this;

			// Item click
			this.$grid.on( 'click', '.gfolio-item', this._handleItemClick );

			// Filter buttons
			this.$filterBar.on( 'click', '.gfolio-filter-btn', this._handleFilterClick );

			// Expand close
			this.$expandPanel.on( 'click', '.gfolio-expand-close', this._handleExpandClose );

			// Lightbox close (backdrop + button)
			this.$lightbox.on( 'click', '.gfolio-lightbox-backdrop, .gfolio-lightbox-close', this._handleLightboxClose );

			// Keyboard escape
			$( document ).on( 'keyup', this._handleKeyup );

			// Prevent lightbox container click from closing
			this.$lightbox.on( 'click', '.gfolio-lightbox-container', function ( e ) {
				e.stopPropagation();
			} );
		},

		/* -------------------------------------------------------
		   ITEM CLICK HANDLER
		   ------------------------------------------------------- */
		onItemClick: function ( $item ) {
			var mode   = $item.data( 'mode' );
			var href   = $item.data( 'href' );
			var target = $item.data( 'target' ) || '_self';

			if ( 'expand' === mode ) {
				this.toggleExpandPanel( $item );
			} else if ( 'popup' === mode ) {
				this.openLightbox( $item );
			} else if ( 'link' === mode && href ) {
				if ( '_blank' === target ) {
					window.open( href, '_blank', 'noopener noreferrer' );
				} else {
					window.location.href = href;
				}
			}
		},

		/* -------------------------------------------------------
		   FILTER HANDLER (Isotope)
		   ------------------------------------------------------- */
		onFilterClick: function ( $btn ) {
			var filterVal = $btn.data( 'filter' );

			this.$filterBar.find( '.gfolio-filter-btn' ).removeClass( 'is-active' );
			$btn.addClass( 'is-active' );

			if ( this.iso ) {
				this.iso.arrange( { filter: filterVal === '*' ? '*' : filterVal } );
			}

			// Close any open expand panel on filter change
			this.closeExpandPanel();
		},

		/* -------------------------------------------------------
		   EXPAND PANEL
		   ------------------------------------------------------- */
		toggleExpandPanel: function ( $item ) {
			var self = this;

			// Clicking the same item again closes the panel
			if ( this.$activeItem && this.$activeItem.is( $item ) ) {
				this.closeExpandPanel();
				return;
			}

			this.closeExpandPanel( function () {
				self.openExpandPanel( $item );
			} );
		},

		openExpandPanel: function ( $item ) {
			var $panel  = this.$expandPanel;
			var $inner  = $panel.find( '.gfolio-expand-inner' );
			var $data   = $item.find( '.gfolio-content-data' );

			if ( ! $data.length ) return;

			// Build inner HTML
			$inner.html( this.buildExpandHTML( $data ) );

			// Position pointer toward item (relative to the wrap, not the grid)
			var itemOffset  = $item.offset();
			var wrapOffset  = this.$wrap.offset();
			var pointerX    = itemOffset.left - wrapOffset.left + ( $item.outerWidth() / 2 );
			$panel.css( '--expand-pointer-x', pointerX + 'px' );

			/*
			 * Keep the expand panel OUTSIDE the grid and appended to the
			 * portfolio wrap.  Inserting it inside the grid (after a .gfolio-item)
			 * caused it to be invisible in page builders like Beaver Builder whose
			 * column wrappers apply overflow:hidden — the panel would open (the
			 * scroll animation fired) but nothing appeared.
			 *
			 * Placing it after the grid, as a sibling, lets the portfolio wrap
			 * (which has overflow:visible) grow naturally to contain it, and the
			 * BB column follows because the wrap is in normal document flow.
			 */
			if ( ! $.contains( this.$wrap[0], $panel[0] ) || $panel.closest( this.$grid ).length ) {
				this.$wrap.append( $panel );
			}

			// Show with animation
			$panel.removeClass( 'anim-slide anim-fade' );
			$panel.css( 'display', 'block' );

			// Force reflow before adding animation class
			$panel[0].offsetHeight; // jshint ignore:line

			$panel.addClass( 'anim-' + this.expandAnim );
			$panel.attr( 'aria-hidden', 'false' );

			// Mark active item
			$item.addClass( 'is-expanded' );
			this.$activeItem = $item;

			// Relayout Isotope masonry items (not the panel itself)
			if ( this.iso ) {
				this.iso.layout();
			}

			// Scroll panel into view
			var self = this;
			setTimeout( function () {
				var panelTop = $panel.offset().top - 40;
				$( 'html, body' ).animate( { scrollTop: panelTop }, 400 );
			}, 100 );
		},

		closeExpandPanel: function ( callback ) {
			var self   = this;
			var $panel = this.$expandPanel;

			if ( ! $panel.is( ':visible' ) ) {
				if ( callback ) callback();
				return;
			}

			$panel.fadeOut( 250, function () {
				$panel.attr( 'aria-hidden', 'true' );
				$panel.removeClass( 'anim-slide anim-fade' );
				$panel.find( '.gfolio-expand-inner' ).empty();

				if ( self.$activeItem ) {
					self.$activeItem.removeClass( 'is-expanded' );
					self.$activeItem = null;
				}

				if ( self.iso ) self.iso.layout();

				if ( callback ) callback();
			} );
		},

		/* Find the last item in the same visual row as $item */
		findRowEnd: function ( $item ) {
			var $items      = this.$grid.find( '.gfolio-item:visible' );
			var itemTop     = Math.round( $item.offset().top );
			var $lastInRow  = $item;

			$items.each( function () {
				var $el    = $( this );
				var elTop  = Math.round( $el.offset().top );
				if ( Math.abs( elTop - itemTop ) <= 4 ) {
					$lastInRow = $el;
				} else if ( elTop > itemTop ) {
					return false; // break
				}
			} );

			return $lastInRow;
		},

		/* -------------------------------------------------------
		   LIGHTBOX
		   ------------------------------------------------------- */
		openLightbox: function ( $item ) {
			var $data  = $item.find( '.gfolio-content-data' );
			var $lbody = this.$lightbox.find( '.gfolio-lightbox-body' );

			$lbody.html( this.buildLightboxHTML( $data ) );

			/*
			 * Move the lightbox to <body> before showing it.
			 *
			 * Page builders (Beaver Builder, Elementor, etc.) often apply CSS
			 * transforms (translate3d, scale, etc.) to their row/column/module
			 * wrappers for entrance animations or GPU acceleration.  Any CSS
			 * transform on an ancestor creates a new stacking context, which
			 * causes position:fixed descendants to be positioned relative to
			 * that transformed ancestor rather than the viewport.  The result is
			 * a lightbox that appears squished inside the module area instead of
			 * covering the full screen.
			 *
			 * Appending to <body> guarantees the lightbox has no transformed
			 * ancestor and always covers the full viewport correctly.
			 */
			// Detach first so it is always the very last child of <body>
			// (DOM order matters for z-index ties within the same stacking context)
			this.$lightbox.detach();
			$( 'body' ).append( this.$lightbox );

			this.$lightbox.css( 'display', 'flex' ).attr( 'aria-hidden', 'false' );
			$( 'body' ).addClass( 'gfolio-lightbox-open' );
			this.$lightbox.find( '.gfolio-lightbox-close' ).focus();

			/*
			 * Prevent body scroll.
			 * Compensate for the scrollbar width so the page content does not
			 * shift when the scrollbar disappears (the "jump" the user sees).
			 */
			var scrollbarW = window.innerWidth - document.documentElement.clientWidth;
			$( 'body' ).css( { overflow: 'hidden', paddingRight: scrollbarW + 'px' } );
		},

		closeLightbox: function () {
			this.$lightbox.find( '.gfolio-lightbox-container' )
				.css( 'animation', 'gfolio-lb-out 0.2s ease both' );

			var self = this;
			setTimeout( function () {
				self.$lightbox.css( 'display', 'none' ).attr( 'aria-hidden', 'true' );
				self.$lightbox.find( '.gfolio-lightbox-body' ).empty();
				self.$lightbox.find( '.gfolio-lightbox-container' ).css( 'animation', '' );
				$( 'body' )
					.removeClass( 'gfolio-lightbox-open' )
					.css( { overflow: '', paddingRight: '' } );

				// Move the lightbox back into the portfolio wrap so it is
				// properly scoped the next time it is opened.
				self.$wrap.append( self.$lightbox );
			}, 200 );
		},

		/* -------------------------------------------------------
		   HTML BUILDERS
		   ------------------------------------------------------- */
		buildExpandHTML: function ( $data ) {
			var html      = '';
			var title     = $data.data( 'title' ) || '';
			var sub       = $data.data( 'subheading' ) || '';
			var desc      = $data.data( 'description' ) || '';
			var showTitle = $data.data( 'show-title' ) !== '0';
			var showSub   = $data.data( 'show-sub' ) !== '0';
			var showDesc  = $data.data( 'show-desc' ) !== '0';
			var richContent = $data.find( '.gfolio-rich-content' ).html() || '';

			if ( showTitle && title ) {
				html += '<h2 class="gfolio-expand-title">' + this.escHtml( title ) + '</h2>';
			}
			if ( showSub && sub ) {
				html += '<p class="gfolio-expand-sub">' + this.escHtml( sub ) + '</p>';
			}
			if ( showDesc && desc ) {
				html += '<p class="gfolio-expand-desc">' + this.escHtml( desc ) + '</p>';
			}
			if ( richContent ) {
				html += '<div class="gfolio-expand-content">' + richContent + '</div>';
			}

			html += this.buildButtonHTML( $data );

			return html;
		},

		buildLightboxHTML: function ( $data ) {
			var html  = '';
			var title = $data.data( 'title' ) || '';
			var sub   = $data.data( 'subheading' ) || '';
			var desc  = $data.data( 'description' ) || '';
			var richContent = $data.find( '.gfolio-rich-content' ).html() || '';

			if ( title ) {
				html += '<h2 class="gfolio-lb-title">' + this.escHtml( title ) + '</h2>';
			}
			if ( sub ) {
				html += '<p class="gfolio-lb-sub">' + this.escHtml( sub ) + '</p>';
			}
			if ( desc ) {
				html += '<p class="gfolio-lb-desc">' + this.escHtml( desc ) + '</p>';
			}
			if ( richContent ) {
				html += '<div class="gfolio-lb-content">' + richContent + '</div>';
			}

			html += this.buildButtonHTML( $data );

			return html;
		},

		buildButtonHTML: function ( $data ) {
			var btnEnabled = $data.data( 'btn-enabled' );
			if ( ! btnEnabled || btnEnabled === '0' || btnEnabled === 0 ) return '';

			var label  = $data.data( 'btn-label' ) || 'View Project';
			var url    = $data.data( 'btn-url' ) || '';
			var blank  = $data.data( 'btn-blank' );
			var style  = $data.data( 'btn-style' ) || 'filled';
			var align  = ( gfolioPublic && gfolioPublic.btnAlign ) ? gfolioPublic.btnAlign : 'left';

			if ( ! url ) return '';

			var target = ( blank && blank !== '0' ) ? '_blank' : '_self';
			var rel    = target === '_blank' ? ' rel="noopener noreferrer"' : '';
			var content = this.escHtml( label );
			if ( style === 'ghost' ) {
				content += ' <span class="gfolio-ghost-arrow">&#8594;</span>';
			}

			return '<div class="gfolio-btn-wrap gfolio-btn-align-' + this.escAttr( align ) + '">' +
				'<a href="' + this.escUrl( url ) + '" class="gfolio-project-btn gfolio-btn-' + this.escAttr( style ) + '" target="' + this.escAttr( target ) + '"' + rel + '>' + content + '</a>' +
				'</div>';
		},

		/* -------------------------------------------------------
		   ESCAPE HELPERS
		   ------------------------------------------------------- */
		escHtml: function ( str ) {
			return String( str )
				.replace( /&/g,  '&amp;' )
				.replace( /</g,  '&lt;' )
				.replace( />/g,  '&gt;' )
				.replace( /"/g,  '&quot;' )
				.replace( /'/g,  '&#039;' );
		},

		escAttr: function ( str ) {
			return this.escHtml( str );
		},

		escUrl: function ( url ) {
			// Basic URL validation
			if ( /^(https?:\/\/|\/|#|mailto:|tel:)/.test( url ) ) {
				return url;
			}
			return '';
		},
	};

} )( jQuery );
