import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['map', 'latitude', 'longitude', 'address'];
    static values = {
        lat: { type: Number, default: -18.8792 },
        lng: { type: Number, default: 47.5079 },
        zoom: { type: Number, default: 13 }
    };

    map = null;
    marker = null;

    connect() {
        // Wait for Leaflet to be loaded
        if (typeof L === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            script.onload = () => this.initializeMap();
            document.head.appendChild(script);

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            link.crossOrigin = '';
            document.head.appendChild(link);
        } else {
            this.initializeMap();
        }
    }

    initializeMap() {
        // Get initial coordinates from input fields if available
        const latInput = this.latitudeTarget.value;
        const lngInput = this.longitudeTarget.value;

        if (latInput && lngInput) {
            this.latValue = parseFloat(latInput);
            this.lngValue = parseFloat(lngInput);
        }

        // Initialize map
        this.map = L.map(this.mapTarget).setView([this.latValue, this.lngValue], this.zoomValue);

        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Custom marker icon
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
                          width: 40px; height: 40px; border-radius: 50%; 
                          border: 4px solid white; 
                          box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4), 0 2px 4px rgba(0,0,0,0.2); 
                          display: flex; align-items: center; justify-content: center; 
                          cursor: move;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" 
                           fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                          <circle cx="12" cy="10" r="3"/>
                      </svg>
                   </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        // Add draggable marker
        if (latInput && lngInput) {
            this.marker = L.marker([this.latValue, this.lngValue], {
                draggable: true,
                icon: customIcon
            }).addTo(this.map);

            this.marker.bindPopup('<b>Delivery Location</b><br>Drag to adjust position').openPopup();
        } else {
            this.marker = L.marker([this.latValue, this.lngValue], {
                draggable: true,
                icon: customIcon
            }).addTo(this.map);
        }

        // Update coordinates when marker is dragged
        this.marker.on('dragend', (event) => {
            const position = event.target.getLatLng();
            this.updateLocation(position.lat, position.lng);
        });

        // Add click handler to map
        this.map.on('click', (event) => {
            const { lat, lng } = event.latlng;
            this.marker.setLatLng([lat, lng]);
            this.updateLocation(lat, lng);
        });

        // Add search control button
        this.addSearchControl();

        // Try to get user's current location
        this.addCurrentLocationButton();
    }

    updateLocation(lat, lng) {
        this.latValue = lat;
        this.lngValue = lng;
        
        // Update hidden input fields
        this.latitudeTarget.value = lat.toFixed(8);
        this.longitudeTarget.value = lng.toFixed(8);

        // Reverse geocode to get address
        this.reverseGeocode(lat, lng);

        // Update marker popup
        this.marker.bindPopup(`
            <div style="font-family: system-ui, -apple-system, sans-serif; min-width: 200px;">
                <strong style="font-size: 14px; color: #1e293b;">Delivery Location</strong>
                <div style="margin-top: 8px; font-size: 12px; color: #64748b;">
                    <div>📍 Lat: ${lat.toFixed(6)}</div>
                    <div>📍 Lng: ${lng.toFixed(6)}</div>
                </div>
            </div>
        `).openPopup();
    }

    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
            );
            const data = await response.json();
            
            if (data.display_name && this.hasAddressTarget) {
                this.addressTarget.value = data.display_name;
            }
        } catch (error) {
            console.error('Reverse geocoding failed:', error);
        }
    }

    addSearchControl() {
        const searchControl = L.control({ position: 'topright' });
        
        searchControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = `
                <div style="background: white; padding: 8px; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                    <input type="text" 
                           id="location-search" 
                           placeholder="Search location..."
                           style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; width: 200px; font-size: 12px;"
                    />
                    <button type="button" 
                            id="search-btn"
                            style="margin-left: 4px; padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                        Search
                    </button>
                </div>
            `;
            
            // Prevent map click events on the control
            L.DomEvent.disableClickPropagation(div);
            
            return div;
        };
        
        searchControl.addTo(this.map);

        // Add search functionality
        setTimeout(() => {
            const searchBtn = document.getElementById('search-btn');
            const searchInput = document.getElementById('location-search');
            
            if (searchBtn && searchInput) {
                searchBtn.addEventListener('click', () => this.searchLocation(searchInput.value));
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.searchLocation(searchInput.value);
                    }
                });
            }
        }, 100);
    }

    async searchLocation(query) {
        if (!query) return;

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`
            );
            const data = await response.json();
            
            if (data && data.length > 0) {
                const { lat, lon } = data[0];
                const latNum = parseFloat(lat);
                const lngNum = parseFloat(lon);
                
                this.map.setView([latNum, lngNum], 15);
                this.marker.setLatLng([latNum, lngNum]);
                this.updateLocation(latNum, lngNum);
            } else {
                alert('Location not found. Please try another search term.');
            }
        } catch (error) {
            console.error('Geocoding failed:', error);
            alert('Search failed. Please try again.');
        }
    }

    addCurrentLocationButton() {
        const locationControl = L.control({ position: 'topright' });
        
        locationControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = `
                <button type="button" 
                        id="current-location-btn"
                        title="Use my current location"
                        style="background: white; width: 34px; height: 34px; border: 2px solid rgba(0,0,0,0.2); 
                               border-radius: 4px; cursor: pointer; display: flex; align-items: center; 
                               justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" 
                         fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            `;
            
            L.DomEvent.disableClickPropagation(div);
            
            return div;
        };
        
        locationControl.addTo(this.map);

        setTimeout(() => {
            const btn = document.getElementById('current-location-btn');
            if (btn) {
                btn.addEventListener('click', () => this.getCurrentLocation());
            }
        }, 100);
    }

    getCurrentLocation() {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    this.map.setView([lat, lng], 15);
                    this.marker.setLatLng([lat, lng]);
                    this.updateLocation(lat, lng);
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    alert('Unable to get your location. Please ensure location services are enabled.');
                }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
}

