## Deliver Registration and Profile Update

### Register Deliver

- Method: POST
- URL: `api/register/delivery`
- Auth: none
- Content-Type: multipart/form-data
- Purpose: Create a delivery account (inactive by default) and return a JWT + user info.

Parameters (multipart):
- email (string, email, required)
- password (string, min 8, required)
- firstName (string, required)
- lastName (string, required)
- phone (string, required)
- vehicleType (string, required; one of: bike, motorcycle, car, van)
- vehiclePlate (string, optional)
- residenceDocument (file, optional; pdf/jpg/png/webp, max 10MB)
- vehicleDocument (file, optional; pdf/jpg/png/webp, max 10MB)

201 Response:
```json
{
  "token": "jwt-token",
  "user": {
    "id": 123,
    "email": "rider@example.com",
    "firstName": "Alex",
    "lastName": "Rider",
    "phone": "+261 34 00 000 00",
    "roles": ["ROLE_DELIVER", "ROLE_USER"],
    "userType": "delivery",
    "isActive": false,
    "delivery": {
      "vehicleType": "motorcycle",
      "vehiclePlate": "ABC-1234",
      "isOnline": false,
      "totalDeliveries": 0,
      "averageRating": 0,
      "totalEarnings": 0
    }
  }
}
```

409 Response:
```json
{ "detail": "Email already exists" }
```

Notes:
- Deliver accounts are created inactive; admin must activate before login is allowed.
- Inactive deliverers are blocked from authentication with a clear message.

### Update Deliver Profile

- Method: POST
- URL: `api/user/update`
- Auth: JWT (`ROLE_USER` required)
- Content-Type: multipart/form-data
- Purpose: Update basic profile details and avatar.

Parameters (multipart):
- firstName (string, optional)
- lastName (string, optional)
- phone (string, optional)
- imageFile (file, optional; avatar image)

200 Response (example):
```json
{
  "id": 123,
  "email": "rider@example.com",
  "firstName": "Alex",
  "lastName": "Rider",
  "phone": "+261 34 00 000 00",
  "vehicleType": "motorcycle",
  "vehiclePlate": "ABC-1234",
  "isOnline": false
}
```

Notes:
- Current update endpoint supports firstName, lastName, phone, and imageFile.
- To extend profile updates to include `vehicleType`, `vehiclePlate`, `residenceDocument`, and `vehicleDocument`, add handling in `App\\State\\User\\UserUpdateProcessor` and expose fields in update groups.


