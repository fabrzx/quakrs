/**
 * SISMA MONITOR - SPA Logic & Acid Integration
 */

document.addEventListener('DOMContentLoaded', () => {
    const MAP_LIMITS = {
        minLat: -85,
        maxLat: 85,
        minLng: -180,
        maxLng: 180
    };
    const WORLD_WRAP_RANGE = 15;

    const mapState = {
        instance: null
    };
    const worldOffsets = [];
    for (let offset = -WORLD_WRAP_RANGE; offset <= WORLD_WRAP_RANGE; offset += 1) {
        worldOffsets.push(offset);
    }

    function bindPointLabel(layer, labelText, className) {
        layer.bindTooltip(labelText, {
            permanent: true,
            direction: 'right',
            offset: [10, 0],
            className: `map-point-label ${className}`
        });
    }

    function shortPlaceLabel(place) {
        const normalized = normalizePlace(place);
        const firstChunk = normalized.split(',')[0].trim();
        return firstChunk.length > 22 ? `${firstChunk.slice(0, 22)}…` : firstChunk;
    }

    function eventIsInLast24Hours(event) {
        const ts = Date.parse(event?.event_time_utc ?? '');
        if (!Number.isFinite(ts)) return false;
        return (Date.now() - ts) <= 24 * 60 * 60 * 1000;
    }

    function pickGlobalListEvents(events) {
        return (Array.isArray(events) ? events : [])
            .filter((event) => Number.isFinite(Number(event?.magnitude))
                && Number(event.magnitude) >= 5.0
                && eventIsInLast24Hours(event))
            .sort((a, b) => {
                const magDiff = Number(b.magnitude) - Number(a.magnitude);
                if (magDiff !== 0) return magDiff;
                const aTs = Date.parse(a?.event_time_utc ?? '') || 0;
                const bTs = Date.parse(b?.event_time_utc ?? '') || 0;
                return bTs - aTs;
            });
    }

    function buildWrappedSeismicLayer(events, minMagnitude, color) {
        const valid = (Array.isArray(events) ? events : [])
            .filter((event) => Number.isFinite(Number(event?.magnitude))
                && Number(event.magnitude) >= minMagnitude
                && Number.isFinite(Number(event?.latitude))
                && Number.isFinite(Number(event?.longitude)))
            .sort((a, b) => {
                const magDiff = Number(b.magnitude) - Number(a.magnitude);
                if (magDiff !== 0) return magDiff;
                const aTs = Date.parse(a?.event_time_utc ?? '') || 0;
                const bTs = Date.parse(b?.event_time_utc ?? '') || 0;
                return bTs - aTs;
            });

        const markers = [];
        valid.forEach((event) => {
            const magnitude = Number(event.magnitude);
            const latitude = Number(event.latitude);
            const longitude = Number(event.longitude);
            const radius = Math.max(4, Math.min(18, 3 + ((magnitude - 1.5) * 1.6)));
            const label = `M${magnitude.toFixed(1)} ${shortPlaceLabel(event.place)}`;

            worldOffsets.forEach((offset) => {
                const marker = L.circleMarker([latitude, longitude + (offset * 360)], {
                    radius,
                    color,
                    fillColor: color,
                    fillOpacity: 1,
                    weight: 2
                });
                bindPointLabel(marker, label, 'label-seismic');
                markers.push(marker);
            });
        });

        return L.layerGroup(markers);
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function normalizeLongitude(lng) {
        const span = MAP_LIMITS.maxLng - MAP_LIMITS.minLng;
        const offset = ((lng - MAP_LIMITS.minLng) % span + span) % span;
        return MAP_LIMITS.minLng + offset;
    }

    function sanitizeLatLng(latlng) {
        return {
            lat: clamp(latlng.lat, MAP_LIMITS.minLat, MAP_LIMITS.maxLat),
            lng: normalizeLongitude(latlng.lng)
        };
    }

    function formatLat(lat) {
        const abs = Math.round(Math.abs(lat));
        if (abs === 0) return 'EQ 0';
        return `${abs}${lat >= 0 ? 'N' : 'S'}`;
    }

    function formatLng(lng) {
        const abs = Math.round(Math.abs(lng));
        if (abs === 0) return '0';
        return `${abs}${lng >= 0 ? 'E' : 'W'}`;
    }

    function formatLatDetailed(lat) {
        const abs = Math.abs(lat).toFixed(2);
        return `${abs}${lat >= 0 ? 'N' : 'S'}`;
    }

    function formatLngDetailed(lng) {
        const abs = Math.abs(lng).toFixed(2);
        return `${abs}${lng >= 0 ? 'E' : 'W'}`;
    }

    function updateCursorCoords(latlng) {
        const el = document.querySelector('.map-cursor-coords');
        if (!el) return;
        if (!latlng) {
            el.textContent = 'LAT -- | LON --';
            return;
        }
        const safeCoords = sanitizeLatLng(latlng);
        el.textContent = `LAT ${formatLatDetailed(safeCoords.lat)} | LON ${formatLngDetailed(safeCoords.lng)}`;
    }

    function updateMapTics(map) {
        const mapContainer = document.querySelector('.map-container');
        if (!mapContainer) return;
        const tics = mapContainer.querySelector('.map-tics');
        if (!tics) return;

        const mapSize = map.getSize();
        tics.querySelectorAll('.tic-y').forEach((el) => {
            const ratio = Number(el.dataset.ratio || 0.5);
            const point = L.point(mapSize.x * 0.03, mapSize.y * ratio);
            const latlng = sanitizeLatLng(map.containerPointToLatLng(point));
            el.textContent = formatLat(latlng.lat);
        });
        tics.querySelectorAll('.tic-x').forEach((el) => {
            const ratio = Number(el.dataset.ratio || 0.5);
            const point = L.point(mapSize.x * ratio, mapSize.y * 0.97);
            const latlng = sanitizeLatLng(map.containerPointToLatLng(point));
            el.textContent = formatLng(latlng.lng);
        });
    }

    function buildCategoryLayers() {
        const categoryStyles = {
            seismic: { color: '#FF6600', fillColor: '#FF6600' },
            volcanoes: { color: '#CCFF00', fillColor: '#CCFF00' },
            tsunamis: { color: '#00FFFF', fillColor: '#00FFFF' }
        };

        function buildWrappedCircleMarker(baseLat, baseLng, options, labelText, className) {
            return L.layerGroup(worldOffsets.map((offset) => {
                const marker = L.circleMarker([baseLat, baseLng + (offset * 360)], options);
                bindPointLabel(marker, labelText, className);
                return marker;
            }));
        }

        function buildWrappedIconMarker(baseLat, baseLng, iconOptions, labelText, className) {
            return L.layerGroup(worldOffsets.map((offset) => {
                const marker = L.marker([baseLat, baseLng + (offset * 360)], {
                    icon: L.divIcon(iconOptions)
                });
                bindPointLabel(marker, labelText, className);
                return marker;
            }));
        }

        const seededEvents = [
            { magnitude: 7.4, latitude: 38.2, longitude: 142.5, place: 'HONSHU, JAPAN', event_time_utc: null },
            { magnitude: 6.8, latitude: -33.1, longitude: -71.6, place: 'SOUTH SANDWICH ISLANDS', event_time_utc: null },
            { magnitude: 5.2, latitude: 63.1, longitude: -32.1, place: 'REYKJANES RIDGE', event_time_utc: null }
        ];

        const volcanoKrakatoa = buildWrappedIconMarker(-6.102, 105.423, {
            className: 'marker-volcano',
            iconSize: [16, 16],
            iconAnchor: [8, 12]
        }, 'KRAKATOA VEI4', 'label-volcano');

        const volcanoEtna = buildWrappedIconMarker(37.751, 14.993, {
            className: 'marker-volcano',
            iconSize: [16, 16],
            iconAnchor: [8, 12]
        }, 'ETNA VEI2', 'label-volcano');

        const tsunamiPacific = buildWrappedIconMarker(35.5, 150.5, {
            className: 'marker-tsunami',
            iconSize: [12, 12],
            iconAnchor: [6, 6]
        }, 'PACIFIC WARNING', 'label-tsunami');

        const wrappedFaultLines = worldOffsets.map((offset) => L.polyline(
            [[45, 140], [32, 155], [15, 165], [-5, 170], [-22, -175], [-40, -150]]
                .map(([lat, lng]) => [lat, lng + (offset * 360)]),
            {
                color: '#FF6600',
                weight: 2,
                dashArray: '6 4'
            }
        ));

        return {
            seismic: L.layerGroup([
                ...wrappedFaultLines,
                buildWrappedSeismicLayer(seededEvents, 5.0, categoryStyles.seismic.color)
            ]),
            seismicGlobal: buildWrappedSeismicLayer(seededEvents, 5.0, categoryStyles.seismic.color),
            volcanoes: L.layerGroup([volcanoKrakatoa, volcanoEtna]),
            tsunamis: L.layerGroup([tsunamiPacific])
        };
    }

    function syncLiveMapLayers(events) {
        if (!mapState.instance) return;
        const { map, layers } = mapState.instance;
        const seismicColor = '#FF6600';
        const globalListEvents = pickGlobalListEvents(events);

        if (layers.seismic && map.hasLayer(layers.seismic)) {
            map.removeLayer(layers.seismic);
        }
        if (layers.seismicGlobal && map.hasLayer(layers.seismicGlobal)) {
            map.removeLayer(layers.seismicGlobal);
        }

        layers.seismic = buildWrappedSeismicLayer(events, 5.0, seismicColor);
        layers.seismicGlobal = buildWrappedSeismicLayer(globalListEvents, 5.0, seismicColor);

        const activeTab = document.querySelector('.nav-btn.active')?.getAttribute('data-target') || 'global';
        setMapCategory(activeTab);
    }

    function createMapInstance(elementId) {
        const mapNode = document.getElementById(elementId);
        if (!mapNode) return null;

        const map = L.map(elementId, {
            zoomControl: false,
            attributionControl: false,
            minZoom: 2,
            maxZoom: 18,
            zoomSnap: 0.25,
            zoomDelta: 1,
            wheelPxPerZoomLevel: 30,
            keyboard: false,
            worldCopyJump: false,
            maxBoundsViscosity: 1.0
        }).setView([15, 20], 2);

        // Block vertical over-pan into non-tiled polar area, keep horizontal travel effectively free.
        map.setMaxBounds([[MAP_LIMITS.minLat, -5400], [MAP_LIMITS.maxLat, 5400]]);

        const tileProviders = [
            {
                url: 'https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png',
                options: { subdomains: 'abcd', maxZoom: 19, noWrap: false }
            },
            {
                url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                options: { subdomains: 'abc', maxZoom: 19, crossOrigin: true, noWrap: false }
            }
        ];
        let providerIndex = 0;
        let currentTileLayer = null;

        function loadTileProvider(index) {
            if (currentTileLayer) map.removeLayer(currentTileLayer);
            const provider = tileProviders[index];
            let errorCount = 0;
            currentTileLayer = L.tileLayer(provider.url, provider.options).addTo(map);

            currentTileLayer.on('load', () => {
                mapNode.classList.add('map-ready');
            });

            currentTileLayer.on('tileerror', () => {
                errorCount += 1;
                if (errorCount >= 3 && providerIndex < tileProviders.length - 1) {
                    providerIndex += 1;
                    loadTileProvider(providerIndex);
                }
            });
        }

        loadTileProvider(providerIndex);

        return {
            map,
            layers: buildCategoryLayers()
        };
    }

    function initBrutalistMap() {
        if (typeof L === 'undefined') return;
        mapState.instance = createMapInstance('brutalist-map');
        if (mapState.instance) {
            const map = mapState.instance.map;
            const update = () => updateMapTics(map);
            map.on('load move zoom resize moveend zoomend', update);
            map.on('mousemove', (e) => updateCursorCoords(e.latlng));
            map.on('mouseout', () => updateCursorCoords(null));
            update();
            updateCursorCoords(null);
        }
    }

    function setMapCategory(targetTab) {
        if (!mapState.instance) return;

        Object.entries(mapState.instance.layers).forEach(([category, layer]) => {
            let shouldShow = false;
            if (targetTab === 'global') {
                shouldShow = category === 'seismicGlobal' || category === 'volcanoes';
            } else if (targetTab === 'network') {
                shouldShow = category !== 'seismicGlobal';
            } else {
                shouldShow = category === targetTab;
            }
            if (shouldShow && !mapState.instance.map.hasLayer(layer)) {
                layer.addTo(mapState.instance.map);
            } else if (!shouldShow && mapState.instance.map.hasLayer(layer)) {
                mapState.instance.map.removeLayer(layer);
            }
        });

        requestAnimationFrame(() => {
            mapState.instance.map.invalidateSize();
        });
    }

    function formatUtcTime(isoString) {
        if (!isoString) return '--:--:--';
        const date = new Date(isoString);
        if (Number.isNaN(date.getTime())) return '--:--:--';
        return [
            String(date.getUTCHours()).padStart(2, '0'),
            String(date.getUTCMinutes()).padStart(2, '0'),
            String(date.getUTCSeconds()).padStart(2, '0')
        ].join(':');
    }

    function classifyMagnitude(magnitude) {
        if (!Number.isFinite(magnitude)) {
            return { magClass: 'mag-49', tagText: '', tagClass: '' };
        }
        if (magnitude >= 7.0) {
            return { magClass: 'mag-74', tagText: 'TSUNAMI WARNING', tagClass: 'seismic-tag-danger' };
        }
        if (magnitude >= 6.0) {
            return { magClass: 'mag-68', tagText: 'MAJOR', tagClass: 'seismic-tag-neutral' };
        }
        return { magClass: magnitude >= 5.0 ? 'mag-52' : 'mag-49', tagText: '', tagClass: '' };
    }

    function normalizePlace(place) {
        if (!place) return 'UNKNOWN LOCATION';
        return String(place).replace(/\s+/g, ' ').trim().toUpperCase();
    }

    function buildSeismicFeedEntry(event, classification) {
        const magnitude = Number.isFinite(Number(event.magnitude)) ? Number(event.magnitude) : null;
        const depth = Number.isFinite(Number(event.depth_km)) ? Number(event.depth_km) : null;
        const magText = magnitude !== null ? `MAG ${magnitude.toFixed(1)}` : 'MAG --.-';
        const depthText = depth !== null ? `${depth.toFixed(1)} KM` : '--.- KM';
        const utcText = formatUtcTime(event.event_time_utc);
        const placeText = normalizePlace(event.place);

        const article = document.createElement('article');
        article.className = 'seismic-feed-entry border-bottom';

        const topRow = document.createElement('div');
        topRow.className = 'seismic-feed-entry-top';

        const magEl = document.createElement('div');
        magEl.className = `seismic-mag ${classification.magClass}`;
        magEl.textContent = magText;
        topRow.appendChild(magEl);

        if (classification.tagText !== '') {
            const tagEl = document.createElement('div');
            tagEl.className = `seismic-tag ${classification.tagClass}`;
            tagEl.textContent = classification.tagText;
            topRow.appendChild(tagEl);
        }

        const locEl = document.createElement('div');
        locEl.className = 'seismic-loc';
        locEl.textContent = placeText;

        const metaEl = document.createElement('div');
        metaEl.className = 'seismic-meta mono-data';
        metaEl.innerHTML = `<span>DEPTH: <strong>${depthText}</strong></span><span>UTC: <strong>${utcText}</strong></span>`;

        article.appendChild(topRow);
        article.appendChild(locEl);
        article.appendChild(metaEl);
        return article;
    }

    function renderFeedEntries(feedSelector, events, options = {}) {
        const {
            maxItems = 20,
            minMagnitude = -Infinity,
            sortByMagnitudeFirst = false,
            tsunamiWarningActive = false,
            classify = null
        } = options;

        const feed = document.querySelector(feedSelector);
        if (!feed) return;

        feed.querySelectorAll('.seismic-feed-entry').forEach((entry) => entry.remove());

        const source = Array.isArray(events) ? [...events] : [];
        const filtered = source.filter((event) => {
            const magnitude = Number(event?.magnitude);
            return Number.isFinite(magnitude) && magnitude >= minMagnitude;
        });

        const sorted = filtered.sort((a, b) => {
            const aMag = Number(a?.magnitude);
            const bMag = Number(b?.magnitude);
            if (sortByMagnitudeFirst && Number.isFinite(aMag) && Number.isFinite(bMag) && aMag !== bMag) {
                return bMag - aMag;
            }
            const aTs = Date.parse(a?.event_time_utc ?? '') || 0;
            const bTs = Date.parse(b?.event_time_utc ?? '') || 0;
            return bTs - aTs;
        });
        const selected = Number.isFinite(maxItems) ? sorted.slice(0, maxItems) : sorted;

        if (selected.length === 0) {
            const empty = document.createElement('article');
            empty.className = 'seismic-feed-entry border-bottom';
            empty.innerHTML = '<div class="seismic-loc">LIVE FEED UNAVAILABLE</div><div class="seismic-meta mono-data"><span>RETRY: <strong>IN PROGRESS</strong></span></div>';
            feed.appendChild(empty);
            return;
        }

        selected.forEach((event) => {
            const magnitude = Number(event?.magnitude);
            const classification = typeof classify === 'function'
                ? classify(magnitude, event, tsunamiWarningActive)
                : classifyMagnitude(magnitude);
            feed.appendChild(buildSeismicFeedEntry(event, classification));
        });
    }

    function classifyGlobalMagnitude(magnitude, _event, tsunamiWarningActive) {
        if (!Number.isFinite(magnitude)) {
            return { magClass: 'mag-49', tagText: '', tagClass: '' };
        }
        if (magnitude >= 7.0) {
            if (tsunamiWarningActive) {
                return { magClass: 'mag-74', tagText: 'TSUNAMI WARNING', tagClass: 'seismic-tag-danger' };
            }
            return { magClass: 'mag-74', tagText: '', tagClass: '' };
        }
        if (magnitude >= 6.5) {
            return { magClass: 'mag-68', tagText: 'MAJOR', tagClass: 'seismic-tag-neutral' };
        }
        if (magnitude >= 5.0) {
            return { magClass: 'mag-52', tagText: '', tagClass: '' };
        }
        return { magClass: 'mag-49', tagText: '', tagClass: '' };
    }

    function renderSeismicFeed(events) {
        renderFeedEntries('#left-seismic .seismic-feed', events, {
            maxItems: 20,
            minMagnitude: -Infinity,
            sortByMagnitudeFirst: false,
            classify: (magnitude) => classifyMagnitude(magnitude)
        });
    }

    function renderGlobalFeed(events, tsunamiWarningActive) {
        const globalEvents = pickGlobalListEvents(events);
        renderFeedEntries('#left-global .global-feed', globalEvents, {
            maxItems: Number.POSITIVE_INFINITY,
            minMagnitude: 5.0,
            sortByMagnitudeFirst: true,
            tsunamiWarningActive,
            classify: classifyGlobalMagnitude
        });
    }

    function hasActiveTsunamiWarning(payload) {
        if (!payload || typeof payload !== 'object') return false;
        if (String(payload.highest_level || '').toLowerCase() === 'warning') return true;
        if (!Array.isArray(payload.alerts)) return false;
        return payload.alerts.some((alert) => String(alert?.warning_level || '').toLowerCase() === 'warning');
    }

    async function fetchEarthquakeAndTsunami() {
        const [earthquakesRes, tsunamiRes] = await Promise.all([
            fetch('/api/earthquakes.php', {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            }),
            fetch('/api/tsunami.php', {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            })
        ]);

        if (!earthquakesRes.ok) {
            throw new Error(`Earthquakes API returned ${earthquakesRes.status}`);
        }

        const earthquakesPayload = await earthquakesRes.json();
        const events = earthquakesPayload?.events ?? [];

        let tsunamiWarningActive = false;
        if (tsunamiRes.ok) {
            const tsunamiPayload = await tsunamiRes.json();
            tsunamiWarningActive = hasActiveTsunamiWarning(tsunamiPayload);
        }

        return { events, tsunamiWarningActive };
    }

    async function refreshSeismicFeed() {
        const { events, tsunamiWarningActive } = await fetchEarthquakeAndTsunami();
        renderSeismicFeed(events);
        renderGlobalFeed(events, tsunamiWarningActive);
        syncLiveMapLayers(events);
    }

    function initSeismicFeed() {
        refreshSeismicFeed().catch((error) => {
            console.warn('Seismic feed refresh failed:', error);
        });

        setInterval(() => {
            refreshSeismicFeed().catch((error) => {
                console.warn('Seismic feed refresh failed:', error);
            });
        }, 60000);
    }

    // 1. Live UTC Clock Update
    const timeDisplay = document.getElementById('live-time');
    
    function updateClock() {
        const now = new Date();
        const year = now.getUTCFullYear();
        const month = String(now.getUTCMonth() + 1).padStart(2, '0');
        const day = String(now.getUTCDate()).padStart(2, '0');
        const h = String(now.getUTCHours()).padStart(2, '0');
        const m = String(now.getUTCMinutes()).padStart(2, '0');
        const s = String(now.getUTCSeconds()).padStart(2, '0');
        
        const timeString = `LAST UPDATE: ${year}-${month}-${day} ${h}:${m}:${s} UTC`;
        if(timeDisplay) timeDisplay.textContent = timeString;
    }

    updateClock();
    setInterval(updateClock, 1000);

    // 2. Live Status Toggle Switch
    const toggleSwitch = document.getElementById('main-toggle');
    const toggleLabel = document.querySelector('.toggle-label');
    
    if(toggleSwitch) {
        toggleSwitch.addEventListener('click', () => {
            toggleSwitch.classList.toggle('active');
            
            if(toggleSwitch.classList.contains('active')) {
                toggleLabel.innerHTML = 'LIVE STATUS: <span class="text-seismic">ACTIVE</span>';
            } else {
                toggleLabel.innerHTML = 'LIVE STATUS: <span class="text-white">PAUSED</span>';
            }
        });
    }

    // 3. SPA Tab Switching Logic (Full Segregation)
    const navButtons = document.querySelectorAll('.nav-btn');
    const leftContents = document.querySelectorAll('.left-tab-content');
    const centerContents = document.querySelectorAll('.center-tab-content');
    const rightContents = document.querySelectorAll('.right-tab-content');
    const mapMarkers = document.querySelectorAll('.map-marker');

    initBrutalistMap();
    setMapCategory('global');
    initSeismicFeed();

    navButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Cleanup previously active buttons and their specific color classes
            navButtons.forEach(b => {
                b.classList.remove('active');
                const colorClassToRemove = b.getAttribute('data-color-class');
                if(colorClassToRemove) b.classList.remove(colorClassToRemove);
            });

            // Set clicked tab to active and attach its Acid color class
            btn.classList.add('active');
            const colorClass = btn.getAttribute('data-color-class');
            if(colorClass) btn.classList.add(colorClass);

            const targetTab = btn.getAttribute('data-target');

            // --- LEFT PANEL SWAP ---
            leftContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === `left-${targetTab}`) {
                    content.classList.add('active');
                }
            });

            // --- CENTER PANEL SWAP ---
            centerContents.forEach(content => {
                content.classList.remove('active');
                if (targetTab === 'reports' && content.id === 'center-reports') {
                    content.classList.add('active');
                } else if (targetTab !== 'reports' && content.id === 'center-map') {
                    content.classList.add('active');
                }
            });

            // --- RIGHT PANEL SWAP ---
            rightContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === `right-${targetTab}`) {
                    content.classList.add('active');
                } else if (targetTab === 'global' && content.id === 'right-seismic') {
                    // Keep GLOBAL behavior identical to previous version: show seismic panel.
                    content.classList.add('active');
                }
            });

            // --- SVG MAP MARKER FILTERING ---
            mapMarkers.forEach(marker => {
                const markerCategory = marker.getAttribute('data-category');
                if (targetTab === 'global' || targetTab === 'network') {
                    // Show all markers in overview tabs
                    marker.style.display = 'block';
                } else {
                    // Filter markers by active category
                    if (markerCategory === targetTab) {
                        marker.style.display = 'block';
                    } else {
                        marker.style.display = 'none';
                    }
                }
            });

            setMapCategory(targetTab);
        });
    });

    // Handle Report link clicks to open specific reports
    const reportLinks = document.querySelectorAll('.report-item');
    reportLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Optional: highlight selected report in the list
            reportLinks.forEach(l => {
                l.classList.remove('bg-seismic', 'text-black', 'bg-white');
                l.classList.add('bg-white'); // default state
            });
            link.classList.remove('bg-white');
            link.classList.add('bg-seismic');
            link.classList.replace('text-white', 'text-black');
        });
    });
});
