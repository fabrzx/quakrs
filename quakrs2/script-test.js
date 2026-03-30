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
        const worldOffsets = [];
        for (let offset = -WORLD_WRAP_RANGE; offset <= WORLD_WRAP_RANGE; offset += 1) {
            worldOffsets.push(offset);
        }

        const addLabel = (layer, labelText, className) => {
            layer.bindTooltip(labelText, {
                permanent: true,
                direction: 'right',
                offset: [10, 0],
                className: `map-point-label ${className}`
            });
        };

        function buildWrappedCircleMarker(baseLat, baseLng, options, labelText, className) {
            return L.layerGroup(worldOffsets.map((offset) => {
                const marker = L.circleMarker([baseLat, baseLng + (offset * 360)], options);
                addLabel(marker, labelText, className);
                return marker;
            }));
        }

        function buildWrappedIconMarker(baseLat, baseLng, iconOptions, labelText, className) {
            return L.layerGroup(worldOffsets.map((offset) => {
                const marker = L.marker([baseLat, baseLng + (offset * 360)], {
                    icon: L.divIcon(iconOptions)
                });
                addLabel(marker, labelText, className);
                return marker;
            }));
        }

        const seismicMain = buildWrappedCircleMarker(38.2, 142.5, {
            radius: 7,
            color: categoryStyles.seismic.color,
            fillColor: categoryStyles.seismic.fillColor,
            fillOpacity: 1,
            weight: 2
        }, 'M7.4 HONSHU', 'label-seismic');

        const seismicChile = buildWrappedCircleMarker(-33.1, -71.6, {
            radius: 6,
            color: categoryStyles.seismic.color,
            fillColor: categoryStyles.seismic.fillColor,
            fillOpacity: 1,
            weight: 2
        }, 'M6.8 VALPARAISO', 'label-seismic');

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
                seismicMain,
                seismicChile
            ]),
            volcanoes: L.layerGroup([volcanoKrakatoa, volcanoEtna]),
            tsunamis: L.layerGroup([tsunamiPacific])
        };
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
        const showAll = targetTab === 'global' || targetTab === 'network';

        Object.entries(mapState.instance.layers).forEach(([category, layer]) => {
            const shouldShow = showAll || category === targetTab;
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
                } else if (targetTab === 'global' && content.id === 'left-seismic') {
                    // Default to seismic left-panel if 'global map' overview is selected
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
                    // Default to seismic right-panel if 'global map' overview is selected
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
