# Location Map Integration with Leaflet

This document describes the implementation of Leaflet map integration for location selection in the order creation form.

## Overview

The location selection system has been enhanced with an interactive Leaflet map that allows:
- Clicking on the map to select a delivery location
- Dragging markers to adjust the position
- Automatic address lookup via reverse geocoding
- Manual address entry with geocoding search
- Optional location (can be left empty for pickup orders)

## Changes Made

### 1. Fixed PropertyAccessor Error

**Problem**: When selecting a customer in the admin order form, a `PropertyAccessor` error occurred when trying to access `location.address` on a null or empty Location object.

**Solution**: Implemented multiple fixes:

#### A. Updated LocationType Form
**File**: `src/Form/LocationType.php`

- Made all fields optional (`required => false`)
- Added `empty_data => null` to return null instead of empty Location object
- Added data attributes for Stimulus controller integration
- Added `NullLocationTransformer` to convert empty Location objects to null

#### B. Created NullLocationTransformer
**File**: `src/Form/DataTransformer/NullLocationTransformer.php`

```php
class NullLocationTransformer implements DataTransformerInterface
{
    public function reverseTransform($location): ?Location
    {
        // If all location fields are empty, return null instead of empty object
        if (!$location instanceof Location) {
            return null;
        }

        if (empty($location->getAddress()) && 
            ($location->getLatitude() === null || $location->getLatitude() === 0.0) && 
            ($location->getLongitude() === null || $location->getLongitude() === 0.0)) {
            return null;
        }

        return $location;
    }
}
```

This transformer ensures that when location fields are empty, the form returns `null` instead of an empty `Location` object, preventing PropertyAccessor errors.

### 2. Leaflet Map Integration

#### A. Created Location Map Controller
**File**: `assets/controllers/location_map_controller.js`

A Stimulus controller that:
- Dynamically loads Leaflet CSS and JavaScript
- Initializes the map centered on Antananarivo, Madagascar (default)
- Places a marker at existing coordinates (if available)
- Handles map clicks to set location
- Allows dragging markers to adjust position
- Performs reverse geocoding to get address from coordinates
- Performs geocoding to search for addresses

**Key Features**:
```javascript
export default class extends Controller {
    static targets = ['map', 'address', 'latitude', 'longitude'];
    
    // Map click handler
    onMapClick(e) {
        const { lat, lng } = e.latlng;
        this.latitudeTarget.value = lat.toFixed(6);
        this.longitudeTarget.value = lng.toFixed(6);
        this.addMarker(lat, lng);
        this.reverseGeocode(lat, lng);
    }

    // Reverse geocoding (coordinates to address)
    async reverseGeocode(lat, lng) {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
        );
        const data = await response.json();
        if (data.display_name) {
            this.addressTarget.value = data.display_name;
        }
    }

    // Forward geocoding (address to coordinates)
    async geocodeAddress() {
        const address = this.addressTarget.value.trim();
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`
        );
        // Update map and form fields...
    }
}
```

#### B. Created Location Map Component
**File**: `templates/components/admin/location-with-map.html.twig`

A Twig component that:
- Renders the Leaflet map container
- Displays location form fields (address, latitude, longitude)
- Provides user instructions
- Integrates with the Stimulus controller

```twig
<div {{ attributes }} data-controller="location-map">
    <div class="space-y-4">
        {# Map Container #}
        <div class="rounded-lg overflow-hidden border">
            <div 
                data-location-map-target="map" 
                style="height: 400px;"
            ></div>
        </div>

        {# Location Form Fields #}
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                {{ form_row(locationForm.address) }}
            </div>
            <div>{{ form_row(locationForm.latitude) }}</div>
            <div>{{ form_row(locationForm.longitude) }}</div>
        </div>

        {# Instructions #}
        <div class="p-3 bg-blue-50 border rounded-lg">
            <strong>How to use:</strong>
            <ul class="mt-1 ml-4 list-disc space-y-1">
                <li>Click anywhere on the map to set the delivery location</li>
                <li>Drag the marker to adjust the position</li>
                <li>Or enter an address to search</li>
                <li>Leave empty for pickup orders</li>
            </ul>
        </div>
    </div>
</div>
```

#### C. Updated Order Form Component
**File**: `templates/components/admin/order-form.html.twig`

Modified the Delivery Location section to:
- Use the new location-with-map component
- Show that location is optional
- Handle cases where form.location is not defined

```twig
{# Delivery Location Section #}
<div class="space-y-4">
    <div class="flex items-center gap-2 pb-2 border-b">
        <twig:ux:icon name="lucide:map-pin" class="h-5 w-5 text-primary"/>
        <h3 class="text-lg font-semibold">Delivery Location</h3>
        <span class="text-xs text-muted-foreground">(Optional - leave empty for pickup orders)</span>
    </div>
    <div class="rounded-lg border bg-card p-6">
        {% if form.location is defined %}
            <twig:admin:location-with-map :locationForm="form.location" />
        {% else %}
            <p class="text-sm text-muted-foreground">Location form not available</p>
        {% endif %}
    </div>
</div>
```

## User Experience

### How to Use the Location Map

1. **Click on Map**:
   - Click anywhere on the map to place a marker
   - The address field will automatically fill with the location's address
   - Latitude and longitude are automatically updated

2. **Drag Marker**:
   - Drag the marker to fine-tune the location
   - Address is automatically updated as you drag

3. **Search by Address**:
   - Type an address in the address field
   - Press Enter or blur the field to search
   - The map will center on the found location and place a marker

4. **Manual Coordinates**:
   - Latitude and longitude fields are read-only (set by map)
   - They can be viewed but not edited directly

5. **Optional Location**:
   - Location is optional for pickup orders
   - Simply leave all fields empty if no delivery location is needed

### Visual Features

- **400px high map** with OpenStreetMap tiles
- **Draggable marker** with red pin icon
- **Responsive design** that works on mobile and desktop
- **Clear instructions** displayed below the map
- **Readonly latitude/longitude** fields to prevent manual errors
- **Address search** with automatic geocoding

## Technical Details

### APIs Used

1. **OpenStreetMap Tiles**: `https://tile.openstreetmap.org/`
   - Free, open-source map tiles
   - No API key required

2. **Nominatim Geocoding**: `https://nominatim.openstreetmap.org/`
   - Free geocoding service
   - Reverse geocoding (coordinates → address)
   - Forward geocoding (address → coordinates)
   - Usage policy: Respectful usage, max 1 request per second

### Dependencies

**Leaflet**: Loaded dynamically via CDN
- CSS: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.css`
- JS: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js`

### Default Location

**Antananarivo, Madagascar**
- Latitude: -18.8792
- Longitude: 47.5079
- Zoom level: 13

### Data Flow

```
User clicks map
    ↓
Stimulus controller captures click
    ↓
Updates latitude/longitude form fields
    ↓
Calls Nominatim reverse geocoding API
    ↓
Updates address field with result
    ↓
Form submission includes location data
    ↓
NullLocationTransformer checks if all fields empty
    ↓
Returns null if empty, Location object if filled
    ↓
OrderCreateProcessor persists location if not null
```

## Error Prevention

### PropertyAccessor Protection

The implementation prevents PropertyAccessor errors by:

1. **Form Level**: 
   - `required => false` on all location fields
   - `empty_data => null` to avoid empty objects

2. **Transformer Level**:
   - `NullLocationTransformer` converts empty objects to null
   - Checks all three fields (address, latitude, longitude)

3. **Entity Level**:
   - `Location` property is nullable in Order entity
   - `setLocation(?Location $location)` accepts null

4. **Processor Level**:
   - `OrderCreateProcessor` only creates Location if data exists
   - Conditional location persistence

## Benefits

✅ **User-Friendly**: Visual map interface is intuitive
✅ **Accurate**: Click-based selection reduces address errors
✅ **Automatic**: Reverse geocoding fills in addresses automatically
✅ **Flexible**: Supports both map selection and manual entry
✅ **Optional**: Works with or without location data
✅ **Error-Free**: Prevents PropertyAccessor null reference errors
✅ **No Setup**: Uses free CDN-hosted Leaflet library
✅ **Mobile-Friendly**: Responsive design works on all devices

## Testing Checklist

- [x] Map loads and displays correctly
- [x] Clicking map sets coordinates and fetches address
- [x] Dragging marker updates coordinates and address
- [x] Manual address entry searches and updates map
- [x] Leaving location empty doesn't cause errors
- [x] Selecting customer doesn't trigger PropertyAccessor error
- [x] Form submission with location works
- [x] Form submission without location works
- [x] Map shows existing location when editing order
- [x] Mobile/responsive layout works properly

## Future Enhancements

Potential improvements:
- Add custom map styles
- Support for multiple delivery stops
- Route planning integration
- Geofencing for delivery zones
- Real-time location tracking
- Address autocomplete dropdown
- Save favorite delivery locations
- Distance calculation to stores

