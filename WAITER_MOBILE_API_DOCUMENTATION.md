# Waiter Mobile App API Documentation

## Base URL
```
http://your-domain.com/api
```

## Authentication

All protected endpoints require an authentication token in the request header:
```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Login
**POST** `/api/waiter/login`

**Request Body (Option 1 - Using Staff ID):**
```json
{
  "staff_id": "STF20251201001",
  "password": "password123"
}
```

**Request Body (Option 2 - Using Email):**
```json
{
  "email": "waiter@mauzo.com",
  "password": "password123"
}
```

**Note:** You can use either `staff_id` OR `email` for login (one is required).

**Response:**
```json
{
  "success": true,
  "token": "random_token_string_here",
  "waiter": {
    "id": 1,
    "staff_id": "STF20251201001",
    "name": "John Doe",
    "email": "john@example.com",
    "phone_number": "+255712345678"
  },
  "expires_at": "2026-01-06T08:12:29+00:00"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Invalid waiter ID or account is inactive"
}
```

---

### 2. Logout
**POST** `/api/waiter/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 3. Get Products (Drinks)
**GET** `/api/waiter/products`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 1,
      "product_name": "Coca Cola",
      "variant": "700 ml - Crates",
      "measurement": "700 ml",
      "packaging": "Crates",
      "quantity": 50,
      "selling_price": 600.00,
      "category": "Soft Drinks",
      "is_alcoholic": false,
      "product_image": null
    }
  ]
}
```

---

### 4. Get Food Items
**GET** `/api/waiter/food-items`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "food_items": [
    {
      "id": 1,
      "name": "Grilled Chicken",
      "description": "Tender grilled chicken",
      "variants": [
        {
          "name": "Regular",
          "price": 15000
        },
        {
          "name": "Large",
          "price": 20000
        }
      ],
      "image": null
    }
  ]
}
```

---

### 5. Get Tables
**GET** `/api/waiter/tables`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "tables": [
    {
      "id": 1,
      "table_number": "T01",
      "table_name": "Table 1",
      "capacity": 4,
      "current_people": 2,
      "remaining_capacity": 2,
      "location": "Main Hall",
      "status": "occupied"
    }
  ]
}
```

---

### 6. Create Order
**POST** `/api/waiter/orders`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "items": [
    {
      "variant_id": 1,
      "quantity": 2,
      "price": 600.00
    },
    {
      "food_item_id": 1,
      "quantity": 1,
      "price": 15000.00,
      "product_name": "Grilled Chicken",
      "variant_name": "Regular",
      "notes": "No spicy"
    }
  ],
  "table_id": 1,
  "customer_name": "John Doe",
  "customer_phone": "+255712345678",
  "order_notes": "Customer prefers window seat"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-01",
    "total_amount": "16200.00",
    "status": "pending",
    "payment_status": "pending",
    "items": [...],
    "kitchen_order_items": [...],
    "table": {...}
  }
}
```

---

### 7. Get Order History
**GET** `/api/waiter/orders?page=1&per_page=20`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20)

**Response:**
```json
{
  "success": true,
  "orders": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

---

### 8. Get Completed Orders
**GET** `/api/waiter/orders/completed?page=1&per_page=20`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "orders": [...],
  "pagination": {...}
}
```

---

### 9. Get Order Details
**GET** `/api/waiter/orders/{orderId}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "order": {
    "id": 1,
    "order_number": "ORD-01",
    "total_amount": "16200.00",
    "status": "served",
    "payment_status": "paid",
    "items": [...],
    "kitchen_order_items": [...],
    "table": {...},
    "order_payments": [...]
  }
}
```

---

### 10. Record Payment
**POST** `/api/waiter/orders/{orderId}/payment`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body (Cash):**
```json
{
  "payment_method": "cash"
}
```

**Request Body (Mobile Money):**
```json
{
  "payment_method": "mobile_money",
  "mobile_money_number": "+255712345678",
  "transaction_reference": "QGH7X8Y9Z"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment recorded successfully",
  "order": {...}
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Payment can be recorded after order is marked as served"
}
```

---

### 11. Get Daily Sales
**GET** `/api/waiter/sales/daily?date=2025-12-07`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `date` (optional): Date in YYYY-MM-DD format (default: today)

**Response:**
```json
{
  "success": true,
  "date": "2025-12-07",
  "summary": {
    "total_sales": 450000.00,
    "total_orders": 25,
    "cash_collected": 300000.00,
    "mobile_money_collected": 150000.00
  },
  "orders": [...]
}
```

---

## Error Responses

All endpoints return errors in the following format:

```json
{
  "success": false,
  "error": "Error message here"
}
```

**HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Flutter Integration Example

### Login
```dart
final response = await http.post(
  Uri.parse('$baseUrl/api/waiter/login'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({
    'staff_id': 'STF20251201001',
    'password': 'password123',
  }),
);

final data = jsonDecode(response.body);
if (data['success']) {
  final token = data['token'];
  // Store token securely (e.g., using flutter_secure_storage)
  await storage.write(key: 'api_token', value: token);
}
```

### Authenticated Request
```dart
final token = await storage.read(key: 'api_token');
final response = await http.get(
  Uri.parse('$baseUrl/api/waiter/products'),
  headers: {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
  },
);
```

---

## Notes

1. **Token Expiration**: Tokens expire after 30 days. App should handle token refresh or re-login.
2. **Payment Recording**: Can only be done for orders that are:
   - Bar orders: status = "served"
   - Food orders: all kitchen items status = "completed"
   - Mixed orders: status = "served" AND all food items = "completed"
3. **Customer SMS**: Automatically sent when:
   - Order is created (if customer phone provided)
   - Food is ready (if customer phone provided)
   - Payment is recorded (if customer phone provided)

