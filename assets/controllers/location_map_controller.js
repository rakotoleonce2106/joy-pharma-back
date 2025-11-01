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
        console.log('Location Map Controller: Connected', {
            hasMapTarget: this.hasMapTarget,
            mapTarget: this.mapTarget?.id || 'none',
            hasAddressTarget: this.hasAddressTarget,
            hasLatitudeTarget: this.hasLatitudeTarget,
            hasLongitudeTarget: this.hasLongitudeTarget,
            element: this.element.className,
            mapTargetDimensions: this.hasMapTarget ? `${this.mapTarget.offsetWidth}x${this.mapTarget.offsetHeight}` : 'N/A'
        });

        // Listen for refresh events
        const refreshHandler = () => {
            this.refresh();
        };
        document.addEventListener('location-map:refresh', refreshHandler);
        this.refreshHandler = refreshHandler;

        // Also listen for Turbo load events
        const turboHandler = () => {
            setTimeout(() => this.tryInitialize(), 200);
        };
        document.addEventListener('turbo:load', turboHandler);
        this.turboHandler = turboHandler;

        // Initialize with staggered attempts to avoid race conditions
        // Use a flag to prevent multiple simultaneous initializations
        this._initializing = false;
        this._initTimeouts = [];
        
        const initDelays = [100, 300, 600, 1000, 2000];
        initDelays.forEach((delay, index) => {
            const timeout = setTimeout(() => {
                if (!this._initializing && !this.map) {
                    this.tryInitialize();
                }
            }, delay);
            this._initTimeouts.push(timeout);
        });
    }

    async tryInitialize() {
        // Prevent multiple simultaneous initializations
        if (this._initializing) {
            console.log('Location Map Controller: Already initializing, skipping...');
            return;
        }

        if (!this.hasMapTarget) {
            console.warn('Location Map Controller: Map target not found');
            return;
        }

        const container = this.mapTarget;
        console.log('Location Map Controller: Checking container', {
            offsetWidth: container.offsetWidth,
            offsetHeight: container.offsetHeight,
            isVisible: container.offsetWidth > 0 && container.offsetHeight > 0,
            hasMap: !!this.map
        });

        // If map already exists, just refresh it
        if (this.map) {
            setTimeout(() => {
                if (this.map && container.offsetWidth > 0 && container.offsetHeight > 0) {
                    this.map.invalidateSize();
                }
            }, 100);
            return;
        }

        // Set initialization flag
        this._initializing = true;

        // Wait for Leaflet to be available
        if (typeof L === 'undefined') {
            // Load Leaflet if not available
            try {
                await this.loadLeaflet();
            } catch (error) {
                console.error('Location Map Controller: Failed to load Leaflet', error);
                this._initializing = false;
                return;
            }
        }

        // Use IntersectionObserver to detect when map container becomes visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                console.log('Location Map Controller: IntersectionObserver triggered', {
                    isIntersecting: entry.isIntersecting,
                    hasMap: !!this.map,
                    intersectionRatio: entry.intersectionRatio
                });

                if (entry.isIntersecting && !this.map) {
                    // Map container is now visible, initialize if not already done
                    this.initialize();
                    observer.disconnect();
                } else if (entry.isIntersecting && this.map) {
                    // Map is visible, ensure it's properly sized
                    setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 100);
                }
            });
        }, {
            threshold: 0.01 // Lower threshold to catch partial visibility
        });

        // Observe the map container and its parents
        if (this.hasMapTarget) {
            observer.observe(this.mapTarget);
            
            // Also observe parent elements to catch tab switches
            let parent = this.mapTarget.parentElement;
            let depth = 0;
            while (parent && depth < 3) {
                observer.observe(parent);
                parent = parent.parentElement;
                depth++;
            }
        }

        // If container is already visible, initialize immediately
        if (container.offsetWidth > 0 && container.offsetHeight > 0 && !this.map) {
            console.log('Location Map Controller: Container visible, initializing immediately');
            await this.initialize();
        }
        
        // Reset initialization flag after a delay to allow for future retries
        setTimeout(() => {
            this._initializing = false;
        }, 500);
    }

    async initialize() {
        if (this.map) {
            console.log('Location Map Controller: Map already initialized');
            return;
        }

        if (typeof L === 'undefined') {
            console.log('Location Map Controller: Loading Leaflet...');
            try {
                await this.loadLeaflet();
                console.log('Location Map Controller: Leaflet loaded successfully');
            } catch (error) {
                console.error('Location Map Controller: Failed to load Leaflet', error);
                return;
            }
        }

        this.initializeMap();
    }

    async loadLeaflet() {
        // Load Leaflet CSS
        if (!document.querySelector('link[href*="leaflet.css"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
            
            // Fix Leaflet marker icon paths (common issue)
            const styleFix = document.createElement('style');
            styleFix.textContent = `
                .leaflet-container {
                    height: 100%;
                    width: 100%;
                }
                .leaflet-default-icon-path {
                    background-image: url(https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png);
                }
                .leaflet-retina .leaflet-default-icon-path {
                    background-image: url(https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png);
                }
            `;
            document.head.appendChild(styleFix);
        }

        // Load Leaflet JS
        if (!window.L) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.crossOrigin = 'anonymous';
                script.onload = () => {
                    // Fix Leaflet icon paths after loading
                    if (window.L && window.L.Icon && window.L.Icon.Default) {
                        delete window.L.Icon.Default.prototype._getIconUrl;
                        window.L.Icon.Default.mergeOptions({
                            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        });
                    }
                    // Wait a bit for Leaflet to fully initialize
                    setTimeout(() => {
                        console.log('Location Map Controller: Leaflet loaded and configured');
                        resolve();
                    }, 100);
                };
                script.onerror = () => {
                    console.error('Failed to load Leaflet library');
                    reject(new Error('Failed to load Leaflet library'));
                };
                document.head.appendChild(script);
            });
        }
        return Promise.resolve();
    }

    initializeMap() {
        if (!this.hasMapTarget) {
            console.warn('Location Map Controller: Map target not found');
            return;
        }

        if (typeof L === 'undefined') {
            console.error('Location Map Controller: Leaflet library not loaded');
            return;
        }

        // Check if map container has dimensions
        const container = this.mapTarget;
        
        // Ensure container has height if not set
        if (!container.style.height && container.offsetHeight === 0) {
            container.style.height = '400px';
            container.style.minHeight = '400px';
        }

        if (container.offsetWidth === 0 || container.offsetHeight === 0) {
            console.log('Location Map Controller: Container has no dimensions, retrying...', {
                width: container.offsetWidth,
                height: container.offsetHeight,
                computedHeight: window.getComputedStyle(container).height
            });
            // Wait for layout to complete
            setTimeout(() => this.initializeMap(), 200);
            return;
        }

        // Default to Antananarivo, Madagascar
        const defaultLat = -18.8792;
        const defaultLng = 47.5079;
        
        // Get initial coordinates from inputs or use default
        const lat = parseFloat(this.latitudeTarget?.value) || defaultLat;
        const lng = parseFloat(this.longitudeTarget?.value) || defaultLng;

        console.log('Location Map Controller: Initializing map', {
            lat,
            lng,
            containerWidth: container.offsetWidth,
            containerHeight: container.offsetHeight
        });

        try {
            // Clear any loading content but keep structure
            const loadingElement = container.querySelector('[data-location-map-loading="true"]');
            if (loadingElement) {
                loadingElement.remove();
            }

            // Initialize map
            this.map = L.map(this.mapTarget, {
                preferCanvas: false,
                zoomControl: true,
                attributionControl: true
            }).setView([lat, lng], 13);

            console.log('Location Map Controller: Map created successfully', {
                containerId: container.id,
                mapSize: container.offsetWidth + 'x' + container.offsetHeight
            });

            // Add OpenStreetMap tiles with error handling
            const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                errorTileUrl: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
            });

            tileLayer.on('tileerror', (error) => {
                console.warn('Location Map Controller: Tile load error', error);
            });

            tileLayer.addTo(this.map);

            console.log('Location Map Controller: Tiles added');

            // Add marker if coordinates exist
            if (this.latitudeTarget?.value && this.longitudeTarget?.value) {
                this.addMarker(lat, lng);
            }

            // Handle map clicks
            this.map.on('click', (e) => {
                this.onMapClick(e);
            });

            // Handle address search - both Enter key and blur
            if (this.hasAddressTarget) {
                this.addressTarget.addEventListener('blur', () => {
                    if (this.addressTarget.value.trim()) {
                        this.geocodeAddress();
                    }
                });
                
                this.addressTarget.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (this.addressTarget.value.trim()) {
                            this.geocodeAddress();
                        }
                    }
                });
            }

            // Invalidate map size after a brief delay to ensure proper rendering
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                    console.log('Location Map Controller: Map size invalidated');
                }
            }, 300);

            // Also invalidate when the container becomes visible (e.g., tab switching)
            const observer = new MutationObserver(() => {
                if (this.map && container.offsetWidth > 0 && container.offsetHeight > 0) {
                    setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 100);
                }
            });

            // Observe changes to parent visibility
            if (container.parentElement) {
                observer.observe(container.parentElement, {
                    attributes: true,
                    attributeFilter: ['class', 'style', 'hidden'],
                    childList: false,
                    subtree: true
                });
            }

            // Handle window resize
            this.resizeHandler = () => {
                if (this.map) {
                    setTimeout(() => {
                        this.map.invalidateSize();
                    }, 100);
                }
            };
            window.addEventListener('resize', this.resizeHandler);

            console.log('Location Map Controller: Map initialized successfully');
        } catch (error) {
            console.error('Location Map Controller: Error initializing map', error);
            // Show error message to user
            const errorDiv = document.createElement('div');
            errorDiv.className = 'p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-200';
            errorDiv.innerHTML = '<strong>Map Error:</strong> ' + error.message + '. Please refresh the page.';
            container.parentElement.insertBefore(errorDiv, container);
        }
    }

    onMapClick(e) {
        const { lat, lng } = e.latlng;
        
        // Update form fields
        if (this.hasLatitudeTarget) {
            this.latitudeTarget.value = lat.toFixed(6);
        }
        if (this.hasLongitudeTarget) {
            this.longitudeTarget.value = lng.toFixed(6);
        }

        // Add/update marker
        this.addMarker(lat, lng);

        // Reverse geocode to get address
        if (this.hasAddressTarget) {
            this.reverseGeocode(lat, lng);
        }
    }

    addMarker(lat, lng) {
        if (!this.map) return;

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
            if (this.hasLatitudeTarget) {
                this.latitudeTarget.value = lat.toFixed(6);
            }
            if (this.hasLongitudeTarget) {
                this.longitudeTarget.value = lng.toFixed(6);
            }
            if (this.hasAddressTarget) {
                this.reverseGeocode(lat, lng);
            }
        });
    }

    async reverseGeocode(lat, lng) {
        if (!this.hasAddressTarget) return;

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                {
                    headers: {
                        'User-Agent': 'JoyPharma Location Selector'
                    }
                }
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
        if (!this.hasAddressTarget) return;
        
        const address = this.addressTarget.value.trim();
        if (!address) return;

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`,
                {
                    headers: {
                        'User-Agent': 'JoyPharma Location Selector'
                    }
                }
            );
            const data = await response.json();
            
            if (data.length > 0) {
                const { lat, lon } = data[0];
                const latitude = parseFloat(lat);
                const longitude = parseFloat(lon);
                
                // Update form fields
                if (this.hasLatitudeTarget) {
                    this.latitudeTarget.value = latitude.toFixed(6);
                }
                if (this.hasLongitudeTarget) {
                    this.longitudeTarget.value = longitude.toFixed(6);
                }
                
                // Update map view and marker
                if (this.map) {
                    this.map.setView([latitude, longitude], 15);
                    this.addMarker(latitude, longitude);
                }
            }
        } catch (error) {
            console.error('Geocoding failed:', error);
        }
    }

    refresh() {
        // Public method to refresh the map (useful for tab switching)
        if (this.map && this.hasMapTarget) {
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                    // Recenter map if coordinates exist
                    if (this.latitudeTarget?.value && this.longitudeTarget?.value) {
                        const lat = parseFloat(this.latitudeTarget.value);
                        const lng = parseFloat(this.longitudeTarget.value);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            this.map.setView([lat, lng], this.map.getZoom());
                        }
                    }
                }
            }, 150);
        }
    }

    disconnect() {
        // Clear all timeouts
        if (this._initTimeouts) {
            this._initTimeouts.forEach(timeout => clearTimeout(timeout));
            this._initTimeouts = [];
        }
        
        if (this.refreshHandler) {
            document.removeEventListener('location-map:refresh', this.refreshHandler);
        }
        
        if (this.turboHandler) {
            document.removeEventListener('turbo:load', this.turboHandler);
        }
        
        if (this.resizeHandler) {
            window.removeEventListener('resize', this.resizeHandler);
        }
        
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        
        this._initializing = false;
        this.marker = null;
    }
}
