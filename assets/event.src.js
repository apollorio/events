/**
 * ============================================================================
 * APOLLO EVENT.JS - Event/DJ/Local Behaviors
 * ============================================================================
 *
 * Handles all event-related JavaScript: listings, filters, modals, forms, maps.
 * Loaded conditionally by base.js when event elements are detected.
 *
 * CDN: https://assets.apollo.rio.br/event.js
 * Source: event.src.js (this file)
 *
 * Scope: Event cards, single events, DJ pages, Local pages.
 * No global pollution, IIFE architecture.
 * ============================================================================
 */

(function () {
    'use strict';

    // Internal state
    let initialized = false;

    /**
     * Detect context from DOM
     */
    function detectContext() {
        const hasEventCards = document.querySelector('[data-apollo-event-card]');
        const isEventPage = document.body.classList.contains('ap-page-event');
        const isDjPage = document.body.classList.contains('ap-page-dj');
        const isLocalPage = document.body.classList.contains('ap-page-local');
        const hasEventArchive = document.querySelector('.ap-events-archive');

        return {
            isEventList: !!(hasEventCards || hasEventArchive),
            isSingleEvent: !!isEventPage,
            isDj: !!isDjPage,
            isLocal: !!isLocalPage
        };
    }

    /**
     * AJAX helper
     */
    function ajaxRequest(action, data, callback) {
        const formData = new FormData();
        formData.append('action', action);
        Object.keys(data).forEach(key => formData.append(key, data[key]));

        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(callback)
            .catch(err => console.error('Apollo AJAX Error:', err));
    }

    /**
     * Event Filters (AJAX-based)
     */
    function initEventFilters() {
        const categoryButtons = document.querySelectorAll('.ap-event-category');
        const searchInput = document.getElementById('eventSearchInput');
        const datePrev = document.getElementById('datePrev');
        const dateNext = document.getElementById('dateNext');

        function applyFilters() {
            const category = document.querySelector('.ap-event-category.active')?.dataset.slug || 'all';
            const search = searchInput?.value || '';
            const month = document.getElementById('dateDisplay')?.dataset.month || '';

            ajaxRequest('apollo_filter_events', { category, search, month }, (response) => {
                if (response.success) {
                    document.querySelector('.ap-events-container').innerHTML = response.data.html;
                }
            });
        }

        categoryButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                categoryButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                applyFilters();
            });
        });

        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(applyFilters, 300);
            });
        }

        if (datePrev) {
            datePrev.addEventListener('click', () => {
                // Update month logic
                applyFilters();
            });
        }
        if (dateNext) {
            dateNext.addEventListener('click', () => {
                // Update month logic
                applyFilters();
            });
        }
    }

    /**
     * Event Modals
     */
    function initEventModals() {
        document.addEventListener('click', (e) => {
            const card = e.target.closest('[data-apollo-event-card]');
            if (card) {
                const eventId = card.dataset.eventId;
                ajaxRequest('apollo_get_event_modal', { event_id: eventId }, (response) => {
                    if (response.success) {
                        document.body.insertAdjacentHTML('beforeend', response.data.html);
                        // Track view
                        ajaxRequest('apollo_track_event_view', { event_id: eventId });
                    }
                });
            }
        });
    }

    /**
     * Favourites/Bookmarks
     */
    function initFavourites() {
        document.addEventListener('click', (e) => {
            const favBtn = e.target.closest('.ap-fav-btn');
            if (favBtn) {
                const eventId = favBtn.dataset.eventId;
                ajaxRequest('toggle_favorite', { event_id: eventId }, (response) => {
                    if (response.success) {
                        favBtn.classList.toggle('active', response.data.is_favourited);
                    }
                });
            }
        });
    }

    /**
     * Tracking (click outs)
     */
    function initTracking() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.ap-event-link');
            if (link) {
                const eventId = link.dataset.eventId;
                ajaxRequest('apollo_record_click_out', { event_id: eventId });
            }
        });
    }

    /**
     * Single Event Components
     */
    function initSingleEvent() {
        // Promo Slider
        const promoTrack = document.getElementById('promoTrack');
        if (promoTrack) {
            const slides = promoTrack.querySelectorAll('.promo-slide');
            let current = 0;
            setInterval(() => {
                current = (current + 1) % slides.length;
                promoTrack.style.transform = `translateX(-${current * 100}%)`;
            }, 5000);
        }

        // Local Slider
        const localTrack = document.getElementById('localTrack');
        if (localTrack) {
            // Similar to promo
        }

        // Map
        const mapEl = document.getElementById('eventMap');
        if (mapEl && typeof L !== 'undefined') {
            const lat = parseFloat(mapEl.dataset.lat);
            const lng = parseFloat(mapEl.dataset.lng);
            if (lat && lng) {
                const map = L.map('eventMap').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker([lat, lng]).addTo(map);
            }
        }

        // Share
        const shareBtn = document.getElementById('bottomShareBtn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                if (navigator.share) {
                    navigator.share({ title: document.title, url: window.location.href });
                } else {
                    navigator.clipboard.writeText(window.location.href);
                }
            });
        }

        // Copy Promo
        document.addEventListener('click', (e) => {
            if (e.target.closest('.copy-code-mini')) {
                navigator.clipboard.writeText('APOLLO');
            }
        });
    }

    /**
     * Event Form Utilities (for creation)
     */
    function initEventForms() {
        // Utility functions
        function apPad(n) { return String(n).padStart(2, '0'); }
        function apFormatDateLabel(d) {
            return `${apPad(d.getDate())}/${apPad(d.getMonth() + 1)} · ${apPad(d.getHours())}h`;
        }
        function apOpenModal(id) {
            document.getElementById(id)?.classList.add('ap-open');
        }
        function apCloseModal(id) {
            document.getElementById(id)?.classList.remove('ap-open');
        }

        // Modal close on overlay click
        document.querySelectorAll('.ap-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) apCloseModal(overlay.id);
            });
        });

        // Slider logic
        const apEls = {
            sd: document.getElementById('ap-start-date'),
            st: document.getElementById('ap-start-time'),
            sl: document.getElementById('ap-duration-slider'),
            dl: document.getElementById('ap-duration-label'),
            sf: document.getElementById('ap-slider-fill'),
            bb: document.getElementById('ap-bubble'),
            ls: document.getElementById('ap-label-start'),
            le: document.getElementById('ap-label-end')
        };

        function apUpdateAll() {
            if (!apEls.sd || !apEls.st || !apEls.sl) return;
            const d = apEls.sd.value;
            const t = apEls.st.value;
            if (!d || !t) return;
            const start = new Date(`${d}T${t}:00`);
            const dur = parseInt(apEls.sl.value, 10) || 1;
            const end = new Date(start.getTime() + dur * 3600 * 1000);
            if (apEls.ls) apEls.ls.textContent = apFormatDateLabel(start);
            if (apEls.le) apEls.le.textContent = apFormatDateLabel(end);
            if (apEls.dl) apEls.dl.textContent = `DURAÇÃO: ${dur}H`;
            const min = parseInt(apEls.sl.min, 10);
            const max = parseInt(apEls.sl.max, 10);
            const pct = ((dur - min) / (max - min)) * 100;
            if (apEls.sf) apEls.sf.style.width = pct + '%';
            if (apEls.bb) {
                apEls.bb.style.left = pct + '%';
                apEls.bb.textContent = `Fim ${apPad(end.getHours())}h`;
            }
        }

        // Init defaults
        if (apEls.sd && apEls.st && apEls.sl) {
            const now = new Date();
            now.setHours(23, 0, 0, 0);
            apEls.sd.valueAsDate = now;
            apEls.st.value = '23:00';
            apEls.sl.value = 9;
            apUpdateAll();
        }

        apEls.sd?.addEventListener('change', apUpdateAll);
        apEls.st?.addEventListener('change', apUpdateAll);
        apEls.sl?.addEventListener('input', apUpdateAll);

        // Combobox
        function apOpenCombobox(input) {
            input.nextElementSibling?.classList.add('ap-active');
        }
        function apCloseCombobox(input) {
            setTimeout(() => input.nextElementSibling?.classList.remove('ap-active'), 200);
        }
        function apFilterCombobox(input) {
            const filter = input.value.toLowerCase();
            const options = input.nextElementSibling?.querySelectorAll('.ap-combobox-option') || [];
            options.forEach(opt => {
                opt.classList.toggle('ap-hidden', !opt.textContent.toLowerCase().includes(filter));
            });
        }

        // Chips
        function apSelectSound(val) {
            const input = document.getElementById('ap-sounds-input');
            const container = document.getElementById('ap-sounds-container');
            if (!container || !input) return;
            if (!Array.from(container.children).some(c => c.textContent.includes(val))) {
                const chip = document.createElement('div');
                chip.className = 'ap-chip ap-active';
                chip.innerHTML = `${val} <span class="ap-chip-remove" onclick="this.parentElement.remove()">×</span>`;
                container.appendChild(chip);
            }
            input.value = '';
            apFilterCombobox(input);
        }

        // Local selection
        function apSelectLocal(val) {
            const input = document.getElementById('ap-local-input');
            if (!input) return;
            if (val === 'other') {
                input.value = 'Outro';
                document.getElementById('ap-manual-local')?.style.setProperty('display', 'block');
            } else {
                input.value = val;
                document.getElementById('ap-manual-local')?.style.setProperty('display', 'none');
            }
        }

        // Timetable
        function apAddTimetableRow(name) {
            const container = document.getElementById('ap-timetable-list');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'ap-timetable-row';
            row.innerHTML = `
        <div class="ap-drag-handle">
          <i class="ph-bold ph-caret-up" onclick="apMoveRow(this, -1)"></i>
          <i class="ph-bold ph-caret-down" onclick="apMoveRow(this, 1)"></i>
        </div>
        <div class="ap-dj-name">${name}</div>
        <div class="ap-time-inputs">
          <input type="time"> <span style="font-size:10px;color:var(--ap-text-muted)">às</span> <input type="time">
        </div>
        <i class="ph-bold ph-x" style="cursor:pointer; font-size:12px; opacity:0.6" onclick="this.parentElement.remove()"></i>
      `;
            container.appendChild(row);
        }

        function apAddDJ(name) {
            const input = document.getElementById('ap-dj-input');
            const container = document.getElementById('ap-timetable-list');
            if (input) input.value = name;
            if (container) {
                const exists = Array.from(container.children).some(row => row.querySelector('.ap-dj-name')?.textContent === name);
                if (!exists) apAddTimetableRow(name);
            }
        }

        function apMoveRow(btn, direction) {
            const row = btn.closest('.ap-timetable-row');
            if (!row) return;
            const parent = row.parentElement;
            if (direction === -1 && row.previousElementSibling) {
                parent.insertBefore(row, row.previousElementSibling);
            } else if (direction === 1 && row.nextElementSibling) {
                parent.insertBefore(row.nextElementSibling, row);
            }
        }

        // Save new local/DJ
        function apSaveNewLocal() {
            const nameEl = document.getElementById('ap-new-local-name');
            const name = nameEl?.value.trim();
            if (!name) return;
            const input = document.getElementById('ap-local-input');
            const dropdown = document.getElementById('ap-local-dropdown');
            if (dropdown) {
                const newOption = document.createElement('div');
                newOption.className = 'ap-combobox-option';
                newOption.textContent = name;
                newOption.addEventListener('mousedown', () => apSelectLocal(name));
                dropdown.appendChild(newOption);
            }
            if (input) input.value = name;
            document.getElementById('ap-manual-local')?.style.setProperty('display', 'none');
            nameEl.value = '';
            document.getElementById('ap-new-local-address').value = '';
            apCloseModal('ap-local-modal');
        }

        function apSaveNewDJ() {
            const nameEl = document.getElementById('ap-new-dj-name');
            const name = nameEl?.value.trim();
            if (!name) return;
            const dropdown = document.getElementById('ap-dj-dropdown');
            if (dropdown) {
                const newOption = document.createElement('div');
                newOption.className = 'ap-combobox-option';
                newOption.textContent = name;
                newOption.addEventListener('mousedown', () => apAddDJ(name));
                dropdown.appendChild(newOption);
            }
            apAddTimetableRow(name);
            nameEl.value = '';
            apCloseModal('ap-dj-modal');
        }

        // Geolocation mock
        function apRefreshGeo() {
            const icon = document.querySelector('#ap-local-modal .ph-arrows-clockwise');
            if (icon) icon.classList.add('ph-spin');
            setTimeout(() => {
                document.getElementById('ap-local-lat').value = '-22.9068';
                document.getElementById('ap-local-lon').value = '-43.1729';
                if (icon) icon.classList.remove('ph-spin');
            }, 1000);
        }

        // Text formatting
        function apFormatText(cmd) {
            const t = document.getElementById('ap-event-desc');
            if (t) {
                t.value += ` [${cmd}] `;
                t.focus();
            }
        }

        function apInsertBullet() {
            const t = document.getElementById('ap-event-desc');
            if (t) {
                const prefix = t.value && !t.value.endsWith('\n') ? '\n' : '';
                t.value += prefix + '• ';
                t.focus();
            }
        }

        // File previews
        function apPreviewFile(inputId, imgId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(imgId);
            if (!input || !preview || !input.files?.[0]) return;
            const reader = new FileReader();
            reader.onload = () => {
                preview.src = reader.result;
                preview.style.display = 'block';
                preview.nextElementSibling?.style.setProperty('opacity', '0');
            };
            reader.readAsDataURL(input.files[0]);
        }

        function apTriggerGallery(idx) {
            const inputs = document.querySelectorAll('.ap-gallery-slot input[type="file"]');
            inputs[idx]?.click();
        }

        function apPreviewGallery(input, idx) {
            if (!input.files?.[0]) return;
            const img = document.getElementById('ap-gal-' + idx);
            if (!img) return;
            const reader = new FileReader();
            reader.onload = () => {
                img.src = reader.result;
                img.style.display = 'block';
                input.parentElement.querySelector('i')?.style.setProperty('display', 'none');
            };
            reader.readAsDataURL(input.files[0]);
        }

        // Form submit mock
        function apSubmitForm() {
            const btn = document.querySelector('.ap-btn-primary');
            if (!btn) return;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';
            setTimeout(() => {
                alert('Formulário Enviado!');
                btn.innerHTML = original;
                btn.disabled = false;
            }, 1000);
        }

        // Expose to window for inline calls (temporary, until templates updated)
        window.apOpenModal = apOpenModal;
        window.apCloseModal = apCloseModal;
        window.apOpenCombobox = apOpenCombobox;
        window.apCloseCombobox = apCloseCombobox;
        window.apFilterCombobox = apFilterCombobox;
        window.apSelectSound = apSelectSound;
        window.apSelectLocal = apSelectLocal;
        window.apAddDJ = apAddDJ;
        window.apMoveRow = apMoveRow;
        window.apSaveNewLocal = apSaveNewLocal;
        window.apSaveNewDJ = apSaveNewDJ;
        window.apRefreshGeo = apRefreshGeo;
        window.apFormatText = apFormatText;
        window.apInsertBullet = apInsertBullet;
        window.apPreviewFile = apPreviewFile;
        window.apTriggerGallery = apTriggerGallery;
        window.apPreviewGallery = apPreviewGallery;
        window.apSubmitForm = apSubmitForm;
    }

    /**
     * Initialize based on context
     */
    function init() {
        if (initialized) return;
        initialized = true;

        const context = detectContext();

        if (context.isEventList) {
            initEventFilters();
            initEventModals();
            initFavourites();
            initTracking();
        }

        if (context.isSingleEvent || context.isDj || context.isLocal) {
            initSingleEvent();
            initEventForms();
        }
    }

    // Run on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();