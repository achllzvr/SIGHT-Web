/**
 * LUMI clinic pin picker — Leaflet + OpenStreetMap tiles + Nominatim search.
 * Free / no API key. Nominatim: max ~1 request/sec; identify with Accept-Language.
 */
(function (window) {
  'use strict';

  var PH_CENTER = [14.5995, 120.9842];
  var DEFAULT_ZOOM = 6;
  var PIN_ZOOM = 15;
  var lastNominatimAt = 0;

  // Inline SVG data-URI — never depends on /vendor/leaflet/images/*.png
  var PIN_SVG =
    '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="40" viewBox="0 0 28 40">' +
    '<path fill="#0d9488" stroke="#0f766e" stroke-width="1.5" ' +
    'd="M14 1C7.4 1 2 6.4 2 13c0 9.5 12 25 12 25s12-15.5 12-25C26 6.4 20.6 1 14 1z"/>' +
    '<circle cx="14" cy="13" r="5" fill="#fff"/>' +
    '</svg>';
  var PIN_DATA_URI = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(PIN_SVG);
  var EMPTY_SHADOW =
    'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  function leafletBase() {
    return (window.LUMI_LEAFLET_BASE || '/vendor/leaflet').replace(/\/$/, '');
  }

  function loadCss(href) {
    if (document.querySelector('link[data-lumi-leaflet]')) return;
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    link.setAttribute('data-lumi-leaflet', '1');
    document.head.appendChild(link);
  }

  function ensurePinStyles() {
    if (document.getElementById('lumi-clinic-pin-styles')) return;
    var style = document.createElement('style');
    style.id = 'lumi-clinic-pin-styles';
    style.textContent = [
      '.leaflet-marker-icon.lumi-clinic-pin-img{border:none!important;background:transparent!important;}',
      '.lumi-clinic-map-picker{display:flex;flex-direction:column;gap:0.5rem;width:100%;}',
      '.lumi-map-label{display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin:0;}',
      '.lumi-map-hint{margin:0;font-size:0.8125rem;line-height:1.45;color:#6b7280;}',
      '.lumi-map-search{display:flex;flex-wrap:wrap;gap:0.5rem;align-items:stretch;}',
      '.lumi-map-search-input{flex:1 1 180px;min-width:0;padding:0.7rem 0.9rem;border:1px solid #d1d5db;border-radius:10px;font-size:0.9375rem;outline:none;background:#fff;min-height:42px;}',
      '.lumi-map-search-input:focus{border-color:#295c4a;box-shadow:0 0 0 3px rgba(41,92,74,.15);}',
      '.lumi-map-btn{padding:0.65rem 0.95rem;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;font-size:0.875rem;font-weight:600;cursor:pointer;white-space:nowrap;min-height:42px;}',
      '.lumi-map-btn:hover{background:#f9fafb;}',
      '.lumi-map-btn--primary{background:#295c4a;border-color:#295c4a;color:#fff;}',
      '.lumi-map-btn--primary:hover{background:#1f4638;color:#fff;}',
      '.lumi-clinic-map{height:240px;width:100%;border-radius:12px;border:1px solid #e5e7eb;z-index:1;background:#eef2f0;}',
      '.lumi-map-status{margin:0;min-height:1.25em;font-size:0.8125rem;line-height:1.4;color:#6b7280;}',
    ].join('');
    document.head.appendChild(style);
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      if (window.L) {
        resolve();
        return;
      }
      var existing = document.querySelector('script[data-lumi-leaflet]');
      if (existing) {
        if (window.L) {
          resolve();
          return;
        }
        existing.addEventListener('load', resolve);
        existing.addEventListener('error', reject);
        return;
      }
      var s = document.createElement('script');
      s.src = src;
      s.setAttribute('data-lumi-leaflet', '1');
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function patchDefaultIcon() {
    if (!window.L || !window.L.Icon || !window.L.Icon.Default) return;
    // Stop Leaflet from prefixing a broken relative /images/ path.
    delete window.L.Icon.Default.prototype._getIconUrl;
    window.L.Icon.Default.mergeOptions({
      iconUrl: PIN_DATA_URI,
      iconRetinaUrl: PIN_DATA_URI,
      shadowUrl: EMPTY_SHADOW,
      iconSize: [28, 40],
      iconAnchor: [14, 40],
      popupAnchor: [0, -36],
      shadowSize: [0, 0],
      className: 'lumi-clinic-pin-img',
    });
  }

  function clinicPinIcon() {
    return window.L.icon({
      iconUrl: PIN_DATA_URI,
      iconRetinaUrl: PIN_DATA_URI,
      shadowUrl: EMPTY_SHADOW,
      iconSize: [28, 40],
      iconAnchor: [14, 40],
      popupAnchor: [0, -36],
      shadowSize: [0, 0],
      className: 'lumi-clinic-pin-img',
    });
  }

  function ensureLeaflet() {
    var base = leafletBase();
    loadCss(base + '/leaflet.css');
    ensurePinStyles();
    return loadScript(base + '/leaflet.js').then(function () {
      patchDefaultIcon();
    });
  }

  function parseNum(el) {
    if (!el) return null;
    var v = parseFloat(el.value);
    return Number.isFinite(v) ? v : null;
  }

  function toCoord(value) {
    if (value === null || value === undefined || value === '') return null;
    var n = typeof value === 'number' ? value : parseFloat(String(value).trim());
    return Number.isFinite(n) ? n : null;
  }

  function setInput(el, value) {
    if (!el) return;
    el.value = value === null || value === undefined ? '' : String(value);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function waitForElement(id, attempts) {
    attempts = attempts || 50;
    return new Promise(function (resolve, reject) {
      var tries = 0;
      (function tick() {
        var el = document.getElementById(id);
        if (el) {
          resolve(el);
          return;
        }
        tries += 1;
        if (tries >= attempts) {
          reject(new Error('Missing element #' + id));
          return;
        }
        setTimeout(tick, 40);
      })();
    });
  }

  var instances = {};

  function destroy(mapId) {
    var inst = instances[mapId];
    if (!inst) return;
    try {
      if (inst.abortController) inst.abortController.abort();
    } catch (_) {}
    try {
      if (inst.map) inst.map.remove();
    } catch (_) {}
    instances[mapId] = null;
    delete instances[mapId];
  }

  function mount(options) {
    return ensureLeaflet()
      .then(function () {
        return waitForElement(options.mapId);
      })
      .then(function () {
      var mapId = options.mapId;
      var container = document.getElementById(mapId);
      if (!container) {
        console.warn('[LumiClinicMapPicker] missing map container', mapId);
        return null;
      }

      destroy(mapId);
      patchDefaultIcon();

      var latInput = document.getElementById(options.latInputId);
      var lngInput = document.getElementById(options.lngInputId);
      var addressInput = options.addressInputId
        ? document.getElementById(options.addressInputId)
        : null;
      var searchInput = options.searchInputId
        ? document.getElementById(options.searchInputId)
        : null;
      var searchBtn = options.searchBtnId
        ? document.getElementById(options.searchBtnId)
        : null;
      var clearBtn = options.clearBtnId
        ? document.getElementById(options.clearBtnId)
        : null;
      var statusEl = options.statusId
        ? document.getElementById(options.statusId)
        : null;

      // Prefer explicit saved coords, then whatever is already in the form fields.
      var initLat = toCoord(options.initialLat);
      var initLng = toCoord(options.initialLng);
      if (initLat === null) initLat = parseNum(latInput);
      if (initLng === null) initLng = parseNum(lngInput);
      var hasPin = initLat !== null && initLng !== null;

      var savedAddress =
        options.initialAddress != null && String(options.initialAddress).trim() !== ''
          ? String(options.initialAddress).trim()
          : '';
      if (savedAddress && addressInput) {
        setInput(addressInput, savedAddress);
      }
      if (savedAddress && searchInput && !(searchInput.value || '').trim()) {
        searchInput.value = savedAddress;
      }

      var map = window.L.map(mapId, {
        scrollWheelZoom: true,
      }).setView(hasPin ? [initLat, initLng] : PH_CENTER, hasPin ? PIN_ZOOM : DEFAULT_ZOOM);

      window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution:
          '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      }).addTo(map);

      var marker = null;
      var pinIcon = clinicPinIcon();

      function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg || '';
      }

      function placeMarker(lat, lng, pan, statusMsg) {
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        if (marker) {
          marker.setLatLng([lat, lng]);
        } else {
          marker = window.L.marker([lat, lng], {
            draggable: true,
            icon: pinIcon,
          }).addTo(map);
          marker.on('dragend', function () {
            var p = marker.getLatLng();
            setInput(latInput, p.lat.toFixed(7));
            setInput(lngInput, p.lng.toFixed(7));
            setStatus('Pin moved. Save to keep this location.');
          });
        }
        setInput(latInput, Number(lat).toFixed(7));
        setInput(lngInput, Number(lng).toFixed(7));
        if (pan) {
          map.setView([lat, lng], Math.max(map.getZoom(), PIN_ZOOM));
        }
        if (statusMsg !== false) {
          setStatus(statusMsg || 'Pin placed. Save to keep this location.');
        }
      }

      function clearPin() {
        if (marker) {
          map.removeLayer(marker);
          marker = null;
        }
        setInput(latInput, '');
        setInput(lngInput, '');
        setStatus('Pin removed. This clinic will not show on the map view.');
      }

      map.on('click', function (e) {
        placeMarker(e.latlng.lat, e.latlng.lng, false);
      });

      if (hasPin) {
        placeMarker(
          initLat,
          initLng,
          true,
          'Saved location loaded. Drag the pin to adjust, or search for a new address.'
        );
      } else {
        setStatus('Search an address or tap the map to place a pin.');
      }

      function runSearch() {
        if (!searchInput) return;
        var q = (searchInput.value || '').trim();
        if (q.length < 3) {
          setStatus('Type at least 3 characters to search.');
          return;
        }

        var now = Date.now();
        var wait = Math.max(0, 1100 - (now - lastNominatimAt));
        setStatus(wait ? 'Please wait a moment…' : 'Searching…');

        setTimeout(function () {
          lastNominatimAt = Date.now();
          var url =
            'https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' +
            encodeURIComponent(q);
          fetch(url, {
            headers: {
              Accept: 'application/json',
              'Accept-Language': 'en',
            },
          })
            .then(function (r) {
              return r.json();
            })
            .then(function (results) {
              if (!Array.isArray(results) || !results.length) {
                setStatus('No results found. Try a more specific address.');
                return;
              }
              var hit = results[0];
              var lat = parseFloat(hit.lat);
              var lng = parseFloat(hit.lon);
              placeMarker(lat, lng, true);
              if (addressInput && hit.display_name) {
                setInput(addressInput, hit.display_name);
              }
              setStatus('Found: ' + (hit.display_name || 'location'));
            })
            .catch(function () {
              setStatus('Search failed. Try again in a moment.');
            });
        }, wait);
      }

      var abortController = new AbortController();
      var signal = abortController.signal;

      if (searchBtn) {
        searchBtn.addEventListener(
          'click',
          function (ev) {
            ev.preventDefault();
            runSearch();
          },
          { signal: signal }
        );
      }
      if (searchInput) {
        searchInput.addEventListener(
          'keydown',
          function (ev) {
            if (ev.key === 'Enter') {
              ev.preventDefault();
              runSearch();
            }
          },
          { signal: signal }
        );
      }
      if (clearBtn) {
        clearBtn.addEventListener(
          'click',
          function (ev) {
            ev.preventDefault();
            clearPin();
          },
          { signal: signal }
        );
      }

      function refreshSize() {
        try {
          map.invalidateSize();
          if (marker) {
            map.setView(marker.getLatLng(), Math.max(map.getZoom(), hasPin ? PIN_ZOOM : map.getZoom()));
          }
        } catch (_) {}
      }

      setTimeout(refreshSize, 80);
      setTimeout(refreshSize, 250);

      instances[mapId] = {
        map: map,
        marker: marker,
        abortController: abortController,
        placeMarker: placeMarker,
        clearPin: clearPin,
        invalidate: refreshSize,
      };

      return instances[mapId];
    })
    .catch(function (err) {
      console.warn('[LumiClinicMapPicker] mount failed', err);
      return null;
    });
  }

  function invalidate(mapId) {
    if (instances[mapId]) instances[mapId].invalidate();
  }

  window.LumiClinicMapPicker = {
    mount: mount,
    destroy: destroy,
    invalidate: invalidate,
    toCoord: toCoord,
    PH_CENTER: PH_CENTER,
  };
})(window);
