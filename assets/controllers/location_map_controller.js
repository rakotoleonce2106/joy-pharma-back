import { Controller } from '@hotwired/stimulus';

/*
 * Location Map Controller
 * Handles Leaflet map integration for location selection
 */
export default class extends Controller {
    static targets = ['map', 'address', 'latitude', 'longitude'];
    
    map = null;
    marker = null;

    connect() {
        // Wait for Leaflet to be loaded
        if (typeof L === 'undefined') {
            // Load Leaflet if not already loaded
            this.loadLeaflet().then(() => {
                this.initializeMap();
            });
        } else {
            this.initializeMap();
        }
    }

    async loadLeaflet() {
        // Load Leaflet CSS
        if (!document.querySelector('link[href*="leaflet.css"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            link.crossOrigin = '';
            document.head.appendChild(link);
        }

        // Load Leaflet JS
        if (!window.L) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                script.crossOrigin = '';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
    }

    initializeMap() {
        if (!this.hasMapTarget) return;

        // Default to Antananarivo, Madagascar
        const defaultLat = -18.8792;
        const defaultLng = 47.5079;
        
        // Get initial coordinates from inputs or use default
        const lat = parseFloat(this.latitudeTarget.value) || defaultLat;
        const lng = parseFloat(this.longitudeTarget.value) || defaultLng;

        // Initialize map
        this.map = L.map(this.mapTarget).setView([lat, lng], 13);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Add marker if coordinates exist
        if (this.latitudeTarget.value && this.longitudeTarget.value) {
            this.addMarker(lat, lng);
        }

        // Handle map clicks
        this.map.on('click', (e) => {
            this.onMapClick(e);
        });

        // Handle address search
        if (this.hasAddressTarget) {
            this.addressTarget.addEventListener('blur', () => {
                this.geocodeAddress();
            });
        }
    }

    onMapClick(e) {
        const { lat, lng } = e.latlng;
        
        // Update form fields
        this.latitudeTarget.value = lat.toFixed(6);
        this.longitudeTarget.value = lng.toFixed(6);

        // Add/update marker
        this.addMarker(lat, lng);

        // Reverse geocode to get address
        this.reverseGeocode(lat, lng);
    }

    addMarker(lat, lng) {
        // Remove existing marker
        if (this.marker) {
            this.map.removeLayer(this.marker);
        }

        // Add new marker
        this.marker = L.marker([lat, lng], {
            draggable: true
        }).addTo(this.map);

        // Handle marker drag
        this.marker.on('dragend', (e) => {
            const { lat, lng } = e.target.getLatLng();
            this.latitudeTarget.value = lat.toFixed(6);
            this.longitudeTarget.value = lng.toFixed(6);
            this.reverseGeocode(lat, lng);
        });
    }

    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
            );
            const data = await response.json();
            
            if (data.display_name) {
                this.addressTarget.value = data.display_name;
            }
        } catch (error) {
            console.error('Reverse geocoding failed:', error);
        }
    }

    async geocodeAddress() {
        const address = this.addressTarget.value.trim();
        if (!address) return;

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`
            );
            const data = await response.json();
            
            if (data.length > 0) {
                const { lat, lon } = data[0];
                const latitude = parseFloat(lat);
                const longitude = parseFloat(lon);
                
                // Update form fields
                this.latitudeTarget.value = latitude.toFixed(6);
                this.longitudeTarget.value = longitude.toFixed(6);
                
                // Update map view and marker
                this.map.setView([latitude, longitude], 15);
                this.addMarker(latitude, longitude);
            }
        } catch (error) {
            console.error('Geocoding failed:', error);
        }
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
}
