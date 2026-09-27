/**
 * MindCrafts AI admin settings scripts.
 *
 * Implements interactive live search, badge filters, category accordions,
 * instant clipboard copy with animated feedback, dynamic metric recalculation,
 * and AJAX handlers for license and audit logs.
 *
 * @package MindCrafts_AI
 * @since   2.1.0
 */

(function () {
	'use strict';

	/**
	 * Copy text to clipboard with modern API and fallback.
	 */
	function copyToClipboard( text ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			return navigator.clipboard.writeText( text );
		}

		return new Promise( function ( resolve, reject ) {
			try {
				var textarea = document.createElement( 'textarea' );
				textarea.value = text;
				textarea.style.position = 'fixed';
				textarea.style.opacity = '0';
				textarea.style.left = '-9999px';
				document.body.appendChild( textarea );
				textarea.focus();
				textarea.select();
				var successful = document.execCommand( 'copy' );
				document.body.removeChild( textarea );
				if ( successful ) {
					resolve();
				} else {
					reject( new Error( 'Copy command unsuccessful' ) );
				}
			} catch ( err ) {
				reject( err );
			}
		} );
	}

	/**
	 * Show temporary feedback on a button after copying.
	 */
	function setCopiedState( button ) {
		var originalHtml = button.innerHTML;
		var copiedMsg = ( typeof mindcraftsAiAdmin !== 'undefined' && mindcraftsAiAdmin.copied ) ? mindcraftsAiAdmin.copied : 'Copied!';

		button.innerHTML = '<span class="dashicons dashicons-yes" style="font-size: 14px; width: 14px; height: 14px; color: #10B981;"></span> ' + copiedMsg;
		button.style.pointerEvents = 'none';

		setTimeout( function () {
			button.innerHTML = originalHtml;
			button.style.pointerEvents = '';
		}, 1800 );
	}

	/**
	 * Global Copy Buttons Handler
	 */
	function initCopyActions() {
		// Generic data-copy-text buttons
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '[data-copy-text]' );
			if ( btn ) {
				e.preventDefault();
				var text = btn.getAttribute( 'data-copy-text' );
				if ( text ) {
					copyToClipboard( text ).then( function () {
						setCopiedState( btn );
					} );
				}
				return;
			}

			// Target-based copy buttons (e.g. config JSON)
			var targetBtn = e.target.closest( '[data-copy-target]' );
			if ( targetBtn ) {
				e.preventDefault();
				var targetId = targetBtn.getAttribute( 'data-copy-target' );
				var sourceEl = document.getElementById( targetId );
				if ( sourceEl ) {
					var val = sourceEl.value || sourceEl.innerText;
					copyToClipboard( val ).then( function () {
						setCopiedState( targetBtn );
					} );
				}
			}
		} );
	}

	/**
	 * Initialize Tools Form (Live Search, Filter Chips, Accordions, Toggles)
	 */
	function initToolsForm() {
		var form = document.getElementById( 'mindcrafts-ai-tools-form' );
		if ( ! form ) {
			return;
		}

		var searchInput = document.getElementById( 'mindcrafts-ai-tool-search' );
		var filterChips = document.querySelectorAll( '.mindcrafts-ai-chip' );
		var activeFilter = 'all';

		var enableAll = form.querySelector( '.mindcrafts-ai-enable-all' );
		var disableAll = form.querySelector( '.mindcrafts-ai-disable-all' );

		// Live Stats counter elements
		var statEnabledEl = document.getElementById( 'mindcrafts-ai-stat-enabled' );

		function updateMetrics() {
			var allCheckboxes = form.querySelectorAll( 'input[type="checkbox"][data-tool-id]' );
			var total = allCheckboxes.length;
			var enabled = 0;

			allCheckboxes.forEach( function ( cb ) {
				if ( cb.checked ) {
					enabled++;
				}
			} );

			if ( statEnabledEl ) {
				statEnabledEl.textContent = enabled;
			}

			// Update per-category counts
			form.querySelectorAll( '.mindcrafts-ai-category' ).forEach( function ( cat ) {
				var catTotal = cat.querySelectorAll( 'input[type="checkbox"]' ).length;
				var catActive = cat.querySelectorAll( 'input[type="checkbox"]:checked' ).length;
				var countEl = cat.querySelector( '.mindcrafts-ai-category-count' );
				if ( countEl ) {
					countEl.textContent = catActive + ' / ' + catTotal + ' active';
				}
			} );
		}

		function filterCards() {
			var query = searchInput ? searchInput.value.toLowerCase().trim() : '';

			form.querySelectorAll( '.mindcrafts-ai-category' ).forEach( function ( cat ) {
				var cards = cat.querySelectorAll( '.mindcrafts-ai-tool-card' );
				var visibleCount = 0;

				cards.forEach( function ( card ) {
					var slug = ( card.getAttribute( 'data-slug' ) || '' ).toLowerCase();
					var badges = ( card.getAttribute( 'data-badges' ) || '' ).toLowerCase();
					var name = ( card.querySelector( '.mindcrafts-ai-tool-name' ) ? card.querySelector( '.mindcrafts-ai-tool-name' ).innerText : '' ).toLowerCase();
					var desc = ( card.querySelector( '.mindcrafts-ai-tool-desc' ) ? card.querySelector( '.mindcrafts-ai-tool-desc' ).innerText : '' ).toLowerCase();

					var matchesQuery = ! query || slug.indexOf( query ) !== -1 || name.indexOf( query ) !== -1 || desc.indexOf( query ) !== -1;
					var matchesFilter = true;

					if ( activeFilter !== 'all' ) {
						if ( activeFilter === 'pro' ) {
							matchesFilter = badges.indexOf( 'pro' ) !== -1;
						} else if ( activeFilter === 'woo' ) {
							matchesFilter = badges.indexOf( 'woo' ) !== -1 || card.getAttribute( 'data-category' ) === 'woocommerce';
						} else if ( activeFilter === 'read-only' ) {
							matchesFilter = badges.indexOf( 'read-only' ) !== -1;
						} else if ( activeFilter === 'destructive' ) {
							matchesFilter = badges.indexOf( 'destructive' ) !== -1;
						}
					}

					if ( matchesQuery && matchesFilter ) {
						card.style.display = '';
						visibleCount++;
					} else {
						card.style.display = 'none';
					}
				} );

				// Hide empty categories when searching or filtering
				if ( visibleCount === 0 ) {
					cat.style.display = 'none';
				} else {
					cat.style.display = '';
				}
			} );
		}

		// Search event
		if ( searchInput ) {
			searchInput.addEventListener( 'input', filterCards );
		}

		// Filter chips
		filterChips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				filterChips.forEach( function ( c ) { c.classList.remove( 'is-active' ); } );
				chip.classList.add( 'is-active' );
				activeFilter = chip.getAttribute( 'data-filter' ) || 'all';
				filterCards();
			} );
		} );

		// Category Accordion Toggles
		form.querySelectorAll( '.mindcrafts-ai-category' ).forEach( function ( category ) {
			var header = category.querySelector( '.mindcrafts-ai-category-header' );
			if ( header ) {
				header.addEventListener( 'click', function ( e ) {
					// Don't toggle if clicking an action button inside header
					if ( e.target.closest( '.mindcrafts-ai-cat-btn' ) ) {
						return;
					}
					category.classList.toggle( 'is-collapsed' );
				} );
			}

			var catEnable = category.querySelector( '.mindcrafts-ai-cat-enable-all' );
			var catDisable = category.querySelector( '.mindcrafts-ai-cat-disable-all' );

			if ( catEnable ) {
				catEnable.addEventListener( 'click', function ( e ) {
					e.stopPropagation();
					category.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( cb ) {
						cb.checked = true;
					} );
					updateCards( form );
					updateEnabledIds( form );
					updateMetrics();
				} );
			}

			if ( catDisable ) {
				catDisable.addEventListener( 'click', function ( e ) {
					e.stopPropagation();
					category.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( cb ) {
						cb.checked = false;
					} );
					updateCards( form );
					updateEnabledIds( form );
					updateMetrics();
				} );
			}
		} );

		// Bulk Enable All / Disable All
		if ( enableAll ) {
			enableAll.addEventListener( 'click', function () {
				form.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( cb ) {
					cb.checked = true;
				} );
				updateCards( form );
				updateEnabledIds( form );
				updateMetrics();
			} );
		}

		if ( disableAll ) {
			disableAll.addEventListener( 'click', function () {
				form.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( cb ) {
					cb.checked = false;
				} );
				updateCards( form );
				updateEnabledIds( form );
				updateMetrics();
			} );
		}

		// Individual checkbox toggle
		form.addEventListener( 'change', function ( event ) {
			if ( event.target.type === 'checkbox' ) {
				updateCards( form );
				updateEnabledIds( form );
				updateMetrics();
			}
		} );

		form.addEventListener( 'submit', function () {
			updateEnabledIds( form );
		} );
	}

	function updateCards( form ) {
		form.querySelectorAll( '.mindcrafts-ai-tool-card' ).forEach( function ( card ) {
			var checkbox = card.querySelector( 'input[type="checkbox"]' );
			if ( checkbox ) {
				card.classList.toggle( 'is-enabled', checkbox.checked );
				card.classList.toggle( 'is-disabled', ! checkbox.checked );
			}
		} );
	}

	function updateEnabledIds( form ) {
		var target = document.getElementById( 'mindcrafts-ai-enabled-ids' );
		var enabledIds = [];

		if ( ! target ) {
			return;
		}

		form.querySelectorAll( 'input[type="checkbox"][data-tool-id]' ).forEach( function ( checkbox ) {
			if ( checkbox.checked ) {
				var id = checkbox.getAttribute( 'data-tool-id' );
				if ( id ) {
					enabledIds.push( id );
				}
			}
		} );

		target.value = enabledIds.join( ',' );
	}

	/**
	 * Initialize License Tab Handlers
	 */
	function initLicenseHandlers() {
		var keyInput = document.getElementById( 'mindcrafts_ai_license_key' );
		var toggleBtn = document.getElementById( 'mindcrafts-ai-toggle-key' );
		var activateBtn = document.getElementById( 'mindcrafts-ai-activate-btn' );
		var deactivateBtn = document.getElementById( 'mindcrafts-ai-deactivate-btn' );
		var feedback = document.getElementById( 'mindcrafts-ai-license-feedback' );

		if ( toggleBtn && keyInput ) {
			toggleBtn.addEventListener( 'click', function () {
				keyInput.type = ( keyInput.type === 'password' ) ? 'text' : 'password';
				var icon = toggleBtn.querySelector( '.dashicons' );
				if ( icon ) {
					icon.classList.toggle( 'dashicons-visibility' );
					icon.classList.toggle( 'dashicons-hidden' );
				}
			} );
		}

		function showFeedback( msg, isSuccess ) {
			if ( ! feedback ) return;
			feedback.style.display = 'flex';
			feedback.className = 'mindcrafts-ai-alert ' + ( isSuccess ? 'mindcrafts-ai-alert--success' : 'mindcrafts-ai-alert--error' );
			feedback.innerHTML = '<span class="dashicons ' + ( isSuccess ? 'dashicons-yes-alt' : 'dashicons-warning' ) + '"></span> <span>' + msg + '</span>';
		}

		if ( activateBtn ) {
			activateBtn.addEventListener( 'click', function () {
				if ( typeof mindcraftsAiAdmin === 'undefined' || ! keyInput ) return;
				var key = keyInput.value.trim();
				if ( ! key ) {
					showFeedback( 'Please enter a valid license key.', false );
					return;
				}

				activateBtn.disabled = true;
				var originalHtml = activateBtn.innerHTML;
				activateBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Activating...';

				var formData = new FormData();
				formData.append( 'action', 'mindcrafts_ai_activate_license' );
				formData.append( 'nonce', mindcraftsAiAdmin.nonce );
				formData.append( 'license_key', key );

				fetch( mindcraftsAiAdmin.ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					activateBtn.disabled = false;
					activateBtn.innerHTML = originalHtml;
					if ( data.success ) {
						showFeedback( ( data.data && data.data.message ) ? data.data.message : 'License successfully activated!', true );
						setTimeout( function () { window.location.reload(); }, 1200 );
					} else {
						showFeedback( ( data.data && data.data.message ) ? data.data.message : 'License activation failed.', false );
					}
				} )
				.catch( function () {
					activateBtn.disabled = false;
					activateBtn.innerHTML = originalHtml;
					showFeedback( mindcraftsAiAdmin.error || 'A network error occurred. Please try again.', false );
				} );
			} );
		}

		if ( deactivateBtn ) {
			deactivateBtn.addEventListener( 'click', function () {
				if ( typeof mindcraftsAiAdmin === 'undefined' ) return;
				deactivateBtn.disabled = true;
				var originalHtml = deactivateBtn.innerHTML;
				deactivateBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Deactivating...';

				var formData = new FormData();
				formData.append( 'action', 'mindcrafts_ai_deactivate_license' );
				formData.append( 'nonce', mindcraftsAiAdmin.nonce );

				fetch( mindcraftsAiAdmin.ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					deactivateBtn.disabled = false;
					deactivateBtn.innerHTML = originalHtml;
					if ( data.success ) {
						showFeedback( ( data.data && data.data.message ) ? data.data.message : 'License deactivated.', true );
						setTimeout( function () { window.location.reload(); }, 1200 );
					} else {
						showFeedback( 'Deactivation failed.', false );
					}
				} )
				.catch( function () {
					deactivateBtn.disabled = false;
					deactivateBtn.innerHTML = originalHtml;
					showFeedback( mindcraftsAiAdmin.error || 'A network error occurred. Please try again.', false );
				} );
			} );
		}
	}

	/**
	 * Initialize Audit Logs Handlers
	 */
	function initLogsHandlers() {
		var clearBtn = document.getElementById( 'mindcrafts-ai-clear-logs-btn' );
		var tbody = document.getElementById( 'mindcrafts-ai-logs-tbody' );
		var feedback = document.getElementById( 'mindcrafts-ai-logs-feedback' );

		if ( ! clearBtn ) return;

		clearBtn.addEventListener( 'click', function () {
			if ( typeof mindcraftsAiAdmin === 'undefined' ) return;
			var confirmMsg = mindcraftsAiAdmin.confirmClear || 'Are you sure you want to clear all audit logs?';
			if ( ! window.confirm( confirmMsg ) ) return;

			clearBtn.disabled = true;
			var origHtml = clearBtn.innerHTML;
			clearBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> ' + ( mindcraftsAiAdmin.clearing || 'Clearing...' );

			var formData = new FormData();
			formData.append( 'action', 'mindcrafts_ai_clear_logs' );
			formData.append( 'nonce', mindcraftsAiAdmin.nonce );

			fetch( mindcraftsAiAdmin.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( data ) {
				clearBtn.disabled = false;
				clearBtn.innerHTML = origHtml;
				if ( data.success ) {
					if ( feedback ) {
						feedback.style.display = 'flex';
						feedback.innerHTML = '<span class="dashicons dashicons-yes-alt"></span> <span>' + ( mindcraftsAiAdmin.cleared || 'Logs cleared successfully.' ) + '</span>';
					}
					if ( tbody ) {
						tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--mc-text-muted); padding: 30px; font-style: italic;">No logs recorded yet.</td></tr>';
					}
				}
			} )
			.catch( function () {
				clearBtn.disabled = false;
				clearBtn.innerHTML = origHtml;
			} );
		} );
	}

	/**
	 * One-click migration of all 6 pages to native Elementor widgets.
	 */
	function initMigrationHandler() {
		var migrateBtn = document.getElementById( 'mindcrafts-ai-migrate-pages-btn' );
		var resultsDiv = document.getElementById( 'mindcrafts-ai-migration-results' );

		if ( ! migrateBtn ) {
			return;
		}

		migrateBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();

			if ( ! confirm( 'Are you sure you want to convert all 6 pages (Home, Services, Projects, About, Pricing, Contact) to native Elementor widgets? This will unpack raw HTML into editable Elementor containers, headings, buttons, and images.' ) ) {
				return;
			}

			var origHtml = migrateBtn.innerHTML;
			migrateBtn.disabled = true;
			migrateBtn.innerHTML = '<span class="dashicons dashicons-update mindcrafts-ai-spin" style="margin-top:4px;"></span> Converting pages to native Elementor...';

			if ( resultsDiv ) {
				resultsDiv.style.display = 'block';
				resultsDiv.innerHTML = '<p style="margin: 0; color: #4F46E5;"><strong>Scanning and converting pages into native Elementor visual widgets... Please wait.</strong></p>';
			}

			var formData = new FormData();
			formData.append( 'action', 'mindcrafts_ai_migrate_pages' );
			formData.append( 'nonce', ( typeof mindcraftsAiAdmin !== 'undefined' ? mindcraftsAiAdmin.nonce : '' ) );

			fetch( mindcraftsAiAdmin.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( data ) {
				migrateBtn.disabled = false;
				migrateBtn.innerHTML = origHtml;

				if ( ! resultsDiv ) {
					return;
				}

				if ( data.success && data.data ) {
					var res = data.data;
					var html = '<div style="color: #10B981; font-weight: 700; font-size: 15px; margin-bottom: 10px;">✅ Migration Completed! Successfully converted ' + ( res.pages_migrated || 0 ) + ' pages into native Elementor visual widgets.</div>';
					html += '<ul style="margin: 0; padding-left: 20px;">';

					if ( Array.isArray( res.results ) ) {
						res.results.forEach( function ( item ) {
							if ( item.status === 'migrated_to_native' ) {
								html += '<li style="margin-bottom: 8px;"><strong>' + item.title + '</strong> (/' + item.slug + '/): Created <strong>' + item.sections_created + '</strong> native Elementor sections. <a href="' + item.edit_url + '" target="_blank" class="button button-small button-primary" style="margin-left: 8px;">Edit in Elementor &rarr;</a> <a href="' + item.preview_url + '" target="_blank" class="button button-small">View Page</a></li>';
							} else {
								html += '<li style="margin-bottom: 8px; color: #64748b;"><strong>' + ( item.title || item.slug ) + '</strong>: ' + ( item.reason || item.message || item.status ) + '</li>';
							}
						} );
					}
					html += '</ul>';
					resultsDiv.innerHTML = html;
				} else {
					resultsDiv.innerHTML = '<div style="color: #ef4444; font-weight: 700;">❌ Migration Error: ' + ( ( data.data && data.data.message ) ? data.data.message : 'Unknown error' ) + '</div>';
				}
			} )
			.catch( function ( err ) {
				migrateBtn.disabled = false;
				migrateBtn.innerHTML = origHtml;
				if ( resultsDiv ) {
					resultsDiv.innerHTML = '<div style="color: #ef4444; font-weight: 700;">❌ Network Error: ' + err.message + '</div>';
				}
			} );
		} );
	}

	// DOM Ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initCopyActions();
			initToolsForm();
			initLicenseHandlers();
			initLogsHandlers();
			initMigrationHandler();
		} );
	} else {
		initCopyActions();
		initToolsForm();
		initLicenseHandlers();
		initLogsHandlers();
		initMigrationHandler();
	}
})();
