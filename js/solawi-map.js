// LISTENER for Single Page Depot List clicks (GLOBAL - always active)
// Using capture phase to catch clicks BEFORE other event handlers
document.addEventListener('click', function(e) {
    const trigger = e.target.closest('.solawi-map-focus-trigger');
    if (trigger) {
        e.preventDefault();
        const depotId = trigger.getAttribute('data-depot-id');
        if (depotId) {
            // Dispatch event to map (filter-update)
            const event = new CustomEvent('solawi-filter-update', {
                detail: {
                    search: '',
                    terms: [],
                    depot: depotId
                }
            });
            document.dispatchEvent(event);
            
            // Scroll to map
            const mapEl = document.getElementById('solawi-map');
            if (mapEl) {
                mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }
}, true); // Capture phase - runs BEFORE other event listeners

document.addEventListener('DOMContentLoaded', function() {
    // Check if the map container and data exist
    if ( !document.getElementById('solawi-map') || typeof solawiMapData === 'undefined' ) {
        return;
    }

    // Data from PHP (via wp_localize_script)
    const locations = solawiMapData.locations;
    const mapCenter = solawiMapData.mapCenter;

    // Initialize map
    const map = L.map('solawi-map', {
        minZoom: 8,
        maxZoom: 16
    }).setView(mapCenter, 11);

    // 1. Standardmäßig das Mausrad deaktivieren
    map.scrollWheelZoom.disable();

    // 2. Wenn auf die Karte geklickt wird -> Mausrad aktivieren
    map.on('click', function() {
        if (map.scrollWheelZoom.enabled()) {
            return;
        }
        map.scrollWheelZoom.enable();
    });

    // 3. Wenn die Maus die Karte verlässt -> Mausrad wieder deaktivieren
    map.on('mouseout', function() {
        map.scrollWheelZoom.disable();
    });

    // Load Local Tiles
    const tileUrl = solawiMapData.tileUrl || 'assets/tiles/{z}/{x}/{y}.png';
    L.tileLayer(tileUrl, {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        minZoom: 8,
        maxNativeZoom: 15,
        maxZoom: 16
    }).addTo(map);

    // Optional: Set bounds to prevent panning outside the tile area
    // Based on Freiburg region
    // Based on Freiburg region - widened to allow more panning
    const southWest = L.latLng(47.5, 7.0);
    const northEast = L.latLng(48.5, 9.0);
    const bounds = L.latLngBounds(southWest, northEast);
    map.setMaxBounds(bounds);
    map.on('drag', function() {
        map.panInsideBounds(bounds, { animate: false });
    });

    // Marker loop
    const markers = [];
    locations.forEach(function(loc) {
        
        // Build popup content
        let popupContent = '<div class="solawi-map-popup">';
        popupContent += '<strong>' + loc.name + '</strong>';
        if(loc.zeiten) {
            popupContent += '<br><small>Anlieferung: ' + loc.zeiten + '</small><hr>';
        }

        // List of Solawis at this location
        if (loc.solawis.length > 0) {
            popupContent += '<ul>';
            loc.solawis.forEach(function(solawi) {
                popupContent += '<li>';
                if(solawi.logo) {
                    popupContent += '<img src="' + solawi.logo + '" alt="Logo von ' + solawi.name + '">';
                }
                popupContent += '<a href="' + solawi.url + '" target="_blank" rel="noopener">' + solawi.name + '</a>';
                popupContent += '</li>';
            });
            popupContent += '</ul>';
        }
        popupContent += '</div>';

        // Custom Icon mit der Status-Klasse aus PHP erstellen
        const pinTypeClass = 'pin-type-' + (loc.type || 'depot');
        const customIcon = L.divIcon({
            className: 'custom-pin-marker ' + (loc.status || 'pin-default') + ' ' + pinTypeClass,
            iconSize: [20, 20]
        });

        const marker = L.marker([loc.lat, loc.lng], { 
            icon: customIcon,
            title: loc.name,
            alt: loc.name
        });

        // Accessibility and Custom Styles
        marker.on('add', function() {
            const el = this.getElement();
            if (el) {
                // Set accessible name for screen readers
                let labelPrefix = 'Verteilpunkt: ';
                if (loc.type === 'hof') labelPrefix = 'Solawi: ';
                if (loc.type === 'laden') labelPrefix = 'Bioladen: ';
                if (loc.type === 'kiste') labelPrefix = 'Biokiste: ';
                el.setAttribute('aria-label', labelPrefix + loc.name);

                // Apply custom color if available (overrides status color)
                if (loc.color) {
                    el.style.setProperty('--pin-color', loc.color);
                    // For legacy support or if CSS variable isn't used everywhere
                    el.style.backgroundColor = loc.color;
                    el.style.borderColor = '#ffffff';
                    el.style.boxShadow = '0 0 0 2px ' + loc.color + ', 0 4px 8px rgba(0, 0, 0, 0.4)';
                }
            }
        });

        marker.addTo(map).bindPopup(popupContent);
        markers.push(marker);
    });

    // Auto-zoom to fit markers (excluding outlier Depot 148)
    if (markers.length > 0) {
        const markersToFit = markers.filter(m => {
            // Check if post ID (stored in options or separate data attribute if available) is 148
            // We need to look up the ID from the locations array based entirely on index since map markers don't carry the WP ID directly in this loop structure easily.
            // BETTER: modify marker creation above to store ID.
            return true; 
        });
        
        // Actually, let's filter based on location data since markers array aligns with locations array
        const relevantMarkers = markers.filter((m, i) => {
            const locId = String(locations[i].id);
            // Ausreißer ignorieren für den initialen Zoom (z.B. weit entfernte Standorte)
            return locId !== '148' && locId !== '448';
        });

        if (relevantMarkers.length > 0) {
            const group = L.featureGroup(relevantMarkers);
            map.fitBounds(group.getBounds().pad(0.05));
        } else {
             // Fallback if ONLY 148 exists or empty
             const group = L.featureGroup(markers);
             map.fitBounds(group.getBounds().pad(0.05));
        }
    }

    // LISTENER: Filter Map Markers based on Grid Search
    document.addEventListener('solawi-filter-update', function(e) {
        const filter = e.detail; // { search: '...', terms: ['10', '12'], depot: '...' }
        const searchLower = filter.search.toLowerCase();
        
        locations.forEach(function(loc, index) {
            const marker = markers[index]; // Markers are pushed in same order as locations
            let isVisible = false;

            // 0. Depot Filter (Pre-Check)
            // If a specific depot is selected, we only bother checking this location if it matches.
            if (filter.depot && filter.depot !== '' && String(loc.id) !== String(filter.depot)) {
                isVisible = false;
            } else {
                // Check if ANY Solawi at this location matches the filters
                for (let i = 0; i < loc.solawis.length; i++) {
                    const solawi = loc.solawis[i];
                    let matchesSearch = true;
                    let matchesTerms = true;

                    // 1. Solawi Search (Dropdown ID)
                    if (filter.search && filter.search !== '') {
                        if (String(solawi.id) !== String(filter.search)) {
                            matchesSearch = false;
                        }
                    }

                    // 2. Product Categories (Terms)
                    if (filter.terms && filter.terms.length > 0) {
                        const solawiCats = (solawi.cats || []).map(String);
                        const hasIntersection = filter.terms.some(termId => solawiCats.includes(termId));
                        if (!hasIntersection) {
                            matchesTerms = false;
                        }
                    }

                    // If this solawi matches filters, the location is valid
                    if (matchesSearch && matchesTerms) {
                        isVisible = true;
                        break; 
                    }
                }
            }
            
            // Apply Visibility
            if (isVisible) {
                if (!map.hasLayer(marker)) {
                    map.addLayer(marker);
                }
            } else {
                if (map.hasLayer(marker)) {
                    marker.closePopup();
                    map.removeLayer(marker);
                }
            }
        });

        // If we filtered by depot specifically, open the popup for that depot
        if (filter.depot && filter.depot !== '') {
            setTimeout(() => {
                locations.forEach((loc, index) => {
                    if (String(loc.id) === String(filter.depot) && map.hasLayer(markers[index])) {
                        markers[index].openPopup();
                    }
                });
            }, 300);
        }
    });

    // Note: Popup opening is now handled via the solawi-filter-update listener above
    // after the map has been filtered to show only the selected depot

});