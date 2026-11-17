function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

export class OutageMapAdapter {
    constructor(elementId, options = {}) {
        this.elementId = elementId;
        this.container = typeof elementId === 'string' ? document.getElementById(elementId) : elementId;
        if (!this.container) return;

        this.options = {
            defaultLat: parseFloat(this.container.dataset.lat || options.defaultLat || 10.492),
            defaultLng: parseFloat(this.container.dataset.lng || options.defaultLng || -66.880),
            defaultZoom: parseInt(this.container.dataset.zoom || options.defaultZoom || 12, 10),
            endpoint: options.endpoint || '/api/v1/outages',
            pollingIntervalMs: parseInt(this.container.dataset.poll || options.pollingIntervalMs || 30000, 10),
            interactive: options.interactive !== false,
            onCountChange: options.onCountChange || null,
            ...options
        };

        this.map = null;
        this.layersGroup = null;
        this.pollTimer = null;
        this.activeCircles = new Map();

        this.init();
    }

    init() {
        if (!window.L) {
            console.warn('[OutageMap] Leaflet (L) is not loaded. Waiting for Leaflet...');
            setTimeout(() => this.init(), 100);
            return;
        }

        if (this.map) return;

        this.map = L.map(this.container, {
            center: [this.options.defaultLat, this.options.defaultLng],
            zoom: this.options.defaultZoom,
            zoomControl: this.options.interactive,
            dragging: this.options.interactive,
            scrollWheelZoom: this.options.interactive ? 'center' : false,
            doubleClickZoom: this.options.interactive,
            touchZoom: this.options.interactive,
        });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        this.layersGroup = L.layerGroup().addTo(this.map);

        const debouncedFetch = debounce(() => this.fetchVisibleOutages(), 300);
        this.map.on('moveend', debouncedFetch);
        this.map.on('zoomend', debouncedFetch);

        this.fetchVisibleOutages();

        if (this.options.pollingIntervalMs > 0) {
            this.pollTimer = setInterval(() => this.fetchVisibleOutages(), this.options.pollingIntervalMs);
        }
    }

    fetchVisibleOutages() {
        if (!this.map) return;

        const bounds = this.map.getBounds();
        const minLat = bounds.getSouth();
        const minLng = bounds.getWest();
        const maxLat = bounds.getNorth();
        const maxLng = bounds.getEast();

        const url = `${this.options.endpoint}?min_lat=${minLat.toFixed(6)}&min_lng=${minLng.toFixed(6)}&max_lat=${maxLat.toFixed(6)}&max_lng=${maxLng.toFixed(6)}`;

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            return res.json();
        })
        .then(payload => {
            const outages = payload.data || [];
            this.renderOutages(outages);
            if (typeof this.options.onCountChange === 'function') {
                this.options.onCountChange(outages.length);
            }
        })
        .catch(err => {
            console.error('[OutageMap] Error loading outages:', err);
        });
    }

    renderOutages(outages) {
        if (!this.layersGroup) return;

        this.layersGroup.clearLayers();
        this.activeCircles.clear();

        outages.forEach(outage => {
            const isOwner = Boolean(outage.is_owner);
            const radiusMeters = (outage.radius_km || 1.0) * 1000;

            const circle = L.circle([outage.latitude, outage.longitude], {
                radius: radiusMeters,
                color: isOwner ? '#2563EB' : '#E5484D',
                fillColor: isOwner ? '#3B82F6' : '#E5484D',
                fillOpacity: isOwner ? 0.35 : 0.25,
                weight: isOwner ? 3 : 2,
                dashArray: isOwner ? '6, 4' : null,
            });

            const createdDate = outage.created_at ? new Date(outage.created_at) : null;
            const createdFormatted = createdDate ? createdDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : null;
            const expirationDate = outage.expires_at ? new Date(outage.expires_at) : null;
            const expFormatted = expirationDate ? expirationDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—';
            const durationLabel = outage.duration_type === 'fixed' 
                ? `${outage.duration_hours || '—'}h fixed`
                : 'Until resolved';

            const popupContent = `
                <div class="p-1 font-sans text-xs text-gray-800" style="min-width: 180px;">
                    <div class="flex items-center justify-between gap-2 mb-1.5 pb-1 border-b border-gray-100">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-700">
                            Active Outage
                        </span>
                        ${isOwner ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-700">Your Report</span>' : ''}
                    </div>
                    <div class="space-y-1 text-gray-600">
                        <div><strong>Radius:</strong> ${outage.radius_km} km</div>
                        <div><strong>Duration:</strong> ${durationLabel}</div>
                        ${createdFormatted ? `<div><strong>Reported:</strong> ${createdFormatted}</div>` : ''}
                        <div><strong>Est. Expiration:</strong> ${expFormatted}</div>
                    </div>
                    ${outage.description ? `
                        <div class="mt-2 pt-1 border-t border-gray-100 text-[11px] text-gray-700 italic">
                            "${escapeHtml(outage.description)}"
                        </div>
                    ` : ''}
                </div>
            `;

            circle.bindPopup(popupContent, { maxWidth: 280 });
            this.layersGroup.addLayer(circle);
            this.activeCircles.set(outage.id, circle);
        });
    }

    destroy() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
}

export class OutagePickerAdapter {
    constructor(elementId, options = {}) {
        this.elementId = elementId;
        this.container = typeof elementId === 'string' ? document.getElementById(elementId) : elementId;
        if (!this.container) return;

        this.options = {
            defaultLat: parseFloat(this.container.dataset.lat || options.defaultLat || 10.492),
            defaultLng: parseFloat(this.container.dataset.lng || options.defaultLng || -66.880),
            defaultZoom: parseInt(this.container.dataset.zoom || options.defaultZoom || 13, 10),
            initialRadiusKm: parseFloat(this.container.dataset.radius || options.initialRadiusKm || 2.0),
            onLocationChange: options.onLocationChange || null,
            ...options
        };

        this.map = null;
        this.marker = null;
        this.circle = null;

        this.init();
    }

    init() {
        if (!window.L) {
            console.warn('[OutagePicker] Leaflet (L) is not loaded. Waiting for Leaflet...');
            setTimeout(() => this.init(), 100);
            return;
        }

        if (this.map) return;

        this.map = L.map(this.container, {
            center: [this.options.defaultLat, this.options.defaultLng],
            zoom: this.options.defaultZoom,
        });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        this.marker = L.marker([this.options.defaultLat, this.options.defaultLng], {
            draggable: true
        }).addTo(this.map);

        this.circle = L.circle([this.options.defaultLat, this.options.defaultLng], {
            radius: this.options.initialRadiusKm * 1000,
            color: '#E5484D',
            fillColor: '#E5484D',
            fillOpacity: 0.25,
            weight: 2
        }).addTo(this.map);

        this.map.on('click', (e) => {
            this.setLocation(e.latlng.lat, e.latlng.lng);
        });

        this.marker.on('dragend', () => {
            const pos = this.marker.getLatLng();
            this.setLocation(pos.lat, pos.lng);
        });

        if (typeof this.options.onLocationChange === 'function') {
            this.options.onLocationChange({
                lat: this.options.defaultLat,
                lng: this.options.defaultLng
            });
        }
    }

    setLocation(lat, lng) {
        lat = parseFloat(Number(lat).toFixed(8));
        lng = parseFloat(Number(lng).toFixed(8));

        if (this.marker) {
            this.marker.setLatLng([lat, lng]);
        }
        if (this.circle) {
            this.circle.setLatLng([lat, lng]);
        }
        if (this.map) {
            this.map.panTo([lat, lng]);
        }
        if (typeof this.options.onLocationChange === 'function') {
            this.options.onLocationChange({ lat, lng });
        }
    }

    setRadius(radiusKm) {
        const radiusMeters = parseFloat(radiusKm) * 1000;
        if (this.circle && !isNaN(radiusMeters)) {
            this.circle.setRadius(radiusMeters);
        }
    }

    locateUser() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('unsupported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    this.setLocation(lat, lng);
                    if (this.map) {
                        this.map.panTo([lat, lng]);
                    }
                    resolve({ lat, lng });
                },
                (error) => {
                    reject(error);
                },
                { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
            );
        });
    }

    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
}

window.initOutageMap = function (elementId = 'outage-map', options = {}) {
    return new OutageMapAdapter(elementId, options);
};

window.initOutagePicker = function (elementId = 'picker-map', options = {}) {
    return new OutagePickerAdapter(elementId, options);
};

document.addEventListener('DOMContentLoaded', () => {
    const defaultEl = document.getElementById('outage-map');
    if (defaultEl && !defaultEl._outageMapInstance) {
        defaultEl._outageMapInstance = window.initOutageMap('outage-map');
    }

    const previewEl = document.getElementById('preview-map');
    if (previewEl && !previewEl._outageMapInstance) {
        previewEl._outageMapInstance = window.initOutageMap('preview-map', { interactive: true });
    }
});
