# Waiter Reconciliation & Notifications API Documentation

## Overview
This API allows waiters to view their reconciliation data and receive notifications when payments are recorded by the Counter or Chef.

---

## Authentication
All endpoints require authentication using the Bearer token obtained from the login endpoint.

**Header:**
```
Authorization: Bearer {api_token}
```

---

## Endpoints

### 1. Get Waiter Reconciliation

Get reconciliation data for a specific date.

**Endpoint:** `GET /api/waiter/reconciliation`

**Query Parameters:**
- `date` (optional): Date in format `Y-m-d` (default: today)

**Response:**
```json
{
  "success": true,
  "date": "2025-12-08",
  "reconciliation": {
    "bar_sales": 6000.00,
    "food_sales": 280000.00,
    "total_sales": 286000.00,
    "bar_orders_count": 10,
    "food_orders_count": 4,
    "total_orders_count": 14,
    "paid_bar_amount": 2400.00,
    "paid_food_amount": 0.00,
    "total_paid_amount": 2400.00,
    "cash_collected": 2400.00,
    "mobile_money_collected": 0.00,
    "has_unpaid_bar_orders": true,
    "has_unpaid_food_orders": true,
    "status": "partial",
    "submitted_amount": 2400.00,
    "expected_amount": 286000.00,
    "difference": -283600.00
  },
  "reconciliation_record": {
    "id": 1,
    "status": "submitted",
    "submitted_amount": 2400.00,
    "expected_amount": 286000.00,
    "difference": -283600.00,
    "submitted_at": "2025-12-08T10:30:00Z",
    "verified_at": null
  }
}
```

**Status Values:**
- `pending`: Has unpaid orders
- `partial`: Some orders paid but not all
- `paid`: All orders paid and amounts match
- `submitted`: Waiter submitted reconciliation
- `verified`: Counter/Chef verified reconciliation
- `disputed`: Reconciliation disputed

---

### 2. Get Notifications

Get waiter notifications (unread or all).

**Endpoint:** `GET /api/waiter/notifications`

**Query Parameters:**
- `limit` (optional): Number of notifications to return (default: 50)
- `unread_only` (optional): Boolean, if true returns only unread notifications (default: false)

**Response:**
```json
{
  "success": true,
  "unread_count": 3,
  "notifications": [
    {
      "id": 1,
      "type": "payment_recorded",
      "title": "Bar Orders Marked as Paid",
      "message": "Counter has marked 3 bar order(s) as paid for Dec 08, 2025. Total amount: TSh 2,400",
      "data": {
        "date": "2025-12-08",
        "orders_count": 3,
        "total_amount": 2400.00,
        "order_type": "bar",
        "marked_by": "counter"
      },
      "is_read": false,
      "read_at": null,
      "created_at": "2025-12-08T10:30:00Z"
    },
    {
      "id": 2,
      "type": "payment_recorded",
      "title": "Food Orders Marked as Paid",
      "message": "Chef has marked 2 food order(s) as paid for Dec 08, 2025. Total amount: TSh 50,000",
      "data": {
        "date": "2025-12-08",
        "orders_count": 2,
        "total_amount": 50000.00,
        "order_type": "food",
        "marked_by": "chef"
      },
      "is_read": false,
      "read_at": null,
      "created_at": "2025-12-08T11:15:00Z"
    }
  ]
}
```

**Notification Types:**
- `payment_recorded`: Payment recorded by Counter or Chef

---

### 3. Mark Notification as Read

Mark a specific notification as read.

**Endpoint:** `POST /api/waiter/notifications/{notificationId}/read`

**Response:**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

### 4. Mark All Notifications as Read

Mark all unread notifications as read.

**Endpoint:** `POST /api/waiter/notifications/read-all`

**Response:**
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

---

## Notification Triggers

### When Counter Marks Bar Orders as Paid
When the Counter marks bar orders (drinks) as paid in bulk:
- Notification type: `payment_recorded`
- Title: "Bar Orders Marked as Paid"
- Contains: date, orders count, total amount, order type: "bar", marked_by: "counter"

### When Chef Marks Food Orders as Paid
When the Chef marks food orders as paid in bulk:
- Notification type: `payment_recorded`
- Title: "Food Orders Marked as Paid"
- Contains: date, orders count, total amount, order type: "food", marked_by: "chef"

---

## Flutter Implementation Example

### Get Reconciliation
```dart
Future<Map<String, dynamic>> getReconciliation({String? date}) async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/waiter/reconciliation${date != null ? '?date=$date' : ''}'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  } else {
    throw Exception('Failed to load reconciliation');
  }
}
```

### Get Notifications
```dart
Future<Map<String, dynamic>> getNotifications({bool unreadOnly = false, int limit = 50}) async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/waiter/notifications?unread_only=$unreadOnly&limit=$limit'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  } else {
    throw Exception('Failed to load notifications');
  }
}
```

### Mark Notification as Read
```dart
Future<void> markNotificationRead(int notificationId) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/waiter/notifications/$notificationId/read'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  if (response.statusCode != 200) {
    throw Exception('Failed to mark notification as read');
  }
}
```

### Mark All Notifications as Read
```dart
Future<void> markAllNotificationsRead() async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/waiter/notifications/read-all'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  if (response.statusCode != 200) {
    throw Exception('Failed to mark all notifications as read');
  }
}
```

---

## Error Responses

All endpoints may return the following error responses:

**401 Unauthorized:**
```json
{
  "success": false,
  "error": "Unauthorized. Invalid or expired token."
}
```

**404 Not Found:**
```json
{
  "success": false,
  "error": "Notification not found"
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "error": "Internal server error"
}
```

---

## Best Practices

1. **Polling for Notifications**: Poll the notifications endpoint every 30-60 seconds to check for new notifications
2. **Badge Count**: Use `unread_count` from the notifications response to show a badge on the notifications icon
3. **Date Selection**: Allow waiters to select different dates to view reconciliation history
4. **Real-time Updates**: Consider implementing WebSocket or push notifications for real-time updates (future enhancement)

---

## Testing with Postman

### Get Reconciliation
```
GET {{base_url}}/api/waiter/reconciliation?date=2025-12-08
Authorization: Bearer {{token}}
```

### Get Notifications
```
GET {{base_url}}/api/waiter/notifications?unread_only=true&limit=20
Authorization: Bearer {{token}}
```

### Mark Notification as Read
```
POST {{base_url}}/api/waiter/notifications/1/read
Authorization: Bearer {{token}}
```

### Mark All as Read
```
POST {{base_url}}/api/waiter/notifications/read-all
Authorization: Bearer {{token}}
```




