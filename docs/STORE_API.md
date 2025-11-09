# Store API Documentation

This document describes the API endpoints available for store owners (users with `ROLE_STORE`) to manage orders and order items.

## Table of Contents

1. [Authentication](#authentication)
2. [Product Management](#product-management)
   - [Search Products (for Adding to Store)](#search-products-for-adding-to-store)
   - [List Store Products](#list-store-products)
   - [Get Store Product Details](#get-store-product-details)
   - [Create Store Product](#create-store-product)
   - [Update Store Product](#update-store-product)
3. [Order Management](#order-management)
   - [List All Orders](#list-all-orders)
   - [Get Order Details](#get-order-details)
   - [Update Order with Order Item Actions](#update-order-with-order-item-actions)
4. [Store Settings](#store-settings)
   - [Get Store Settings](#get-store-settings)
   - [Update Store Settings](#update-store-settings)

## Authentication

All store API endpoints require JWT authentication with the `ROLE_STORE` role.

**Authorization Header:**
```
Authorization: Bearer {JWT_TOKEN}
```

## Product Management

Store owners can manage their store's product inventory, including adding products, updating prices, managing stock levels, and removing products.

### Workflow for Managing Store Products

1. **Search for Products**: Use the `/api/products/search` endpoint to find products you want to add to your store
2. **Create Store Product**: Use the `/api/store_products` POST endpoint to add a product to your store with pricing and stock information
3. **List Store Products**: Use the `/api/store_products` GET endpoint to view all products in your store
4. **Update Store Product**: Use the `/api/store_products/{id}` PUT endpoint to update prices, stock, or status
5. **View Store Product Details**: Use the `/api/store_products/{id}` GET endpoint to get detailed information about a specific store product

### Search Products (for Adding to Store)

Search for products to add to your store. This endpoint uses Elasticsearch for fast, full-text search with filtering capabilities.

**Endpoint:** `GET /api/products/search`

**Authentication:** Optional (public endpoint, but recommended for better results)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` or `query` | string | No | Search query for product name, code, or description |
| `category` | integer | No | Filter by category ID |
| `brand` | integer | No | Filter by brand ID |
| `manufacturer` | integer | No | Filter by manufacturer ID |
| `isActive` | boolean | No | Filter by active status (true/false) |
| `page` | integer | No | Page number for pagination (default: 1) |
| `perPage` or `itemsPerPage` | integer | No | Number of items per page (default: 10, max: 50) |

**Example Request:**
```bash
# Search for products by name
curl -X GET "https://api.example.com/api/products/search?q=paracetamol" \
  -H "Authorization: Bearer {JWT_TOKEN}"

# Search with filters
curl -X GET "https://api.example.com/api/products/search?q=aspirin&category=3&brand=5&page=1&perPage=20" \
  -H "Authorization: Bearer {JWT_TOKEN}"

# Search all products in a category
curl -X GET "https://api.example.com/api/products/search?category=3&page=1&perPage=10" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Success Response (200 OK):**
```json
[
  {
    "id": 45,
    "name": "Paracetamol 500mg",
    "code": "PAR-500",
    "description": "Pain reliever and fever reducer",
    "images": [
      {
        "id": 12,
        "name": "paracetamol.jpg",
        "size": 102400,
        "mimeType": "image/jpeg",
        "originalName": "paracetamol.jpg"
      }
    ],
    "form": {
      "id": 1,
      "name": "Tablet"
    },
    "brand": {
      "id": 5,
      "name": "PharmaBrand"
    },
    "manufacturer": {
      "id": 2,
      "name": "PharmaManufacturer Inc."
    },
    "category": [
      {
        "id": 3,
        "name": "Pain Relief"
      }
    ],
    "isActive": true,
    "unit": {
      "id": 1,
      "name": "Box"
    },
    "quantity": 10,
    "unitPrice": 100.00,
    "totalPrice": 1000.00,
    "currency": {
      "id": 1,
      "code": "MGA",
      "name": "Malagasy Ariary"
    },
    "stock": 500,
    "createdAt": "2025-01-10T08:00:00+00:00",
    "updatedAt": "2025-01-15T10:30:00+00:00"
  },
  {
    "id": 46,
    "name": "Aspirin 100mg",
    "code": "ASP-100",
    "description": "Blood thinner and pain reliever",
    "images": [],
    "form": {
      "id": 1,
      "name": "Tablet"
    },
    "brand": {
      "id": 5,
      "name": "PharmaBrand"
    },
    "category": [
      {
        "id": 3,
        "name": "Pain Relief"
      }
    ],
    "isActive": true,
    "unit": {
      "id": 1,
      "name": "Box"
    },
    "quantity": 20,
    "unitPrice": 50.00,
    "totalPrice": 1000.00,
    "currency": {
      "id": 1,
      "code": "MGA",
      "name": "Malagasy Ariary"
    },
    "stock": 300,
    "createdAt": "2025-01-10T08:00:00+00:00",
    "updatedAt": "2025-01-15T10:30:00+00:00"
  }
]
```

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Product ID (use this when creating store products) |
| `name` | string | Product name |
| `code` | string | Product code/SKU |
| `description` | string | Product description |
| `images` | array | Array of product images |
| `form` | object | Product form (e.g., Tablet, Capsule) |
| `brand` | object | Product brand information |
| `manufacturer` | object | Product manufacturer information |
| `category` | array | Array of categories the product belongs to |
| `isActive` | boolean | Whether the product is active |
| `unit` | object | Product unit (e.g., Box, Bottle) |
| `quantity` | integer | Quantity per unit |
| `unitPrice` | float | Price per unit |
| `totalPrice` | float | Total price for the product |
| `currency` | object | Currency information |
| `stock` | integer | Available stock |
| `createdAt` | string | ISO 8601 creation date |
| `updatedAt` | string | ISO 8601 update date |

**Search Behavior:**
- The search is case-insensitive and supports fuzzy matching
- Searches across product name, code, and description
- Returns results ordered by relevance score
- Empty query returns all products (with pagination)

**Error Responses:**

- `400 Bad Request`: Invalid parameters
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Invalid parameter: perPage must be between 1 and 50"
  }
  ```

### List Store Products

Get a list of all products in your store. This endpoint returns only products that belong to the authenticated store owner's store.

**Endpoint:** `GET /api/store_products`

**Authentication:** Required (`ROLE_STORE`)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number for pagination (default: 1) |
| `itemsPerPage` | integer | No | Number of items per page (default: 30) |

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/store_products" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Success Response (200 OK):**
```json
[
  {
    "id": 15,
    "product": {
      "id": 45,
      "name": "Paracetamol 500mg",
      "code": "PAR-500",
      "description": "Pain reliever and fever reducer",
      "images": [
        {
          "id": 12,
          "name": "paracetamol.jpg",
          "size": 102400,
          "mimeType": "image/jpeg",
          "originalName": "paracetamol.jpg"
        }
      ],
      "form": {
        "id": 1,
        "name": "Tablet"
      },
      "brand": {
        "id": 5,
        "name": "PharmaBrand"
      },
      "category": [
        {
          "id": 3,
          "name": "Pain Relief"
        }
      ],
      "isActive": true,
      "unit": {
        "id": 1,
        "name": "Box"
      }
    },
    "store": {
      "id": 3,
      "name": "Pharmacy ABC"
    },
    "unitPrice": 500.00,
    "price": 1500.00,
    "stock": 50,
    "isActive": true,
    "createdAt": "2025-01-10T08:00:00+00:00",
    "updatedAt": "2025-01-15T10:30:00+00:00"
  },
  {
    "id": 16,
    "product": {
      "id": 46,
      "name": "Aspirin 100mg",
      "code": "ASP-100",
      "description": "Blood thinner and pain reliever",
      "images": [],
      "isActive": true
    },
    "store": {
      "id": 3,
      "name": "Pharmacy ABC"
    },
    "unitPrice": 300.00,
    "price": 900.00,
    "stock": 25,
    "isActive": true,
    "createdAt": "2025-01-10T08:00:00+00:00",
    "updatedAt": "2025-01-15T10:30:00+00:00"
  }
]
```

**Error Responses:**

- `401 Unauthorized`: Authentication required
  ```json
  {
    "code": 401,
    "message": "JWT Token not found"
  }
  ```

- `403 Forbidden`: User is not a store owner
  ```json
  {
    "code": 403,
    "message": "Access Denied."
  }
  ```

### Get Store Product Details

Get detailed information about a specific product in your store.

**Endpoint:** `GET /api/store_products/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the store product |

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/store_products/15" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Success Response (200 OK):**
```json
{
  "id": 15,
  "product": {
    "id": 45,
    "name": "Paracetamol 500mg",
    "code": "PAR-500",
    "description": "Pain reliever and fever reducer",
    "images": [
      {
        "id": 12,
        "name": "paracetamol.jpg",
        "size": 102400,
        "mimeType": "image/jpeg",
        "originalName": "paracetamol.jpg"
      }
    ],
    "form": {
      "id": 1,
      "name": "Tablet"
    },
    "brand": {
      "id": 5,
      "name": "PharmaBrand"
    },
    "manufacturer": {
      "id": 2,
      "name": "PharmaManufacturer Inc."
    },
    "category": [
      {
        "id": 3,
        "name": "Pain Relief"
      }
    ],
    "isActive": true,
    "unit": {
      "id": 1,
      "name": "Box"
    },
    "quantity": 10,
    "unitPrice": 100.00,
    "totalPrice": 1000.00,
    "currency": {
      "id": 1,
      "code": "MGA",
      "name": "Malagasy Ariary"
    }
  },
  "store": {
    "id": 3,
    "name": "Pharmacy ABC"
  },
  "unitPrice": 500.00,
  "price": 1500.00,
  "stock": 50,
  "isActive": true,
  "createdAt": "2025-01-10T08:00:00+00:00",
  "updatedAt": "2025-01-15T10:30:00+00:00"
}
```

**Error Responses:**

- `404 Not Found`: Store product not found or doesn't belong to your store
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Store product not found"
  }
  ```

- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Access denied

### Create Store Product

Create a new store product in your inventory. This endpoint allows you to add a product to your store with pricing and stock information.

**Endpoint:** `POST /api/store_products`

**Authentication:** Required (`ROLE_STORE`)

**Content-Type:** `application/json`

**Request Body:**

```json
{
  "product": "/api/product/45",
  "price": 1500.00,
  "unitPrice": 500.00,
  "stock": 50
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product` | string (IRI) | Yes | Product resource IRI (e.g., `/api/product/45`) or product ID as object |
| `price` | float | Yes | Selling price for the product (must be greater than 0) |
| `unitPrice` | float | No | Price per unit |
| `stock` | integer | Yes | Available stock quantity (must be 0 or greater) |

**Alternative Request Format (using product ID directly):**

```json
{
  "product": 45,
  "price": 1500.00,
  "unitPrice": 500.00,
  "stock": 50
}
```

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/store_products" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "product": "/api/product/45",
    "price": 1500.00,
    "unitPrice": 500.00,
    "stock": 50
  }'
```

**Success Response (201 Created):**
```json
{
  "id": 15,
  "product": {
    "id": 45,
    "name": "Paracetamol 500mg",
    "code": "PAR-500",
    "description": "Pain reliever and fever reducer",
    "images": [
      {
        "id": 12,
        "name": "paracetamol.jpg",
        "size": 102400,
        "mimeType": "image/jpeg",
        "originalName": "paracetamol.jpg"
      }
    ],
    "form": {
      "id": 1,
      "name": "Tablet"
    },
    "brand": {
      "id": 5,
      "name": "PharmaBrand"
    },
    "manufacturer": {
      "id": 2,
      "name": "PharmaManufacturer Inc."
    },
    "category": [
      {
        "id": 3,
        "name": "Pain Relief"
      }
    ],
    "isActive": true,
    "unit": {
      "id": 1,
      "name": "Box"
    },
    "quantity": 10,
    "unitPrice": 100.00,
    "totalPrice": 1000.00,
    "currency": {
      "id": 1,
      "code": "MGA",
      "name": "Malagasy Ariary"
    },
    "stock": 500,
    "createdAt": "2025-01-10T08:00:00+00:00",
    "updatedAt": "2025-01-15T10:30:00+00:00"
  },
  "store": {
    "id": 3,
    "name": "Pharmacy ABC"
  },
  "unitPrice": 500.00,
  "price": 1500.00,
  "stock": 50,
  "isActive": true,
  "createdAt": "2025-01-15T10:30:00+00:00",
  "updatedAt": "2025-01-15T10:30:00+00:00"
}
```

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Store product ID |
| `product` | object | Full product details |
| `store` | object | Store information (automatically set to your store) |
| `unitPrice` | float | Price per unit |
| `price` | float | Selling price |
| `stock` | integer | Available stock |
| `isActive` | boolean | Whether the store product is active |
| `createdAt` | string | ISO 8601 creation date |
| `updatedAt` | string | ISO 8601 update date |

**Error Responses:**

- `400 Bad Request`: Invalid request, validation failed, or product already exists
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Product is required"
  }
  ```
  
  Common error messages:
  - `"Product is required"` - Product field is missing
  - `"Product not found"` - Product ID doesn't exist
  - `"Product ID is required"` - Invalid product format
  - `"Store product already exists for this product"` - Product already added to store
  - `"Price must be greater than 0"` - Invalid price value
  - `"Stock must be 0 or greater"` - Invalid stock value
  - `"Validation failed: {field}: {message}"` - Validation errors

- `401 Unauthorized`: Authentication required
  ```json
  {
    "code": 401,
    "message": "JWT Token not found"
  }
  ```

- `403 Forbidden`: User must be a store owner
  ```json
  {
    "code": 403,
    "message": "User must be a store owner"
  }
  ```

- `404 Not Found`: User doesn't have a store
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "User does not have a store"
  }
  ```

### Update Store Product

Update the price, stock level, or status of a product in your store.

**Endpoint:** `PUT /api/store_products/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Content-Type:** `application/json`

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the store product to update |

**Request Body:**

```json
{
  "price": 1500.00,
  "unitPrice": 500.00,
  "stock": 50,
  "isActive": true
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `price` | float | No | Selling price for the product |
| `unitPrice` | float | No | Price per unit |
| `stock` | integer | No | Available stock quantity |
| `isActive` | boolean | No | Whether the product is active/available |

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/store_products/15" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "price": 1500.00,
    "unitPrice": 500.00,
    "stock": 50,
    "isActive": true
  }'
```

**Success Response (200 OK):**
```json
{
  "id": 15,
  "product": {
    "id": 45,
    "name": "Paracetamol 500mg"
  },
  "store": {
    "id": 3,
    "name": "Pharmacy ABC"
  },
  "unitPrice": 500.00,
  "price": 1500.00,
  "stock": 50,
  "isActive": true,
  "createdAt": "2025-01-10T08:00:00+00:00",
  "updatedAt": "2025-01-15T10:35:00+00:00"
}
```

**Error Responses:**

- `404 Not Found`: Store product not found or doesn't belong to your store
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Store product not found"
  }
  ```

- `400 Bad Request`: Validation error
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Validation failed",
    "violations": [
      {
        "propertyPath": "stock",
        "message": "Stock must be a positive integer"
      }
    ]
  }
  ```

- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Access denied

### Delete Store Product

Remove a product from your store inventory.

**Endpoint:** `DELETE /api/store_products/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the store product to delete |

**Example Request:**
```bash
curl -X DELETE "https://api.example.com/api/store_products/15" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Success Response (204 No Content):**

The response body is empty.

**Error Responses:**

- `404 Not Found`: Store product not found or doesn't belong to your store
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Store product not found"
  }
  ```

- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Access denied

**Note:** Deleting a store product does not delete the underlying product from the system. It only removes the product from your store's inventory.

## Order Management

Stores manage orders by updating order items within each order. Each order can contain multiple items, and each item can be assigned to a specific store.

### List All Orders

Get a list of all orders. Stores with `ROLE_STORE` can view all orders.

**Endpoint:** `GET /api/orders`

**Authentication:** Required (`ROLE_STORE`, `ROLE_ADMIN`, `ROLE_USER`, or `ROLE_DELIVER`)

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/orders" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
[
  {
    "id": 78,
    "reference": "ORD-2025-001",
    "totalAmount": 5000.00,
    "status": "Pending",
    "priority": "Standard",
    "createdAt": "2025-01-15T08:00:00+00:00",
    "owner": {
      "id": 12,
      "email": "customer@example.com"
    },
    "location": {
      "address": "123 Main Street, Antananarivo",
      "latitude": -18.8792,
      "longitude": 47.5079
    },
    "items": [
      {
        "id": 123,
        "product": {
          "id": 45,
          "name": "Paracetamol 500mg"
        },
        "quantity": 2,
        "storeStatus": "pending"
      }
    ]
  }
]
```

### Get Order Details

Get detailed information about a specific order.

**Endpoint:** `GET /api/order/{id}`

**Authentication:** Required (`ROLE_STORE`, `ROLE_ADMIN`, `ROLE_USER`, or `ROLE_DELIVER`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order |

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/order/78" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "totalAmount": 5000.00,
  "status": "Pending",
  "priority": "Standard",
  "phone": "+261340000000",
  "createdAt": "2025-01-15T08:00:00+00:00",
  "scheduledDate": "2025-01-16T14:00:00+00:00",
  "notes": "Customer prefers morning delivery",
  "deliveryNotes": "Ring doorbell twice",
  "owner": {
    "id": 12,
    "email": "customer@example.com",
    "firstName": "John",
    "lastName": "Doe"
  },
  "location": {
    "id": 34,
    "address": "123 Main Street, Antananarivo",
    "latitude": -18.8792,
    "longitude": 47.5079
  },
  "items": [
    {
      "id": 123,
      "product": {
        "id": 45,
        "name": "Paracetamol 500mg",
        "price": 1500.00
      },
      "quantity": 2,
      "totalPrice": 3000.00,
      "store": {
        "id": 5,
        "name": "Pharmacy ABC"
      },
      "storeStatus": "pending",
      "storePrice": null,
      "storeNotes": null
    }
  ]
}
```

### Update Order with Order Item Actions

Update an order by accepting, refusing, or suggesting alternatives for multiple order items in a single request.

**Endpoint:** `PUT /api/store/order/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order to update |

**Request Body:**

```json
{
  "orderItemActions": [
    {
      "orderItemId": 123,
      "action": "accept",
      "notes": "Item will be ready for pickup tomorrow morning"
    },
    {
      "orderItemId": 124,
      "action": "refuse",
      "reason": "Product is currently out of stock. Expected restock in 3 days."
    },
    {
      "orderItemId": 125,
      "action": "suggest",
      "suggestedProductId": 56,
      "suggestion": "We have a generic version available at 20% lower price",
      "notes": "Same active ingredient and dosage"
    }
  ]
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `orderItemActions` | array | Yes | Array of order item actions (minimum 1 action required) |

**Order Item Action Object:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `orderItemId` | integer | Yes | ID of the order item to update |
| `action` | string | Yes | Action to perform: `accept`, `refuse`, or `suggest` |
| `notes` | string | No | Optional notes (for `accept` action) |
| `reason` | string | Yes | Required reason (for `refuse` action) |
| `suggestedProductId` | integer | Yes | ID of alternative product (for `suggest` action) |
| `suggestion` | string | No | Explanation of suggestion (for `suggest` action) |

**Validation Rules:**

1. The order must exist
2. The authenticated user must be the owner of the store assigned to each order item
3. Each order item must belong to the order being updated
4. For `accept` action:
   - Product must be available in store's inventory
   - Store must have a valid price set for the product
5. For `refuse` action:
   - Reason is required
6. For `suggest` action:
   - Suggested product ID is required
   - Suggested product must be available in store's inventory

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/store/order/78" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "orderItemActions": [
      {
        "orderItemId": 123,
        "action": "accept",
        "notes": "Item will be ready for pickup tomorrow morning"
      },
      {
        "orderItemId": 124,
        "action": "refuse",
        "reason": "Product is currently out of stock"
      }
    ]
  }'
```

**Success Response (200 OK):**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "totalAmount": 4500.00,
  "status": "Pending",
  "items": [
    {
      "id": 123,
      "product": {
        "id": 45,
        "name": "Paracetamol 500mg"
      },
      "quantity": 2,
      "storeStatus": "accepted",
      "storePrice": 1500.00,
      "storeNotes": "Item will be ready for pickup tomorrow morning",
      "storeActionAt": "2025-01-15T10:30:00+00:00"
    },
    {
      "id": 124,
      "product": {
        "id": 46,
        "name": "Aspirin 100mg"
      },
      "quantity": 1,
      "storeStatus": "refused",
      "storeNotes": "Product is currently out of stock",
      "storeActionAt": "2025-01-15T10:30:00+00:00"
    }
  ]
}
```

**What Happens:**

1. Each order item action is processed sequentially
2. For `accept` action:
   - Order item `storeStatus` is set to `accepted`
   - Store's price for the product is set as `storePrice`
   - Optional notes are saved in `storeNotes`
   - `storeActionAt` timestamp is recorded
3. For `refuse` action:
   - Order item `storeStatus` is set to `refused`
   - Reason is saved in `storeNotes`
   - `storeActionAt` timestamp is recorded
4. For `suggest` action:
   - Order item `storeStatus` is set to `suggested`
   - Suggested product is linked to the order item
   - Store's price for the suggested product is stored
   - Suggestion text and notes are saved
   - `storeActionAt` timestamp is recorded
   - **Note:** Suggestions require admin approval before they can be applied
5. After all actions are processed, the parent order's total amount is recalculated

**Error Responses:**

- `404 Not Found`: Order not found or order item not found
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: You are not authorized to update this order or order item
- `400 Bad Request`: 
  - Order item actions are required
  - Invalid action type (must be: accept, refuse, suggest)
  - Product is not available in your store inventory (for accept/suggest)
  - Store product has no valid price set (for accept/suggest)
  - Reason is required (for refuse)
  - Suggested product ID is required (for suggest)
  - Order item does not belong to the order being updated

## Store Settings

### Get Store Settings

Get the store settings for the authenticated store owner.

**Endpoint:** `GET /api/store_settings` or `GET /api/store_settings/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | No | Store settings ID (optional - falls back to authenticated user store settings) |

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/store_settings" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
{
  "id": 10,
  "store": {
    "id": 5,
    "name": "Pharmacy ABC"
  },
  "businessHours": [
    {
      "id": 1,
      "dayOfWeek": 1,
      "openTime": "08:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    {
      "id": 2,
      "dayOfWeek": 2,
      "openTime": "08:00",
      "closeTime": "18:00",
      "isClosed": false
    }
  ]
}
```

### Update Store Settings

Update store settings including business hours.

**Endpoint:** `PUT /api/store_settings/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | The ID of the store settings |

**Request Body:**

```json
{
  "businessHours": [
    {
      "@id": "/business_hours/1",
      "dayOfWeek": 1,
      "openTime": "08:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    {
      "dayOfWeek": 2,
      "openTime": "09:00",
      "closeTime": "17:00",
      "isClosed": false
    }
  ]
}
```

**Note:** Include `@id` for existing business hours to update them, or omit `@id` to create new ones.

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/store_settings/10" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "businessHours": [
      {
        "@id": "/business_hours/1",
        "dayOfWeek": 1,
        "openTime": "08:00",
        "closeTime": "18:00",
        "isClosed": false
      }
    ]
  }'
```

## Order Item Status Flow

The status of an order item follows this flow:

1. **`pending`**: Initial status when order is created and assigned to store
2. **`accepted`**: Store accepts the order item with their price
3. **`refused`**: Store refuses the order item with a reason
4. **`suggested`**: Store suggests an alternative product (requires admin approval)
5. **`approved`**: Admin approves the suggestion (returns item to `pending` status for store to accept)
6. **`rejected`**: Admin rejects the suggestion

## Workflow Examples

### Example 1: Accepting Multiple Order Items

1. Store owner calls `GET /api/orders` to see all orders
2. Store owner selects an order and calls `GET /api/order/78` to see details
3. Store owner reviews the order items and their products
4. Store owner calls `PUT /api/store/order/78` with actions to accept items:
   ```json
   {
     "orderItemActions": [
       {
         "orderItemId": 123,
         "action": "accept",
         "notes": "Ready for pickup"
       },
       {
         "orderItemId": 124,
         "action": "accept",
         "notes": "Will be ready tomorrow"
       }
     ]
   }
   ```
5. System sets `storeStatus` to `accepted` for both items and applies store's prices
6. Order total is recalculated

### Example 2: Mixing Actions (Accept, Refuse, Suggest)

1. Store owner reviews an order with multiple items
2. Store owner calls `PUT /api/store/order/78` with mixed actions:
   ```json
   {
     "orderItemActions": [
       {
         "orderItemId": 123,
         "action": "accept",
         "notes": "Available"
       },
       {
         "orderItemId": 124,
         "action": "refuse",
         "reason": "Out of stock"
       },
       {
         "orderItemId": 125,
         "action": "suggest",
         "suggestedProductId": 56,
         "suggestion": "Generic alternative available",
         "notes": "Same dosage"
       }
     ]
   }
   ```
3. System processes all actions in one transaction
4. Order total is recalculated based on accepted items

### Example 3: Suggesting an Alternative Product

1. Store owner finds a product is out of stock or wants to suggest a better option
2. Store owner calls `PUT /api/store/order/78` with a suggest action:
   ```json
   {
     "orderItemActions": [
       {
         "orderItemId": 125,
         "action": "suggest",
         "suggestedProductId": 56,
         "suggestion": "We have a generic version available at 20% lower price",
         "notes": "Same active ingredient and dosage"
       }
     ]
   }
   ```
3. System sets `storeStatus` to `suggested`
4. Admin reviews the suggestion
5. If approved, admin calls `POST /api/admin/order-item/approve-suggestion`
6. The order item returns to `pending` status with the new product
7. Store owner can now accept the item using the update order endpoint

## Error Handling

All endpoints return standard HTTP status codes:

- `200 OK`: Success
- `201 Created`: Resource created successfully
- `400 Bad Request`: Validation error or bad request
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `500 Internal Server Error`: Server error

Error response format:
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Specific error message",
  "violations": [
    {
      "propertyPath": "orderItemActions[0].orderItemId",
      "message": "Order item ID is required"
    }
  ]
}
```

## Best Practices

### Product Management

1. **Set prices when adding products**: After adding products to your store, immediately update them with prices and stock levels
2. **Keep stock levels updated**: Regularly update stock quantities to ensure accurate inventory management
3. **Use isActive flag**: Set products to inactive when temporarily unavailable instead of deleting them
4. **Monitor prices**: Keep prices competitive and update them regularly based on market conditions
5. **Batch operations**: Use the add products endpoint to add multiple products at once

### Order Management

1. **Batch Operations**: Use the update order endpoint to process multiple order items in a single request for better performance
2. **Always check inventory**: Before accepting, verify the product is in your store's inventory
3. **Use notes effectively**: Provide helpful notes to customers when accepting items
4. **Clear refusal reasons**: When refusing, provide detailed reasons for better customer experience
5. **Suggest alternatives**: Instead of refusing, consider suggesting alternative products
6. **Transaction Safety**: All order item actions are processed in a single transaction - if one fails, all are rolled back
7. **Order Totals**: Order totals are automatically recalculated after processing all actions

## Workflow Examples

### Example 1: Adding Products to Store

1. Store owner calls `GET /api/products` to see available products
2. Store owner selects products and calls `POST /api/store_products` to add them:
   ```json
   {
     "items": [45, 46, 47]
   }
   ```
3. Store owner updates each product with prices and stock:
   ```json
   PUT /api/store_products/15
   {
     "price": 1500.00,
     "unitPrice": 500.00,
     "stock": 50,
     "isActive": true
   }
   ```

### Example 2: Updating Stock Levels

1. Store owner receives new inventory
2. Store owner calls `PUT /api/store_products/{id}` to update stock:
   ```json
   {
     "stock": 100,
     "price": 1500.00
   }
   ```

### Example 3: Managing Product Availability

1. Product is temporarily out of stock
2. Store owner sets product to inactive:
   ```json
   PUT /api/store_products/15
   {
     "isActive": false
   }
   ```
3. When product is back in stock:
   ```json
   PUT /api/store_products/15
   {
     "isActive": true,
     "stock": 25
   }
   ```

## Integration Notes

- All timestamps are in ISO 8601 format with timezone
- Prices are in the local currency (typically MGA for Madagascar)
- The API uses JSON for request and response bodies
- Store prices are automatically applied from StoreProduct when accepting items
- The order total is recalculated automatically after all actions are processed
- All order item actions in a single request are processed in one transaction
- Store products are automatically filtered by the authenticated store owner's store
- Products must exist in the system before they can be added to a store
