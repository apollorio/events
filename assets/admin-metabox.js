/**
 * Apollo Events Manager - Admin Metabox JavaScript
 * Dynamic timetable & add DJ/Local dialogs
 */

(function ($) {
	'use strict';

	// Store current timetable data
	let timetableData = [];

	// Initialize
	$( document ).ready(
		function () {
			initDialogs();
			initTimetable();
			initEnhancedSelects();
			initImageUploads();
			bindEvents();

			// Load existing timetable on page load
			loadExistingTimetable();

			// Initialize Motion.dev animations if available
			if (typeof window.motion !== 'undefined' || typeof window.Motion !== 'undefined') {
				initMotionAnimations();
			}
		}
	);

	/**
	 * Initialize jQuery UI Dialogs
	 */
	function initDialogs() {
		// DJ Dialog
		$( '#apollo_add_dj_dialog' ).dialog(
			{
				autoOpen: false,
				modal: true,
				width: 500,
				buttons: [
				{
					text: 'Adicionar',
					class: 'button button-primary',
					click: function () {
						submitNewDJ();
					}
				},
				{
					text: 'Cancelar',
					class: 'button',
					click: function () {
						$( this ).dialog( 'close' );
						clearDJForm();
					}
				}
				]
			}
		);

		// Local Dialog
		$( '#apollo_add_local_dialog' ).dialog(
			{
				autoOpen: false,
				modal: true,
				width: 500,
				buttons: [
				{
					text: 'Adicionar',
					class: 'button button-primary',
					click: function () {
						submitNewLocal();
					}
				},
				{
					text: 'Cancelar',
					class: 'button',
					click: function () {
						$( this ).dialog( 'close' );
						clearLocalForm();
					}
				}
				]
			}
		);
	}

	/**
	 * Bind events
	 */
	function bindEvents() {
		// Open DJ dialog
		$( '#apollo_add_new_dj' ).on(
			'click',
			function (e) {
				e.preventDefault();
				$( '#apollo_add_dj_dialog' ).dialog( 'open' );
			}
		);

		// Open Local dialog
		$( '#apollo_add_new_local' ).on(
			'click',
			function (e) {
				e.preventDefault();
				$( '#apollo_add_local_dialog' ).dialog( 'open' );
			}
		);

		// DJ select change -> rebuild timetable
		$( '#apollo_event_djs' ).on(
			'change',
			function () {
				rebuildTimetable();
			}
		);

		// Refresh timetable button
		$( '#apollo_refresh_timetable' ).on(
			'click',
			function (e) {
				e.preventDefault();
				rebuildTimetable();
			}
		);

		// Before form submit -> save timetable JSON and sync selects
		$( 'form#post' ).on(
			'submit',
			function () {
				saveTimetableToHidden();
				syncAllSelects();
			}
		);
	}

	/**
	 * Submit new DJ via AJAX
	 */
	function submitNewDJ() {
		const name = $( '#new_dj_name' ).val().trim();
		const $msg = $( '#apollo_dj_form_message' );

		if ( ! name) {
			showMessage( $msg, apolloAdmin.i18n.enter_name, 'error' );
			return;
		}

		// Show loading
		showMessage( $msg, 'Verificando...', 'info' );

		$.ajax(
			{
				url: apolloAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'apollo_add_new_dj',
					nonce: apolloAdmin.nonce,
					name: name
				},
				success: function (response) {
					if (response.success) {
						// Add to select
						const option = $(
							'<option>',
							{
								value: response.data.id,
								text: response.data.name,
								selected: true
							}
						);
						$( '#apollo_event_djs' ).append( option );

						// Close dialog
						$( '#apollo_add_dj_dialog' ).dialog( 'close' );
						clearDJForm();

						// Rebuild timetable
						rebuildTimetable();

						// Success message
						alert( 'DJ ' + response.data.name + ' adicionado com sucesso!' );
					} else {
						showMessage( $msg, response.data, 'error' );
					}
				},
				error: function () {
					showMessage( $msg, 'Erro de conexão', 'error' );
				}
			}
		);
	}

	/**
	 * Submit new Local via AJAX
	 */
	function submitNewLocal() {
		const name    = $( '#new_local_name' ).val().trim();
		const address = $( '#new_local_address' ).val().trim();
		const city    = $( '#new_local_city' ).val().trim();
		const $msg    = $( '#apollo_local_form_message' );

		if ( ! name) {
			showMessage( $msg, apolloAdmin.i18n.enter_name, 'error' );
			return;
		}

		showMessage( $msg, 'Verificando...', 'info' );

		$.ajax(
			{
				url: apolloAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'apollo_add_new_local',
					nonce: apolloAdmin.nonce,
					name: name,
					address: address,
					city: city
				},
				success: function (response) {
					if (response.success) {
						// Add to select
						const option = $(
							'<option>',
							{
								value: response.data.id,
								text: response.data.name,
								selected: true
							}
						);
						$( '#apollo_event_local' ).append( option );

						// Close dialog
						$( '#apollo_add_local_dialog' ).dialog( 'close' );
						clearLocalForm();

						// Success message
						alert(
							'Local ' + response.data.name + ' adicionado com sucesso!\n' +
								(city ? 'Geocoding será feito automaticamente.' : '')
						);
					} else {
						showMessage( $msg, response.data, 'error' );
					}
				},
				error: function () {
					showMessage( $msg, 'Erro de conexão', 'error' );
				}
			}
		);
	}

	/**
	 * Initialize timetable
	 */
	function initTimetable() {
		// Delegate events for dynamic rows
		$( '#apollo_timetable_rows' ).on(
			'change',
			'input[type="time"]',
			function () {
				updateTimetableData();
			}
		);

		// Delegate events for order controls
		$( '#apollo_timetable_rows' ).on(
			'click',
			'.apollo-move-up',
			function (e) {
				e.preventDefault();
				moveRowUp( $( this ).closest( 'tr' ) );
			}
		);

		$( '#apollo_timetable_rows' ).on(
			'click',
			'.apollo-move-down',
			function (e) {
				e.preventDefault();
				moveRowDown( $( this ).closest( 'tr' ) );
			}
		);
	}

	/**
	 * Load existing timetable from PHP
	 */
	function loadExistingTimetable() {
		// Check if there's existing data in hidden field
		const existingJSON = $( '#apollo_event_timetable' ).val();
		if (existingJSON) {
			try {
				timetableData = JSON.parse( existingJSON );
			} catch (e) {
				timetableData = [];
			}
		}

		if ( ! Array.isArray( timetableData )) {
			timetableData = [];
		}

		// Rebuild with existing data
		rebuildTimetable();
	}

	/**
	 * Rebuild timetable rows based on selected DJs
	 * Respects custom order if saved, otherwise uses selected order
	 */
	function rebuildTimetable() {
		const selectedDJs = $( '#apollo_event_djs' ).val() || [];
		const $rows       = $( '#apollo_timetable_rows' );
		const $table      = $( '#apollo_timetable_table' );
		const $empty      = $( '#apollo_timetable_empty' );

		$rows.empty();

		if (selectedDJs.length === 0) {
			$table.hide();
			$empty.show();
			timetableData = [];
			return;
		}

		$table.show();
		$empty.hide();

		// Get saved order from timetable data (if exists)
		const savedOrder = [];
		if (Array.isArray( timetableData ) && timetableData.length > 0) {
			timetableData.forEach(
				function (item) {
					if (item.dj && item.order !== undefined) {
						savedOrder.push( { dj: parseInt( item.dj, 10 ), order: parseInt( item.order, 10 ) } );
					}
				}
			);
			// Sort by saved order
			savedOrder.sort(
				function (a, b) {
					return a.order - b.order; }
			);
		}

		// Build ordered DJ list: use saved order if exists, otherwise use selected order
		let orderedDJs = [];
		if (savedOrder.length > 0) {
			// Use saved order, but only include DJs that are still selected
			savedOrder.forEach(
				function (item) {
					if (selectedDJs.indexOf( String( item.dj ) ) !== -1) {
						orderedDJs.push( String( item.dj ) );
					}
				}
			);
			// Add any new DJs that weren't in saved order (append at end)
			selectedDJs.forEach(
				function (djID) {
					if (orderedDJs.indexOf( String( djID ) ) === -1) {
						orderedDJs.push( String( djID ) );
					}
				}
			);
		} else {
			// No saved order, use selected order
			orderedDJs = selectedDJs.map( String );
		}

		// Create row for each DJ in order
		orderedDJs.forEach(
			function (djID, index) {
				const $option = $( '#apollo_event_djs option[value="' + djID + '"]' );
				const djName  = $option.text();

				// Find existing time data
				let existingStart = '';
				let existingEnd   = '';
				let existingOrder = index + 1;
				const existing    = timetableData.find( item => item.dj == djID );
				if (existing) {
					existingStart = existing.from || existing.start || '';
					existingEnd   = existing.to || existing.end || '';
					existingOrder = existing.order !== undefined ? parseInt( existing.order, 10 ) : index + 1;
				}

				const row = $(
					'<tr>',
					{
						'data-dj-id': djID,
						'data-order': existingOrder,
						'class': 'apollo-timetable-row'
					}
				);

				// Column 1: Order number (#1, #2, #3)
				row.append(
					$( '<td>' ).html( '<span class="apollo-order-number">#' + existingOrder + '</span>' )
				);

				// Column 2: Drag handle and arrows
				const orderControls = $( '<div>', { 'class': 'apollo-order-controls' } );
				orderControls.append(
					$(
						'<span>',
						{
							'class': 'apollo-drag-handle dashicons dashicons-menu',
							'title': 'Arrastar para reordenar'
						}
					)
				);
				orderControls.append(
					$(
						'<button>',
						{
							'type': 'button',
							'class': 'apollo-move-up button button-small',
							'title': 'Mover para cima',
							'html': '<span class="dashicons dashicons-arrow-up-alt"></span>'
						}
					)
				);
				orderControls.append(
					$(
						'<button>',
						{
							'type': 'button',
							'class': 'apollo-move-down button button-small',
							'title': 'Mover para baixo',
							'html': '<span class="dashicons dashicons-arrow-down-alt"></span>'
						}
					)
				);
				row.append( $( '<td>' ).html( orderControls ) );

				// Column 3: DJ Name
				row.append(
					$( '<td>' ).html( '<strong>' + djName + '</strong>' )
				);

				// Column 4: Start time
				row.append(
					$( '<td>' ).html(
						'<input type="time" class="timetable-start" data-dj-id="' + djID + '" value="' + existingStart + '">'
					)
				);

				// Column 5: End time
				row.append(
					$( '<td>' ).html(
						'<input type="time" class="timetable-end" data-dj-id="' + djID + '" value="' + existingEnd + '">'
					)
				);

				// Column 6: Actions (empty for now, can add remove button later)
				row.append( $( '<td>' ) );

				$rows.append( row );
			}
		);

		// Initialize Sortable.js for drag and drop
		initTimetableSortable();

		// Update order numbers
		updateOrderNumbers();

		// Update data
		updateTimetableData();
	}

	/**
	 * Initialize Sortable.js for timetable rows
	 */
	function initTimetableSortable() {
		// Destroy existing instance if any
		if ($( '#apollo_timetable_rows' ).data( 'sortable' )) {
			$( '#apollo_timetable_rows' ).sortable( 'destroy' );
		}

		// Check if Sortable.js is available (load from CDN if not)
		if (typeof Sortable === 'undefined') {
			// Load Sortable.js from CDN
			const script  = document.createElement( 'script' );
			script.src    = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
			script.onload = function () {
				createSortableInstance();
			};
			document.head.appendChild( script );
		} else {
			createSortableInstance();
		}

		function createSortableInstance() {
			const sortable = new Sortable(
				document.getElementById( 'apollo_timetable_rows' ),
				{
					handle: '.apollo-drag-handle',
					animation: 150,
					ghostClass: 'apollo-sortable-ghost',
					chosenClass: 'apollo-sortable-chosen',
					dragClass: 'apollo-sortable-drag',
					onEnd: function (evt) {
						updateOrderNumbers();
						updateTimetableData();
					}
				}
			);
			$( '#apollo_timetable_rows' ).data( 'sortable', sortable );
		}
	}

	/**
	 * Update order numbers (#1, #2, #3) based on current row positions
	 */
	function updateOrderNumbers() {
		$( '#apollo_timetable_rows tr' ).each(
			function (index) {
				const order = index + 1;
				$( this ).find( '.apollo-order-number' ).text( '#' + order );
				$( this ).attr( 'data-order', order );
			}
		);
	}

	/**
	 * Move row up
	 */
	function moveRowUp($row) {
		const $prev = $row.prev();
		if ($prev.length) {
			$row.insertBefore( $prev );
			updateOrderNumbers();
			updateTimetableData();
		}
	}

	/**
	 * Move row down
	 */
	function moveRowDown($row) {
		const $next = $row.next();
		if ($next.length) {
			$row.insertAfter( $next );
			updateOrderNumbers();
			updateTimetableData();
		}
	}

	/**
	 * Update timetable data from inputs
	 * Includes order information
	 */
	function updateTimetableData() {
		timetableData = [];

		$( '#apollo_timetable_rows tr' ).each(
			function (index) {
				const djID  = $( this ).data( 'dj-id' );
				const start = $( this ).find( '.timetable-start' ).val();
				const end   = $( this ).find( '.timetable-end' ).val();
				const order = index + 1; // Order based on position in table

				// ✅ ALWAYS add DJ to timetable, even without time
				// This ensures DJs are saved and displayed
				const entry = {
					dj: parseInt( djID, 10 ),
					order: order // Save custom order
				};

				if (start) {
					entry.from = start;
					entry.to   = end || start; // Fallback to start if no end
				}

				timetableData.push( entry );
			}
		);
	}

	/**
	 * Save timetable data to hidden field before submit
	 */
	function saveTimetableToHidden() {
		updateTimetableData();
		$( '#apollo_event_timetable' ).val( JSON.stringify( timetableData ) );
	}

	/**
	 * Show message in form
	 */
	function showMessage($el, msg, type) {
		const classes = {
			'error': 'notice notice-error',
			'success': 'notice notice-success',
			'info': 'notice notice-info'
		};

		$el.removeClass( 'notice-error notice-success notice-info' )
			.addClass( classes[type] || 'notice' )
			.html( '<p>' + msg + '</p>' )
			.show();
	}

	/**
	 * Clear DJ form
	 */
	function clearDJForm() {
		$( '#new_dj_name' ).val( '' );
		$( '#apollo_dj_form_message' ).hide();
	}

	/**
	 * Clear Local form
	 */
	function clearLocalForm() {
		$( '#new_local_name, #new_local_address, #new_local_city' ).val( '' );
		$( '#apollo_local_form_message' ).hide();
	}

	/**
	 * Sync all enhanced selects with hidden selects before submit
	 */
	function syncAllSelects() {
		// Sync DJ select
		const $djList   = $( '#apollo_dj_list' );
		const $djHidden = $( '#apollo_event_djs' );
		$djHidden.find( 'option' ).prop( 'selected', false );
		$djList.find( 'input[type="checkbox"]:checked' ).each(
			function () {
				const value = $( this ).val();
				$djHidden.find( 'option[value="' + value + '"]' ).prop( 'selected', true );
			}
		);

		// Sync Local select
		const $localList    = $( '#apollo_local_list' );
		const $localHidden  = $( '#apollo_event_local' );
		const selectedLocal = $localList.find( 'input[type="radio"]:checked' ).val() || '';
		$localHidden.val( selectedLocal );
	}

	/**
	 * Initialize Enhanced Select Components (ShadCN Style)
	 */
	function initEnhancedSelects() {
		// DJ Multi-Select
		initDJSelect();

		// Local Single-Select
		initLocalSelect();
	}

	/**
	 * Initialize DJ Multi-Select with Search
	 */
	function initDJSelect() {
		const $search       = $( '#apollo_dj_search' );
		const $list         = $( '#apollo_dj_list' );
		const $hiddenSelect = $( '#apollo_event_djs' );
		const $count        = $( '#apollo_dj_selected_count' );

		// Search filter - REAL TIME (on every keystroke)
		$search.on(
			'input keyup',
			function () {
				const query      = $( this ).val().toLowerCase().trim();
				let visibleCount = 0;

				$list.find( '.apollo-select-item' ).each(
					function () {
						const $item = $( this );
						const name  = ($item.data( 'dj-name' ) || '').toLowerCase();
						const djId  = $item.data( 'dj-id' );

						// Check if name contains query OR if item is selected (always show selected items)
						const matchesSearch = name.indexOf( query ) !== -1 || query === '';
						const isSelected    = $item.find( 'input[type="checkbox"]' ).is( ':checked' );

						if (matchesSearch || isSelected) {
							$item.show();
							visibleCount++;
						} else {
							$item.hide();
						}
					}
				);
			}
		);

		// Checkbox change - sync with hidden select
		$list.on(
			'change',
			'input[type="checkbox"]',
			function () {
				const $checkbox = $( this );
				const $item     = $checkbox.closest( '.apollo-select-item' );
				const value     = $checkbox.val();
				const isChecked = $checkbox.is( ':checked' );

				// Update visual state
				if (isChecked) {
					$item.addClass( 'selected' );
					if ( ! $item.find( '.apollo-check-icon' ).length) {
						$item.append( '<i class="ri-check-line apollo-check-icon"></i>' );
					}
				} else {
					$item.removeClass( 'selected' );
					$item.find( '.apollo-check-icon' ).remove();
				}

				// Sync with hidden select
				syncDJSelect();
				updateDJCount();
			}
		);

		// Update count on load AND after any change
		updateDJCount();

		// Also update count when hidden select changes (from PHP)
		$hiddenSelect.on(
			'change',
			function () {
				updateDJCount();
			}
		);

		function syncDJSelect() {
			$hiddenSelect.find( 'option' ).prop( 'selected', false );
			$list.find( 'input[type="checkbox"]:checked' ).each(
				function () {
					const value = $( this ).val();
					$hiddenSelect.find( 'option[value="' + value + '"]' ).prop( 'selected', true );
				}
			);
		}

		function updateDJCount() {
			const count = $list.find( 'input[type="checkbox"]:checked' ).length;
			const text  = count + ' selecionado' + (count !== 1 ? 's' : '');
			$count.text( text );

			// Also sync hidden select to ensure consistency
			syncDJSelect();
		}

		// Initial sync from hidden select to visual checkboxes (on page load)
		setTimeout(
			function () {
				$hiddenSelect.find( 'option:selected' ).each(
					function () {
						const value     = $( this ).val();
						const $checkbox = $list.find( 'input[type="checkbox"][value="' + value + '"]' );
						if ($checkbox.length && ! $checkbox.is( ':checked' )) {
							$checkbox.prop( 'checked', true );
							$checkbox.closest( '.apollo-select-item' ).addClass( 'selected' );
							if ( ! $checkbox.closest( '.apollo-select-item' ).find( '.apollo-check-icon' ).length) {
								$checkbox.closest( '.apollo-select-item' ).append( '<i class="ri-check-line apollo-check-icon"></i>' );
							}
						}
					}
				);
				updateDJCount();
			},
			100
		);
	}

	/**
	 * Initialize Local Single-Select with Search
	 */
	function initLocalSelect() {
		const $search       = $( '#apollo_local_search' );
		const $list         = $( '#apollo_local_list' );
		const $hiddenSelect = $( '#apollo_event_local' );

		// Search filter - REAL TIME (on every keystroke)
		$search.on(
			'input keyup',
			function () {
				const query      = $( this ).val().toLowerCase().trim();
				let visibleCount = 0;

				$list.find( '.apollo-select-item' ).each(
					function () {
						const $item   = $( this );
						const name    = ($item.data( 'local-name' ) || '').toLowerCase();
						const localId = $item.data( 'local-id' );

						// Check if name contains query OR if item is selected (always show selected items)
						const matchesSearch = name.indexOf( query ) !== -1 || query === '';
						const isSelected    = $item.find( 'input[type="radio"]' ).is( ':checked' );

						if (matchesSearch || isSelected) {
							$item.show();
							visibleCount++;
						} else {
							$item.hide();
						}
					}
				);
			}
		);

		// Radio change - sync with hidden select
		$list.on(
			'change',
			'input[type="radio"]',
			function () {
				const $radio = $( this );
				const $item  = $radio.closest( '.apollo-select-item' );
				const value  = $radio.val();

				// Update visual state
				$list.find( '.apollo-select-item' ).removeClass( 'selected' ).find( '.apollo-check-icon' ).remove();
				if (value !== '') {
					$item.addClass( 'selected' );
					if ( ! $item.find( '.apollo-check-icon' ).length) {
						$item.append( '<i class="ri-check-line apollo-check-icon"></i>' );
					}
				}

				// Sync with hidden select
				$hiddenSelect.val( value );
			}
		);

		// Initial sync from hidden select to visual radio (on page load)
		setTimeout(
			function () {
				const selectedValue = $hiddenSelect.val();
				if (selectedValue) {
					const $radio = $list.find( 'input[type="radio"][value="' + selectedValue + '"]' );
					if ($radio.length && ! $radio.is( ':checked' )) {
						$radio.prop( 'checked', true );
						$radio.closest( '.apollo-select-item' ).addClass( 'selected' );
						if ( ! $radio.closest( '.apollo-select-item' ).find( '.apollo-check-icon' ).length) {
							$radio.closest( '.apollo-select-item' ).append( '<i class="ri-check-line apollo-check-icon"></i>' );
						}
					}
				}
			},
			100
		);
	}

	/**
	 * Initialize Image Uploads
	 */
	function initImageUploads() {
		// Image upload buttons
		$( document ).on(
			'click',
			'.apollo-upload-image-btn',
			function (e) {
				e.preventDefault();
				const $btn        = $( this );
				const targetInput = $btn.data( 'target' ) || $btn.closest( '.apollo-image-input-row' ).find( 'input[type="url"]' );
				const $input      = typeof targetInput === 'string' ? $( '#' + targetInput ) : $( targetInput );

				// Use WordPress media uploader
				const frame = wp.media(
					{
						title: 'Selecionar Imagem',
						button: {
							text: 'Usar esta imagem'
						},
						multiple: false
					}
				);

				frame.on(
					'select',
					function () {
						const attachment = frame.state().get( 'selection' ).first().toJSON();
						$input.val( attachment.url );

						// Add preview button if not exists
						const $row = $input.closest( '.apollo-image-input-row, .apollo-field-controls' );
						if ( ! $row.find( '.apollo-preview-image-btn' ).length) {
							const $previewBtn = $(
								'<button>',
								{
									type: 'button',
									class: 'button apollo-preview-image-btn',
									html: '<span class="dashicons dashicons-visibility"></span>'
								}
							);
							$previewBtn.insertAfter( $btn );
						}
					}
				);

				frame.open();
			}
		);

		// Image preview
		$( document ).on(
			'click',
			'.apollo-preview-image-btn',
			function (e) {
				e.preventDefault();
				const url = $( this ).data( 'url' ) || $( this ).closest( '.apollo-image-input-row, .apollo-field-controls' ).find( 'input[type="url"]' ).val();
				if ( ! url) {
					return;
				}

				showImagePreview( url );
			}
		);
	}

	/**
	 * Show Image Preview Modal
	 */
	function showImagePreview(url) {
		let $modal = $( '#apollo-image-preview-modal' );
		if ( ! $modal.length) {
			$modal = $(
				'<div>',
				{
					id: 'apollo-image-preview-modal',
					class: 'apollo-image-preview-modal'
				}
			);
			$modal.html(
				'<div class="apollo-image-preview-content">' +
				'<button class="apollo-image-preview-close">&times;</button>' +
				'<img src="" alt="Preview">' +
				'</div>'
			);
			$( 'body' ).append( $modal );

			$modal.on(
				'click',
				function (e) {
					if ($( e.target ).is( '.apollo-image-preview-modal, .apollo-image-preview-close' )) {
						$modal.removeClass( 'active' );
						setTimeout(
							function () {
								$modal.remove(); },
							200
						);
					}
				}
			);
		}

		$modal.find( 'img' ).attr( 'src', url );
		$modal.addClass( 'active' );

		// Animate with Motion.dev if available
		if (typeof window.motion !== 'undefined' || typeof window.Motion !== 'undefined') {
			const motion = window.motion || window.Motion;
			if (motion && motion.animate) {
				motion.animate(
					$modal[0],
					{
						opacity: [0, 1]
					},
					{ duration: 0.2 }
				);
			}
		}
	}

	/**
	 * Initialize Motion.dev Animations
	 */
	function initMotionAnimations() {
		const motion = window.motion || window.Motion;
		if ( ! motion || ! motion.animate) {
			return;
		}

		// Animate field groups on load
		$( '[data-motion-group="true"]' ).each(
			function (index) {
				motion.animate(
					this,
					{
						opacity: [0, 1],
						transform: ['translateY(10px)', 'translateY(0px)']
					},
					{
						duration: 0.3,
						delay: index * 0.1,
						easing: 'ease-out'
					}
				);
			}
		);

		// Animate select components
		$( '[data-motion-select="true"]' ).each(
			function () {
				motion.animate(
					this,
					{
						opacity: [0, 1]
					},
					{
						duration: 0.2,
						easing: 'ease-out'
					}
				);
			}
		);

		// Animate inputs on focus
		$( '[data-motion-input="true"] input' ).on(
			'focus',
			function () {
				const $row = $( this ).closest( '[data-motion-input="true"]' );
				motion.animate(
					$row[0],
					{
						scale: [1, 1.01]
					},
					{
						duration: 0.2,
						easing: 'ease-out'
					}
				);
			}
		).on(
			'blur',
			function () {
				const $row = $( this ).closest( '[data-motion-input="true"]' );
				motion.animate(
					$row[0],
					{
						scale: [1.01, 1]
					},
					{
						duration: 0.2,
						easing: 'ease-out'
					}
				);
			}
		);
	}

})( jQuery );
