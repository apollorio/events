/* Admin JS for Apollo Dashboard
- Depends on jQuery, DataTables, Chart.js (enqueued by PHP)
- Lazy loads charts and provides UI glue
*/

(function ($) {
	$(
		function () {
			'use strict';

			// Tab switching
			$( '.apollo-tab-btn' ).on(
				'click',
				function () {
					var tabId = $( this ).data( 'tab' );
					$( '.apollo-tab-btn' ).removeClass( 'active' );
					$( '.apollo-tab' ).removeClass( 'active' );
					$( this ).addClass( 'active' );
					$( '#apollo-tab-' + tabId ).addClass( 'active' );

					// Lazy load charts when analytics tab is opened
					if (tabId === 'analytics' && typeof Chart !== 'undefined') {
						loadAnalyticsCharts();
					}
				}
			);

			// Initialize Events DataTable
			if ($.fn.DataTable && $( '#apollo-events-table' ).length) {
				$( '#apollo-events-table' ).DataTable(
					{
						ajax: {
							url: apolloDashboard.restUrl + 'analytics',
							dataSrc: function (json) {
								// Transform analytics data to events table format
								return [];
							}
						},
						columns: [
						{ data: 'id' },
						{ data: 'title' },
						{ data: 'date' },
						{ data: 'views' },
						{ data: 'likes' },
						{ data: 'actions', orderable: false }
						],
						pageLength: 25,
						order: [[0, 'desc']],
						language: {
							url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
						}
					}
				);
			}

			// Initialize Likes DataTable
			if ($.fn.DataTable && $( '#apollo-likes-table' ).length) {
				$( '#apollo-likes-table' ).DataTable(
					{
						ajax: {
							url: apolloDashboard.restUrl + 'likes',
							dataSrc: 'data'
						},
						columns: [
						{ data: 'event_title' },
						{ data: 'count' },
						{ data: 'actions', orderable: false }
						],
						pageLength: 25,
						order: [[1, 'desc']],
						language: {
							url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
						}
					}
				);
			}

			/**
			 * Lazy load analytics charts
			 */
			function loadAnalyticsCharts() {
				if (window.apolloChartsLoaded) {
					return; // Already loaded
				}

				$.ajax(
					{
						url: apolloDashboard.restUrl + 'analytics',
						method: 'GET',
						headers: {
							'X-WP-Nonce': apolloDashboard.nonce
						},
						success: function (data) {
							renderCharts( data );
							window.apolloChartsLoaded = true;
						}
					}
				);
			}

			/**
			 * Render Chart.js charts
			 */
			function renderCharts(data) {
				// Views over time chart
				var viewsCtx = document.getElementById( 'apollo-chart-views' );
				if (viewsCtx && typeof Chart !== 'undefined') {
					new Chart(
						viewsCtx,
						{
							type: 'line',
							data: {
								labels: [],
								datasets: [{
									label: 'Views',
									data: [],
									borderColor: 'rgb(75, 192, 192)',
									tension: 0.1
								}]
							},
							options: {
								responsive: true,
								maintainAspectRatio: false
							}
						}
					);
				}

				// Countries chart
				var countriesCtx = document.getElementById( 'apollo-chart-countries' );
				if (countriesCtx && typeof Chart !== 'undefined') {
					new Chart(
						countriesCtx,
						{
							type: 'doughnut',
							data: {
								labels: ['Brasil', 'Outros'],
								datasets: [{
									data: [80, 20],
									backgroundColor: [
									'rgb(54, 162, 235)',
									'rgb(201, 203, 207)'
									]
								}]
							},
							options: {
								responsive: true,
								maintainAspectRatio: false
							}
						}
					);
				}

				// Devices chart
				var devicesCtx = document.getElementById( 'apollo-chart-devices' );
				if (devicesCtx && typeof Chart !== 'undefined') {
					new Chart(
						devicesCtx,
						{
							type: 'bar',
							data: {
								labels: ['Desktop', 'Mobile'],
								datasets: [{
									label: 'Acessos',
									data: [60, 40],
									backgroundColor: [
									'rgba(54, 162, 235, 0.5)',
									'rgba(255, 99, 132, 0.5)'
									]
								}]
							},
							options: {
								responsive: true,
								maintainAspectRatio: false
							}
						}
					);
				}
			}

			// Export functions
			window.apolloExportCSV = function (tableId) {
				var table = $( '#' + tableId ).DataTable();
				var data  = table.data().toArray();
				var csv   = '';

				// Headers
				table.columns().every(
					function () {
						csv += this.header().textContent + ',';
					}
				);
				csv += '\n';

				// Data
				data.forEach(
					function (row) {
						Object.values( row ).forEach(
							function (cell) {
								csv += '"' + String( cell ).replace( /"/g, '""' ) + '",';
							}
						);
						csv += '\n';
					}
				);

				// Download
				var blob   = new Blob( [csv], { type: 'text/csv' } );
				var url    = window.URL.createObjectURL( blob );
				var a      = document.createElement( 'a' );
				a.href     = url;
				a.download = 'apollo-export-' + new Date().getTime() + '.csv';
				a.click();
			};

			window.apolloExportPNG = function (canvasId) {
				var canvas = document.getElementById( canvasId );
				if (canvas) {
					var url    = canvas.toDataURL( 'image/png' );
					var a      = document.createElement( 'a' );
					a.href     = url;
					a.download = 'apollo-chart-' + new Date().getTime() + '.png';
					a.click();
				}
			};
		}
	);
})( jQuery );
