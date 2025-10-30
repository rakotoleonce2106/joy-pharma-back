# Store Business Hours API

## Overview

The Store Business Hours API allows store owners to view their store's weekly operating hours, including real-time status of whether the store is currently open and when it will next open.

## Endpoint

### Get Store Business Hours

**GET** `/api/store/business-hours`

Retrieve the complete weekly schedule for the authenticated store owner's store.

#### Authentication
- **Required**: Yes (Bearer Token)
- **Role**: `ROLE_STORE`

#### Path Parameters

None - The store is automatically determined from the authenticated user.

#### Response

**Status Code**: `200 OK`

```json
{
  "storeId": 1,
  "storeName": "Joy Pharma Downtown",
  "hours": {
    "monday": {
      "isClosed": false,
      "openTime": "09:00",
      "closeTime": "18:00",
      "formatted": "09:00 - 18:00"
    },
    "tuesday": {
      "isClosed": false,
      "openTime": "09:00",
      "closeTime": "18:00",
      "formatted": "09:00 - 18:00"
    },
    "wednesday": {
      "isClosed": false,
      "openTime": "09:00",
      "closeTime": "18:00",
      "formatted": "09:00 - 18:00"
    },
    "thursday": {
      "isClosed": false,
      "openTime": "09:00",
      "closeTime": "18:00",
      "formatted": "09:00 - 18:00"
    },
    "friday": {
      "isClosed": false,
      "openTime": "09:00",
      "closeTime": "18:00",
      "formatted": "09:00 - 18:00"
    },
    "saturday": {
      "isClosed": false,
      "openTime": "10:00",
      "closeTime": "16:00",
      "formatted": "10:00 - 16:00"
    },
    "sunday": {
      "isClosed": true,
      "openTime": null,
      "closeTime": null,
      "formatted": "Closed"
    }
  },
  "isCurrentlyOpen": true,
  "nextOpenTime": null
}
```

#### Response Fields

| Field               | Type    | Description                                                       |
|---------------------|---------|-------------------------------------------------------------------|
| `storeId`           | integer | Unique identifier of the store                                    |
| `storeName`         | string  | Name of the store                                                 |
| `hours`             | object  | Weekly schedule with keys for each day (monday-sunday)            |
| `hours.*.isClosed`  | boolean | Whether the store is closed on this day                           |
| `hours.*.openTime`  | string  | Opening time in HH:mm format (null if closed)                     |
| `hours.*.closeTime` | string  | Closing time in HH:mm format (null if closed)                     |
| `hours.*.formatted` | string  | Human-readable format (e.g., "09:00 - 18:00" or "Closed")         |
| `isCurrentlyOpen`   | boolean | Whether the store is currently open (based on current time)       |
| `nextOpenTime`      | string  | Next opening time (e.g., "Monday at 09:00"), null if currently open |

#### Error Responses

**404 Not Found** - No store associated with the authenticated user
```json
{
  "error": "No store found for this user"
}
```

**404 Not Found** - Store settings not configured
```json
{
  "error": "Store settings not found"
}
```

**401 Unauthorized** - Missing or invalid authentication token
```json
{
  "error": "Authentication required"
}
```

**403 Forbidden** - User does not have ROLE_STORE
```json
{
  "error": "Access denied"
}
```

## Use Cases

### 1. Store Management Dashboard
Store owners can view their current operating hours to verify they are correctly configured.

### 2. Real-Time Status Check
Store owners can see if their store is currently marked as open based on the configured hours.

### 3. Operating Hours Review
Before updating hours, store owners can review their current schedule.

### 4. Mobile Store Owner App
Store owner mobile apps can display current hours and allow quick viewing of the weekly schedule.

## Example Requests

### cURL

```bash
curl -X GET "https://api.joy-pharma.com/api/store/business-hours" \
  -H "Authorization: Bearer YOUR_STORE_OWNER_JWT_TOKEN" \
  -H "Accept: application/json"
```

### JavaScript (Fetch API)

```javascript
const response = await fetch('https://api.joy-pharma.com/api/store/business-hours', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${storeOwnerToken}`,
    'Accept': 'application/json'
  }
});

const businessHours = await response.json();

if (businessHours.isCurrentlyOpen) {
  console.log(`Your store is currently open!`);
} else if (businessHours.nextOpenTime) {
  console.log(`Your store opens ${businessHours.nextOpenTime}`);
}
```

### React Example (Store Owner Dashboard)

```jsx
import { useState, useEffect } from 'react';

function MyStoreHours({ token }) {
  const [hours, setHours] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchHours() {
      try {
        const response = await fetch(
          '/api/store/business-hours',
          {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json'
            }
          }
        );
        const data = await response.json();
        setHours(data);
      } catch (error) {
        console.error('Failed to fetch business hours:', error);
      } finally {
        setLoading(false);
      }
    }

    fetchHours();
  }, [token]);

  if (loading) return <div>Loading...</div>;
  if (!hours) return <div>Unable to load hours</div>;

  return (
    <div className="store-hours">
      <h3>My Store Business Hours - {hours.storeName}</h3>
      
      {hours.isCurrentlyOpen ? (
        <div className="status open">Your Store is Currently Open</div>
      ) : (
        <div className="status closed">
          Your Store is Currently Closed
          {hours.nextOpenTime && (
            <span> • Opens {hours.nextOpenTime}</span>
          )}
        </div>
      )}

      <table>
        <tbody>
          {Object.entries(hours.hours).map(([day, schedule]) => (
            <tr key={day}>
              <td className="day">{day.charAt(0).toUpperCase() + day.slice(1)}</td>
              <td className="time">{schedule.formatted}</td>
            </tr>
          ))}
        </tbody>
      </table>
      
      <button onClick={() => window.location.href = '/store/hours/edit'}>
        Edit Business Hours
      </button>
    </div>
  );
}
```

## Implementation Details

### Provider
- **Class**: `App\State\Store\BusinessHoursProvider`
- **Location**: `src/State/Store/BusinessHoursProvider.php`

### DTO
- **Class**: `App\Dto\BusinessHoursResponse`
- **Location**: `src/Dto/BusinessHoursResponse.php`

### API Resource Configuration
- **File**: `src/ApiResource/DeliverySystem.yaml`
- **Operation Name**: `get_business_hours`

## Related Entities

- **Store**: Main store entity
- **StoreSetting**: Holds the weekly business hours configuration
- **BusinessHours**: Individual day hours (open time, close time, closed status)

## Business Rules

1. **Store Owner Access**: Only the authenticated store owner can view their own store's hours.
2. **Default Hours**: Stores default to 9:00 AM - 6:00 PM on weekdays, 10:00 AM - 4:00 PM on Saturday, and closed on Sunday.
3. **Closed Days**: Days marked as `isClosed: true` will show null for open/close times.
4. **24/7 Stores**: Stores without specific hours (null open/close times but not marked as closed) are considered open 24/7.
5. **Current Time Check**: The `isCurrentlyOpen` status is calculated based on the server's current time.
6. **Next Open Calculation**: Checks up to 7 days ahead to find the next opening time.
7. **User-Store Relationship**: The store is automatically determined from the `owner` field in the Store entity.

## Testing

### Test as Store Owner
```bash
# Get a JWT token for a store owner (ROLE_STORE)
STORE_OWNER_TOKEN="your_store_owner_jwt_token_here"

curl -X GET "http://localhost/api/store/business-hours" \
  -H "Authorization: Bearer $STORE_OWNER_TOKEN" \
  -H "Accept: application/json" | jq .
```

### Expected Response Structure
The response should always include:
- Valid store ID and name
- All 7 days of the week with their respective hours
- Current open/closed status
- Next opening time (if currently closed)

## Store Owner Mobile App Integration

### Recommended UI Elements

1. **Dashboard Card**: Show "Your Store is Open" or "Your Store is Closed"
2. **Hours Table**: Display all weekly hours in an editable table
3. **Next Opening**: Show text like "Opens Monday at 9:00" when closed
4. **Quick Actions**: Provide quick links to edit hours or temporarily close store

### Caching Recommendations

Business hours typically don't change frequently, so:
- Cache for 1 hour
- Refresh when app comes to foreground
- Force refresh on pull-to-refresh gesture
- Invalidate cache after editing business hours
- Re-calculate `isCurrentlyOpen` client-side based on cached data and current time

### Security Considerations

- This endpoint requires `ROLE_STORE` authentication
- Only the store owner can view their own hours
- The store is automatically determined from the authenticated user's ownership

## See Also

- [Mobile Store API Complete](MOBILE_STORE_API_COMPLETE.md)
- [Store Features Guide](STORE_FEATURES_GUIDE.md)
- [Mobile App Developer Guide](MOBILE_APP_DEVELOPER_GUIDE.md)

