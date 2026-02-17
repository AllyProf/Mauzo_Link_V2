# Order API: Drinks vs Food Items - How It Works

## Overview
The order creation API handles **drinks** and **food items** differently. This document explains how the API distinguishes between them and processes each type.

---

## 🔑 Key Distinction

The API uses **different fields** to identify drinks vs food:

| Type | Identifier Field | Additional Required Fields |
|------|-----------------|---------------------------|
| **Drinks** | `variant_id` | `quantity`, `price` |
| **Food** | `food_item_id` | `quantity`, `price`, `product_name` |

---

## 📊 API Flow

### **Step 1: Fetch Available Items**

#### **Get Drinks** - `GET /api/waiter/products`
Returns product variants (drinks) available in counter stock.

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 1,                    // ← This is the variant_id
      "product_name": "Coca Cola",
      "variant": "700 ml - Crates",
      "measurement": "700 ml",
      "packaging": "Crates",
      "quantity": 50,             // Stock available
      "selling_price": 600.00,    // Price per unit
      "category": "Soft Drinks",
      "is_alcoholic": false,
      "product_image": null
    }
  ]
}
```

#### **Get Food Items** - `GET /api/waiter/food-items`
Returns food items with their variants.

**Response:**
```json
{
  "success": true,
  "food_items": [
    {
      "id": 1,                    // ← This is the food_item_id
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

## 🛒 Step 2: Create Order - Request Structure

### **Mixed Order Example (Drinks + Food)**

```json
{
  "items": [
    {
      // DRINK ITEM - Uses variant_id
      "variant_id": 1,              // ← From products API (variant.id)
      "quantity": 2,
      "price": 600.00               // ← Use selling_price from products API
    },
    {
      // FOOD ITEM - Uses food_item_id
      "food_item_id": 1,            // ← From food-items API (food_item.id)
      "quantity": 1,
      "price": 20000.00,            // ← Use price from selected variant
      "product_name": "Grilled Chicken",  // ← Required for food items
      "variant_name": "Large",      // ← Optional: variant name selected
      "notes": "No spicy"          // ← Optional: special instructions
    }
  ],
  "table_id": 1,
  "customer_name": "John Doe",
  "customer_phone": "+255712345678",
  "order_notes": "Window seat preferred"
}
```

---

## 🔍 How API Processes Items

### **Processing Logic in `createOrder()` Method:**

```php
foreach ($validated['items'] as $item) {
    // 1. CHECK IF IT'S A FOOD ITEM
    if (isset($item['food_item_id']) && $item['food_item_id'] !== null) {
        // ✅ IT'S A FOOD ITEM
        // - Add to kitchen_order_items array
        // - Store in kitchen_order_items table
        // - Status: 'pending' (chef will handle)
        // - Can include special_instructions
        continue;
    }
    
    // 2. CHECK IF IT'S A DRINK ITEM
    if (isset($item['variant_id']) && $item['variant_id']) {
        // ✅ IT'S A DRINK ITEM
        // - Validate stock availability
        // - Deduct from counter stock
        // - Add to order_items array
        // - Store in order_items table
    }
}
```

---

## 📋 Item Structure Comparison

### **Drink Item Structure**

```json
{
  "variant_id": 1,        // Required: ID from products API
  "quantity": 2,          // Required: Number of units
  "price": 600.00        // Required: Price per unit
}
```

**What happens:**
1. ✅ Validates `variant_id` exists in `product_variants` table
2. ✅ Checks stock availability in counter location
3. ✅ Deducts stock quantity
4. ✅ Creates record in `order_items` table
5. ✅ Links to `product_variant_id`

---

### **Food Item Structure**

```json
{
  "food_item_id": 1,              // Required: ID from food-items API
  "quantity": 1,                  // Required: Number of units
  "price": 20000.00,              // Required: Price from selected variant
  "product_name": "Grilled Chicken",  // Required: Food item name
  "variant_name": "Large",        // Optional: Selected variant name
  "notes": "No spicy"             // Optional: Special instructions
}
```

**What happens:**
1. ✅ Validates `food_item_id` exists in `food_items` table
2. ✅ Creates record in `kitchen_order_items` table
3. ✅ Status set to `'pending'` (chef will update)
4. ✅ Stores `special_instructions` if provided
5. ✅ No stock deduction (food items don't have stock tracking)

---

## 🗄️ Database Storage

### **Drinks → `order_items` Table**

```sql
order_items
├── id
├── order_id
├── product_variant_id  ← Links to product_variants
├── quantity
├── unit_price
└── total_price
```

### **Food → `kitchen_order_items` Table**

```sql
kitchen_order_items
├── id
├── order_id
├── food_item_name      ← Stored as text (not linked)
├── variant_name        ← Stored as text
├── quantity
├── unit_price
├── total_price
├── special_instructions
└── status             ← 'pending', 'preparing', 'ready', 'completed'
```

---

## ✅ Validation Rules

### **For Drink Items:**
- ✅ `variant_id` must exist in `product_variants` table
- ✅ `quantity` must be ≥ 1
- ✅ `price` must be ≥ 0
- ✅ Stock must be available (quantity in counter ≥ requested quantity)
- ❌ `food_item_id` should NOT be present

### **For Food Items:**
- ✅ `food_item_id` must exist in `food_items` table
- ✅ `quantity` must be ≥ 1
- ✅ `price` must be ≥ 0
- ✅ `product_name` is REQUIRED
- ✅ `variant_name` is optional
- ✅ `notes` is optional (max 500 characters)
- ❌ `variant_id` should NOT be present

### **Mutual Exclusivity:**
- Each item must have **EITHER** `variant_id` **OR** `food_item_id`
- **NOT BOTH** at the same time
- Validation: `'items.*.variant_id' => 'required_without:items.*.food_item_id'`
- Validation: `'items.*.food_item_id' => 'required_without:items.*.variant_id'`

---

## 🔄 Complete Flow Example

### **1. Mobile App Fetches Data**

```javascript
// Fetch drinks
GET /api/waiter/products
Response: { products: [{ id: 1, product_name: "Coca Cola", ... }] }

// Fetch food items
GET /api/waiter/food-items
Response: { food_items: [{ id: 1, name: "Grilled Chicken", variants: [...] }] }
```

### **2. User Selects Items**

**User adds drink:**
- Selects "Coca Cola 700ml" from products
- Uses `variant_id: 1` from products response
- Uses `selling_price: 600.00` from products response

**User adds food:**
- Selects "Grilled Chicken" → "Large" variant
- Uses `food_item_id: 1` from food-items response
- Uses `price: 20000` from selected variant
- Enters `product_name: "Grilled Chicken"`
- Enters `variant_name: "Large"`
- Optionally adds `notes: "No spicy"`

### **3. Mobile App Builds Request**

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
  "customer_phone": "+255712345678"
}
```

### **4. API Processes Order**

```
1. Validates all items
2. For drink item (variant_id: 1):
   - Checks stock: ✅ Available
   - Deducts 2 units from counter stock
   - Adds to order_items table
3. For food item (food_item_id: 1):
   - Validates food_item_id exists
   - Adds to kitchen_order_items table
   - Status: 'pending'
   - Stores special_instructions: "No spicy"
4. Calculates total: (2 × 600) + (1 × 20000) = 21,200
5. Creates order record
6. Sends SMS notifications
7. Returns success response
```

### **5. API Response**

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

## 🎯 Key Differences Summary

| Aspect | Drinks | Food Items |
|--------|--------|------------|
| **API Endpoint** | `/api/waiter/products` | `/api/waiter/food-items` |
| **Identifier** | `variant_id` | `food_item_id` |
| **Stock Check** | ✅ Yes (counter stock) | ❌ No |
| **Stock Deduction** | ✅ Yes (automatic) | ❌ No |
| **Database Table** | `order_items` | `kitchen_order_items` |
| **Status Tracking** | ❌ No | ✅ Yes (pending → preparing → ready → completed) |
| **Special Instructions** | ❌ No | ✅ Yes (notes field) |
| **Variant Selection** | Pre-defined (from products) | User selects from variants array |
| **Price Source** | From `selling_price` in products API | From selected variant in food-items API |
| **Required Fields** | `variant_id`, `quantity`, `price` | `food_item_id`, `quantity`, `price`, `product_name` |

---

## 💡 Implementation Tips for Mobile App

### **1. When Adding a Drink:**
```dart
OrderItem addDrink(Product product, int quantity) {
  return OrderItem(
    variantId: product.id,           // From products API
    quantity: quantity,
    price: product.sellingPrice,     // From products API
  );
}
```

### **2. When Adding Food:**
```dart
OrderItem addFood(FoodItem foodItem, FoodVariant variant, int quantity, String? notes) {
  return OrderItem(
    foodItemId: foodItem.id,          // From food-items API
    quantity: quantity,
    price: variant.price,             // From selected variant
    productName: foodItem.name,       // Required!
    variantName: variant.name,        // Optional
    notes: notes,                     // Optional
  );
}
```

### **3. Building Request:**
```dart
CreateOrderRequest buildRequest(List<OrderItem> items) {
  return CreateOrderRequest(
    items: items.map((item) => item.toJson()).toList(),
    tableId: selectedTable?.id,
    customerName: customerName,
    customerPhone: customerPhone,
    orderNotes: orderNotes,
  );
}
```

---

## ⚠️ Common Mistakes to Avoid

1. ❌ **Sending both `variant_id` and `food_item_id`** in the same item
2. ❌ **Missing `product_name`** for food items
3. ❌ **Using wrong price** (should use API response prices, not hardcoded)
4. ❌ **Not checking stock** before adding drinks (API will reject if insufficient)
5. ❌ **Sending `variant_id` for food items** or `food_item_id` for drinks

---

## 📝 Example: Complete Order Request

```json
{
  "items": [
    {
      "variant_id": 5,
      "quantity": 3,
      "price": 500.00
    },
    {
      "variant_id": 12,
      "quantity": 1,
      "price": 1200.00
    },
    {
      "food_item_id": 3,
      "quantity": 2,
      "price": 15000.00,
      "product_name": "Fried Rice",
      "variant_name": "Regular",
      "notes": "Extra vegetables"
    },
    {
      "food_item_id": 7,
      "quantity": 1,
      "price": 25000.00,
      "product_name": "Grilled Fish",
      "variant_name": "Large"
    }
  ],
  "table_id": 5,
  "customer_name": "Jane Smith",
  "customer_phone": "+255755123456",
  "order_notes": "Birthday celebration"
}
```

**This order contains:**
- 2 drink items (variant_id: 5 and 12)
- 2 food items (food_item_id: 3 and 7)

**Total calculation:**
- Drinks: (3 × 500) + (1 × 1200) = 2,700
- Food: (2 × 15,000) + (1 × 25,000) = 55,000
- **Grand Total: 57,700**

---

This structure allows the API to properly route drinks to the bar/counter and food items to the kitchen, with appropriate stock management and status tracking for each type.




