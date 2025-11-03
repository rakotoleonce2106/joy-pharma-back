# Order Management Documentation

This document explains how to create and update orders in the JoyPharma admin system.

## Table of Contents

1. [Overview](#overview)
2. [Creating an Order](#creating-an-order)
3. [Updating an Order](#updating-an-order)
4. [Order Form Fields](#order-form-fields)
5. [Validation Rules](#validation-rules)
6. [Technical Implementation](#technical-implementation)

## Overview

Orders in JoyPharma can be created and updated through the admin interface. Each order consists of:

- **Order Details**: Reference, customer, priority, status
- **Delivery Location**: Optional address with map selection
- **Order Items**: One or more products with quantities
- **Delivery Information**: Phone, delivery person, notes
- **Total Amount**: Auto-calculated from order items

## Creating an Order

### Access
Navigate to: `/admin/order/new`

### Required Fields

1. **Reference** (Required)
   - Unique order reference number
   - Format: Text string

2. **Customer** (Required)
   - Select from existing customers
   - Only users without `ROLE_ADMIN` or `ROLE_DELIVERY` are available

3. **Phone** (Required)
   - Customer phone number
   - Format: e.g., `+261340000000`

4. **Priority** (Required)
   - Options:
     - `Urgent`
     - `Standard`
     - `Planned`

5. **Status** (Required)
   - Options:
     - `Pending`
     - `Confirmed`
     - `Processing`
     - `Shipped`
     - `Delivered`
     - `Cancelled`

6. **Order Items** (Required)
   - At least one item must be added
   - Each item requires:
     - **Product**: Select from available products
     - **Quantity**: Minimum 1
     - **Store**: Optional, specific pharmacy/pharmacy

### Optional Fields

1. **Delivery Location**
   - Interactive map for address selection
   - Can be left empty for pickup orders
   - Fields:
     - Address (auto-filled from map)
     - Latitude (auto-filled from map)
     - Longitude (auto-filled from map)

2. **Scheduled Date**
   - Date and time for order delivery
   - Format: DateTime

3. **Delivery Person**
   - Assign a delivery person (users with `ROLE_DELIVERY`)
   - Optional

4. **Notes**
   - Internal notes for staff
   - Format: Textarea

5. **Delivery Notes**
   - Special instructions for delivery person
   - Format: Textarea

### Step-by-Step Process

1. **Fill Order Details**
   - Enter order reference
   - Select customer
   - Set priority and status
   - Enter phone number

2. **Add Delivery Location** (Optional)
   - Click on map to set location
   - Or enter address manually
   - Address will be auto-filled if you click on the map

3. **Add Order Items**
   - Click "Add Item" button
   - Select product
   - Enter quantity
   - Optionally select store
   - Repeat for each item
   - Total amount is calculated automatically

4. **Add Additional Information** (Optional)
   - Set scheduled date
   - Assign delivery person
   - Add notes or delivery notes

5. **Save Order**
   - Click "Save Order" button
   - Order is validated and saved
   - Redirected to orders list

## Updating an Order

### Access
Navigate to: `/admin/order/{id}/edit`

### Process

1. **Load Existing Order**
   - Order details are pre-filled
   - All items are displayed
   - Location is shown on map if exists

2. **Modify Fields**
   - Change any order details
   - Add/remove/update items
   - Modify location
   - Update status, priority, etc.

3. **Save Changes**
   - Click "Save Order" button
   - Changes are validated and saved
   - Redirected to orders list

### Restrictions

- Cannot delete order items without replacing them
- Must have at least one item
- Customer is required
- Phone is required

## Order Form Fields

### Order Details Section

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Reference | Text | Yes | Unique order reference |
| Customer | Entity | Yes | Order owner (User entity) |
| Total Amount | Number | Auto | Calculated from items (read-only) |

### Priority and Status

| Field | Type | Required | Options |
|-------|------|----------|---------|
| Priority | Enum | Yes | Urgent, Standard, Planned |
| Status | Enum | Yes | Pending, Confirmed, Processing, Shipped, Delivered, Cancelled |

### Delivery Location Section

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Address | Text | No | Delivery address |
| Latitude | Number | No | Auto-filled from map |
| Longitude | Number | No | Auto-filled from map |

**Map Features:**
- Interactive Leaflet map
- Click to set location
- Drag marker to adjust
- Search address to geocode
- Reverse geocoding on click

### Order Items Section

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Product | Entity | Yes | Product to order |
| Quantity | Integer | Yes | Minimum: 1 |
| Store | Entity | No | Specific pharmacy/pharmacy |

**Features:**
- Dynamic item addition/removal
- Auto-calculate total amount
- Product price lookup
- Store selection for each item

### Additional Information

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Scheduled Date | DateTime | No | Delivery date/time |
| Phone | Text | Yes | Customer phone |
| Delivery Person | Entity | No | User with ROLE_DELIVERY |
| Notes | Textarea | No | Internal notes |
| Delivery Notes | Textarea | No | Delivery instructions |

## Validation Rules

### Required Fields

1. **Reference**: Must be provided
2. **Customer**: Must be selected
3. **Phone**: Must be provided
4. **Priority**: Must be selected
5. **Status**: Must be selected
6. **Items**: At least one item required

### Business Rules

1. **Total Amount Calculation**
   - Sum of (Product Price × Quantity) for all items
   - Updated automatically when items change
   - Displayed in real-time

2. **Location Validation**
   - If address provided, latitude and longitude should be set
   - Map automatically sets coordinates when clicked
   - Can be empty for pickup orders

3. **Item Validation**
   - Quantity must be ≥ 1
   - Product must exist
   - Store is optional (system can auto-assign)

## Technical Implementation

### Controller

**File**: `src/Controller/Admin/OrderController.php`

**Methods:**
- `createAction()`: Handle order creation
- `editAction()`: Handle order editing
- `handleorderForm()`: Shared form processing logic

**Routes:**
- `GET /admin/order/new`: Show create form
- `POST /admin/order/new`: Process create
- `GET /admin/order/{id}/edit`: Show edit form
- `POST /admin/order/{id}/edit`: Process update

### Form Type

**File**: `src/Form/OrderType.php`

**Fields:**
- Reference (TextType)
- Owner/Customer (EntityType)
- Location (LocationType with map widget)
- Priority (EnumType)
- Status (EnumType)
- Scheduled Date (DateTimeType)
- Phone (TextType)
- Deliver/Delivery Person (EntityType)
- Items (CollectionType with OrderItemType)
- Notes (TextareaType)
- Delivery Notes (TextareaType)

### Service

**File**: `src/Service/OrderService.php`

**Methods:**
- `createOrder(Order $order)`: Persist new order
- `updateOrder(Order $order)`: Flush order changes
- `deleteOrder(Order $order)`: Remove order
- `batchDeleteOrders(array $ids)`: Delete multiple orders

### Entity

**File**: `src/Entity/Order.php`

**Key Properties:**
- `owner`: User (customer)
- `location`: Location (optional)
- `items`: Collection<OrderItem>
- `totalAmount`: float (calculated)
- `status`: OrderStatus enum
- `priority`: PriorityType enum
- `phone`: string
- `deliver`: User (delivery person)

### Location Map Integration

The order form includes an interactive map for location selection:

**Controller**: `assets/controllers/location_map_controller.js`

**Features:**
- Leaflet.js map integration
- Click to set location
- Drag marker to adjust
- Address geocoding
- Reverse geocoding
- Auto-fill coordinates

**Widget**: `templates/form/location_widget.html.twig`
- Renders map with form fields
- Handles `_order_location_widget` block
- Includes map container and input fields

### Order Items

**Form**: `src/Form/OrderItemType.php`

**Fields:**
- Product (EntityType)
- Quantity (IntegerType)
- Store (EntityType, optional)

**JavaScript Controller**: `assets/controllers/order_total_controller.js`
- Real-time total calculation
- Updates when items change
- Handles product price changes

## Form Validation Flow

1. **Form Submission**
   - Symfony form validation
   - Entity validation (Assert annotations)
   - Custom validation in controller

2. **Custom Validations**
   - Customer must be selected
   - Phone must be provided
   - At least one item required

3. **Service Layer**
   - `OrderService::createOrder()` or `updateOrder()`
   - Persists order and related entities
   - Handles cascade operations

## Error Handling

### Common Errors

1. **Missing Customer**
   - Message: "Customer is required. Please select a customer before saving."
   - Solution: Select a customer from dropdown

2. **Missing Phone**
   - Message: "Phone number is required."
   - Solution: Enter phone number

3. **No Items**
   - Message: "Order must have at least one item."
   - Solution: Add at least one item

4. **PropertyAccessor Error (Location)**
   - Fixed by initializing Location object in form
   - `LocationType` uses `empty_data` callback

## Best Practices

1. **Order Reference**
   - Use unique, descriptive references
   - Consider date-based prefixes: `ORD-2025-001`

2. **Location Selection**
   - Use map for accurate coordinates
   - Verify address before saving
   - Leave empty for pickup orders

3. **Order Items**
   - Add items one by one
   - Verify quantities before saving
   - Check total amount is correct

4. **Status Management**
   - Start with `Pending` for new orders
   - Update status as order progresses
   - Use `Cancelled` appropriately

5. **Notes**
   - Use "Notes" for internal staff information
   - Use "Delivery Notes" for delivery instructions

## Examples

### Creating a Simple Order

```
Reference: ORD-2025-001
Customer: John Doe - john@example.com
Phone: +261340000000
Priority: Standard
Status: Pending

Items:
  - Product: Aspirin 100mg, Quantity: 2
  - Product: Paracetamol 500mg, Quantity: 1

Notes: Regular customer, delivery preferred
```

### Creating an Order with Location

```
Reference: ORD-2025-002
Customer: Jane Smith - jane@example.com
Phone: +261341111111
Priority: Urgent
Status: Confirmed

Location:
  - Address: 123 Main Street, Antananarivo
  - Latitude: -18.8792
  - Longitude: 47.5079

Items:
  - Product: Medicine A, Quantity: 3, Store: Pharmacy X
  - Product: Medicine B, Quantity: 1, Store: Pharmacy Y

Scheduled Date: 2025-01-15 14:00
Delivery Person: Delivery Person 1
Delivery Notes: Ring doorbell twice, leave at door if no answer
```

## Troubleshooting

### Map Not Showing
- Check browser console for errors
- Ensure Leaflet.js is loading
- Verify controller is connecting
- Check container dimensions

### Total Amount Not Updating
- Verify JavaScript is enabled
- Check `order-total` controller is connected
- Ensure product prices are set
- Refresh page if needed

### Location Not Saving
- Verify Location object is initialized
- Check form validation passes
- Ensure coordinates are valid
- Check database constraints

## API Integration

Orders can also be created via API Platform:

**Endpoint**: `POST /api/orders`

**Processor**: `src/State/Order/OrderCreateProcessor.php`

**Input DTO**: `src/Dto/OrderInput.php`

The API processor handles:
- Order creation
- Location creation (if provided)
- Order items creation
- Payment creation
- Total amount calculation

