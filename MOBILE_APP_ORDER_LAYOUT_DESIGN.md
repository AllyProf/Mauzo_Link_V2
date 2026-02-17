# Mobile App - Order Creation Layout Design

## Overview
This document provides the structure, layout design, and data flow for the order creation feature in the Waiter Mobile App.

---

## 📱 Screen Flow

```
1. Dashboard/Home Screen
   ↓
2. Create Order Screen
   ├── Step 1: Select Table (Optional)
   ├── Step 2: Customer Info (Optional)
   ├── Step 3: Add Items (Drinks & Food)
   └── Step 4: Review & Submit
```

---

## 🎨 Screen Layouts

### **Screen 1: Create Order - Main Screen**

```
┌─────────────────────────────────────┐
│  [← Back]  Create New Order         │
├─────────────────────────────────────┤
│                                     │
│  📋 ORDER DETAILS                    │
│  ┌─────────────────────────────┐   │
│  │ Table: [Select Table ▼]    │   │
│  │ Customer: [Name Input]     │   │
│  │ Phone: [Phone Input]       │   │
│  └─────────────────────────────┘   │
│                                     │
│  🍽️ ITEMS                           │
│  ┌─────────────────────────────┐   │
│  │ [Drinks Tab] [Food Tab]     │   │
│  ├─────────────────────────────┤   │
│  │ [Product Grid/List]          │   │
│  │                              │   │
│  │ [Item 1] [Item 2] [Item 3]  │   │
│  │ [Item 4] [Item 5] [Item 6]  │   │
│  │                              │   │
│  └─────────────────────────────┘   │
│                                     │
│  📝 ORDER SUMMARY                   │
│  ┌─────────────────────────────┐   │
│  │ Selected Items: 3           │   │
│  │ Total: Tsh 25,000           │   │
│  │                              │   │
│  │ [View Cart] [Place Order]   │   │
│  └─────────────────────────────┘   │
└─────────────────────────────────────┘
```

### **Screen 2: Product Selection (Drinks)**

```
┌─────────────────────────────────────┐
│  [← Back]  Select Drinks            │
├─────────────────────────────────────┤
│  [Search Bar] 🔍                    │
│  [Category Filter: All ▼]           │
├─────────────────────────────────────┤
│                                     │
│  ┌──────────┐  ┌──────────┐        │
│  │ [Image]  │  │ [Image]  │        │
│  │ Coca Cola│  │ Pepsi     │        │
│  │ 700ml    │  │ 500ml     │        │
│  │ Tsh 600  │  │ Tsh 500   │        │
│  │ Stock:50 │  │ Stock:30  │        │
│  │ [+ Add]  │  │ [+ Add]   │        │
│  └──────────┘  └──────────┘        │
│                                     │
│  ┌──────────┐  ┌──────────┐        │
│  │ [Image]  │  │ [Image]  │        │
│  │ Fanta    │  │ Sprite   │        │
│  │ ...      │  │ ...      │        │
│  └──────────┘  └──────────┘        │
│                                     │
└─────────────────────────────────────┘
```

### **Screen 3: Product Selection (Food)**

```
┌─────────────────────────────────────┐
│  [← Back]  Select Food              │
├─────────────────────────────────────┤
│  [Search Bar] 🔍                    │
├─────────────────────────────────────┤
│                                     │
│  ┌─────────────────────────────┐   │
│  │ [Image]                      │   │
│  │ Grilled Chicken              │   │
│  │ Tender grilled chicken       │   │
│  │                              │   │
│  │ Variants:                    │   │
│  │ ○ Regular - Tsh 15,000       │   │
│  │ ● Large - Tsh 20,000         │   │
│  │                              │   │
│  │ Quantity: [ - ] 1 [ + ]      │   │
│  │ Notes: [Special instructions]│   │
│  │                              │   │
│  │ [Add to Order]               │   │
│  └─────────────────────────────┘   │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ [Image]                      │   │
│  │ Fried Rice                   │   │
│  │ ...                          │   │
│  └─────────────────────────────┘   │
│                                     │
└─────────────────────────────────────┘
```

### **Screen 4: Cart/Order Summary**

```
┌─────────────────────────────────────┐
│  [← Back]  Order Summary            │
├─────────────────────────────────────┤
│                                     │
│  📋 ORDER ITEMS                     │
│  ┌─────────────────────────────┐   │
│  │ 🍺 Coca Cola 700ml          │   │
│  │    Qty: 2 × Tsh 600        │   │
│  │    [Edit] [Remove]         │   │
│  ├─────────────────────────────┤   │
│  │ 🍗 Grilled Chicken (Large)  │   │
│  │    Qty: 1 × Tsh 20,000     │   │
│  │    Note: No spicy          │   │
│  │    [Edit] [Remove]         │   │
│  ├─────────────────────────────┤   │
│  │ Subtotal: Tsh 21,200       │   │
│  │ Total: Tsh 21,200          │   │
│  └─────────────────────────────┘   │
│                                     │
│  📝 ORDER NOTES                     │
│  ┌─────────────────────────────┐   │
│  │ [Add notes...]              │   │
│  └─────────────────────────────┘   │
│                                     │
│  [Place Order]                      │
│                                     │
└─────────────────────────────────────┘
```

---

## 📊 Data Structure

### **1. API Response: Get Products (Drinks)**

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
      "product_image": "https://example.com/image.jpg"
    }
  ]
}
```

### **2. API Response: Get Food Items**

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
      "image": "https://example.com/chicken.jpg"
    }
  ]
}
```

### **3. API Response: Get Tables**

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

### **4. Request Body: Create Order**

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
      "price": 20000.00,
      "product_name": "Grilled Chicken",
      "variant_name": "Large",
      "notes": "No spicy"
    }
  ],
  "table_id": 1,
  "customer_name": "John Doe",
  "customer_phone": "+255712345678",
  "order_notes": "Customer prefers window seat"
}
```

### **5. Response: Create Order**

```json
{
  "success": true,
  "message": "Order created successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-20",
    "total_amount": "21200.00",
    "status": "pending",
    "payment_status": "pending",
    "table": {
      "id": 1,
      "table_number": "T01"
    },
    "items": [
      {
        "id": 1,
        "product_variant": {
          "product": {
            "name": "Coca Cola"
          }
        },
        "quantity": 2,
        "unit_price": "600.00",
        "total_price": "1200.00"
      }
    ],
    "kitchen_order_items": [
      {
        "id": 1,
        "food_item_name": "Grilled Chicken",
        "variant_name": "Large",
        "quantity": 1,
        "unit_price": "20000.00",
        "total_price": "20000.00",
        "special_instructions": "No spicy",
        "status": "pending"
      }
    ]
  }
}
```

---

## 🏗️ Component Structure (Flutter/Dart Example)

### **1. Order Item Model**

```dart
class OrderItem {
  int? variantId;          // For drinks
  int? foodItemId;         // For food
  int quantity;
  double price;
  String? productName;      // Required for food items
  String? variantName;      // Optional variant name
  String? notes;            // Special instructions
  
  OrderItem({
    this.variantId,
    this.foodItemId,
    required this.quantity,
    required this.price,
    this.productName,
    this.variantName,
    this.notes,
  });
  
  Map<String, dynamic> toJson() {
    if (foodItemId != null) {
      return {
        'food_item_id': foodItemId,
        'quantity': quantity,
        'price': price,
        'product_name': productName,
        'variant_name': variantName,
        'notes': notes,
      };
    } else {
      return {
        'variant_id': variantId,
        'quantity': quantity,
        'price': price,
      };
    }
  }
}
```

### **2. Create Order Request Model**

```dart
class CreateOrderRequest {
  List<OrderItem> items;
  int? tableId;
  String? customerName;
  String? customerPhone;
  String? orderNotes;
  
  CreateOrderRequest({
    required this.items,
    this.tableId,
    this.customerName,
    this.customerPhone,
    this.orderNotes,
  });
  
  Map<String, dynamic> toJson() {
    return {
      'items': items.map((item) => item.toJson()).toList(),
      if (tableId != null) 'table_id': tableId,
      if (customerName != null) 'customer_name': customerName,
      if (customerPhone != null) 'customer_phone': customerPhone,
      if (orderNotes != null) 'order_notes': orderNotes,
    };
  }
}
```

### **3. Product Model (Drinks)**

```dart
class Product {
  int id;
  String productName;
  String variant;
  String measurement;
  String packaging;
  int quantity;
  double sellingPrice;
  String category;
  bool isAlcoholic;
  String? productImage;
  
  Product({
    required this.id,
    required this.productName,
    required this.variant,
    required this.measurement,
    required this.packaging,
    required this.quantity,
    required this.sellingPrice,
    required this.category,
    required this.isAlcoholic,
    this.productImage,
  });
  
  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      productName: json['product_name'],
      variant: json['variant'],
      measurement: json['measurement'],
      packaging: json['packaging'],
      quantity: json['quantity'],
      sellingPrice: (json['selling_price'] as num).toDouble(),
      category: json['category'],
      isAlcoholic: json['is_alcoholic'],
      productImage: json['product_image'],
    );
  }
}
```

### **4. Food Item Model**

```dart
class FoodItem {
  int id;
  String name;
  String description;
  List<FoodVariant> variants;
  String? image;
  
  FoodItem({
    required this.id,
    required this.name,
    required this.description,
    required this.variants,
    this.image,
  });
  
  factory FoodItem.fromJson(Map<String, dynamic> json) {
    return FoodItem(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      variants: (json['variants'] as List)
          .map((v) => FoodVariant.fromJson(v))
          .toList(),
      image: json['image'],
    );
  }
}

class FoodVariant {
  String name;
  double price;
  
  FoodVariant({
    required this.name,
    required this.price,
  });
  
  factory FoodVariant.fromJson(Map<String, dynamic> json) {
    return FoodVariant(
      name: json['name'],
      price: (json['price'] as num).toDouble(),
    );
  }
}
```

### **5. Table Model**

```dart
class Table {
  int id;
  String tableNumber;
  String tableName;
  int capacity;
  int currentPeople;
  int remainingCapacity;
  String location;
  String status;
  
  Table({
    required this.id,
    required this.tableNumber,
    required this.tableName,
    required this.capacity,
    required this.currentPeople,
    required this.remainingCapacity,
    required this.location,
    required this.status,
  });
  
  factory Table.fromJson(Map<String, dynamic> json) {
    return Table(
      id: json['id'],
      tableNumber: json['table_number'],
      tableName: json['table_name'],
      capacity: json['capacity'],
      currentPeople: json['current_people'],
      remainingCapacity: json['remaining_capacity'],
      location: json['location'],
      status: json['status'],
    );
  }
}
```

---

## 🎯 UI Component Recommendations

### **1. Product Card (Drinks)**
- Image (if available)
- Product name
- Variant info (measurement, packaging)
- Price
- Stock quantity (show if low stock)
- Add button (+)
- Alcoholic indicator (if applicable)

### **2. Food Item Card**
- Image (if available)
- Food name
- Description
- Variant selector (radio buttons or dropdown)
- Quantity selector (+/-)
- Notes input field
- Add to order button

### **3. Cart Item Widget**
- Item name and variant
- Quantity with +/- controls
- Price per unit
- Total price
- Edit/Remove buttons
- Special instructions (if any)

### **4. Order Summary Widget**
- List of selected items
- Subtotal
- Total amount
- Order notes input
- Place order button

---

## 🔄 Workflow Steps

### **Step 1: Initialize Order Screen**
1. Fetch products (GET `/api/waiter/products`)
2. Fetch food items (GET `/api/waiter/food-items`)
3. Fetch tables (GET `/api/waiter/tables`)
4. Display in respective tabs/sections

### **Step 2: Add Drink Items**
1. User taps on a drink product
2. Show quantity selector (default: 1)
3. Add to cart with `variant_id` and `price`
4. Update order summary

### **Step 3: Add Food Items**
1. User taps on a food item
2. Show variant selector (if multiple variants)
3. User selects variant
4. User sets quantity
5. User can add special instructions (optional)
6. Add to cart with `food_item_id`, `product_name`, `variant_name`, `price`, `notes`
7. Update order summary

### **Step 4: Review & Submit**
1. Display all selected items in cart
2. Show total amount
3. Allow editing/removing items
4. Optional: Add order notes
5. User taps "Place Order"
6. Send POST request to `/api/waiter/orders`
7. Show success message with order number
8. Navigate to order details or order history

---

## ✅ Validation Rules

### **Order Items:**
- At least 1 item required
- Each item must have either `variant_id` OR `food_item_id`
- Quantity must be ≥ 1
- Price must be ≥ 0
- For food items: `product_name` is required

### **Table:**
- Must exist in database (if provided)
- Optional field

### **Customer Info:**
- Phone format validation (if provided)
- Optional fields

### **Stock Check:**
- For drinks: Check if `quantity` in stock ≥ requested quantity
- Show error if insufficient stock

---

## 🎨 Design Recommendations

### **Colors:**
- Primary: Restaurant brand color
- Success: Green (#4CAF50)
- Warning: Orange (#FF9800)
- Error: Red (#F44336)
- Background: Light gray (#F5F5F5)

### **Typography:**
- Headers: Bold, 18-20sp
- Body: Regular, 14-16sp
- Prices: Bold, 16-18sp
- Labels: Medium, 12-14sp

### **Spacing:**
- Card padding: 16dp
- Item spacing: 8-12dp
- Section spacing: 24dp

### **Icons:**
- Use Material Icons or Font Awesome
- Consistent icon size: 24dp
- Color: Match text color

---

## 📝 Notes for Implementation

1. **State Management:** Use Provider, Riverpod, or Bloc for managing order state
2. **Caching:** Cache products, food items, and tables to reduce API calls
3. **Offline Support:** Consider storing orders locally if offline
4. **Error Handling:** Show user-friendly error messages
5. **Loading States:** Show loading indicators during API calls
6. **Success Feedback:** Show success message with order number after creation
7. **Navigation:** Clear navigation flow between screens
8. **Accessibility:** Ensure proper labels and contrast ratios

---

## 🔗 API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/waiter/products` | GET | Get available drinks |
| `/api/waiter/food-items` | GET | Get available food items |
| `/api/waiter/tables` | GET | Get available tables |
| `/api/waiter/orders` | POST | Create new order |

---

## 📱 Example Flutter Widget Structure

```dart
// Main Order Screen
class CreateOrderScreen extends StatefulWidget {
  @override
  _CreateOrderScreenState createState() => _CreateOrderScreenState();
}

class _CreateOrderScreenState extends State<CreateOrderScreen> {
  // State variables
  List<OrderItem> cartItems = [];
  Table? selectedTable;
  String? customerName;
  String? customerPhone;
  String? orderNotes;
  
  // Methods
  void addDrinkToCart(Product product) { }
  void addFoodToCart(FoodItem foodItem, FoodVariant variant, int quantity, String? notes) { }
  void removeFromCart(int index) { }
  void updateQuantity(int index, int newQuantity) { }
  Future<void> submitOrder() async { }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Create Order')),
      body: Column(
        children: [
          // Table & Customer Info Section
          OrderDetailsSection(),
          // Product Selection Tabs
          TabBarView(
            children: [
              DrinksTab(onAddToCart: addDrinkToCart),
              FoodTab(onAddToCart: addFoodToCart),
            ],
          ),
          // Order Summary
          OrderSummaryWidget(
            items: cartItems,
            onPlaceOrder: submitOrder,
          ),
        ],
      ),
    );
  }
}
```

---

This structure provides a complete foundation for implementing the order creation feature in your mobile app!




