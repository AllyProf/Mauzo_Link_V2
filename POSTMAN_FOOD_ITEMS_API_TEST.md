# Postman API Test Guide - Food Items

## 📋 Step-by-Step Testing Instructions

### **Step 1: Login to Get Authentication Token**

#### **Request Setup:**
- **Method:** `POST`
- **URL:** `http://your-domain.com/api/waiter/login`
  - Replace `your-domain.com` with your actual domain (e.g., `10.143.103.160:8000`)

#### **Headers:**
```
Content-Type: application/json
```

#### **Body (raw JSON):**
```json
{
  "email": "waiter@mauzo.com",
  "password": "NANCY"
}
```

**OR using staff_id:**
```json
{
  "staff_id": "STF2025120004",
  "password": "NANCY"
}
```

#### **Expected Response:**
```json
{
  "success": true,
  "token": "random_token_string_here_60_characters",
  "waiter": {
    "id": 5,
    "staff_id": "STF2025120004",
    "name": "NANCY",
    "email": "waiter@mauzo.com",
    "phone_number": "+255710490428"
  },
  "expires_at": "2026-01-06T08:12:29+00:00"
}
```

**⚠️ IMPORTANT:** Copy the `token` value from the response. You'll need it for the next step.

---

### **Step 2: Get Food Items**

#### **Request Setup:**
- **Method:** `GET`
- **URL:** `http://your-domain.com/api/waiter/food-items`
  - Example: `http://10.143.103.160:8000/api/waiter/food-items`

#### **Headers:**
```
Authorization: Bearer {paste_your_token_here}
Content-Type: application/json
```

**Example:**
```
Authorization: Bearer random_token_string_here_60_characters
Content-Type: application/json
```

#### **Body:**
- No body required (GET request)

---

## 📝 Complete Postman Collection

### **Collection 1: Login**

```
POST http://10.143.103.160:8000/api/waiter/login

Headers:
Content-Type: application/json

Body (raw JSON):
{
  "email": "waiter@mauzo.com",
  "password": "NANCY"
}
```

### **Collection 2: Get Food Items**

```
GET http://10.143.103.160:8000/api/waiter/food-items

Headers:
Authorization: Bearer {{auth_token}}
Content-Type: application/json
```

**Note:** Use Postman variables `{{auth_token}}` to store the token from login response.

---

## 🔧 Postman Setup Instructions

### **Option 1: Manual Setup**

1. **Create New Request:**
   - Click "New" → "HTTP Request"
   - Name it: "Get Food Items"

2. **Set Method and URL:**
   - Method: `GET`
   - URL: `http://10.143.103.160:8000/api/waiter/food-items`

3. **Add Headers:**
   - Click "Headers" tab
   - Add:
     - Key: `Authorization`
     - Value: `Bearer YOUR_TOKEN_HERE` (replace with actual token from login)

4. **Send Request:**
   - Click "Send" button

---

### **Option 2: Using Postman Variables (Recommended)**

1. **First, Login and Save Token:**
   - Create a "Login" request
   - After successful login, go to "Tests" tab
   - Add this script:
   ```javascript
   if (pm.response.code === 200) {
       var jsonData = pm.response.json();
       if (jsonData.success && jsonData.token) {
           pm.environment.set("auth_token", jsonData.token);
           console.log("Token saved:", jsonData.token);
       }
   }
   ```

2. **Create Food Items Request:**
   - Method: `GET`
   - URL: `http://10.143.103.160:8000/api/waiter/food-items`
   - Headers:
     - `Authorization`: `Bearer {{auth_token}}`

3. **Send Request:**
   - Token will be automatically inserted from environment variable

---

## 📊 Expected Response Format

### **Success Response (200 OK):**

```json
{
  "success": true,
  "food_items": [
    {
      "id": 1,
      "name": "Grilled Chicken",
      "description": "Tender grilled chicken with spices",
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
    },
    {
      "id": 2,
      "name": "Fried Rice",
      "description": "Delicious fried rice with vegetables",
      "variants": [
        {
          "name": "Small",
          "price": 8000
        },
        {
          "name": "Regular",
          "price": 12000
        },
        {
          "name": "Large",
          "price": 15000
        }
      ],
      "image": "https://example.com/fried-rice.jpg"
    }
  ]
}
```

### **Error Response - No Token (401 Unauthorized):**

```json
{
  "message": "Unauthenticated."
}
```

### **Error Response - Invalid Token (401 Unauthorized):**

```json
{
  "message": "Invalid or expired token"
}
```

---

## 🧪 Quick Test Scripts for Postman

### **Test Script (in Postman "Tests" tab):**

```javascript
// Check if request was successful
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

// Check response structure
pm.test("Response has success field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
    pm.expect(jsonData.success).to.be.true;
});

// Check food_items array exists
pm.test("Response contains food_items array", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('food_items');
    pm.expect(jsonData.food_items).to.be.an('array');
});

// Validate food item structure
pm.test("Food items have required fields", function () {
    var jsonData = pm.response.json();
    if (jsonData.food_items.length > 0) {
        var item = jsonData.food_items[0];
        pm.expect(item).to.have.property('id');
        pm.expect(item).to.have.property('name');
        pm.expect(item).to.have.property('description');
        pm.expect(item).to.have.property('variants');
        pm.expect(item.variants).to.be.an('array');
    }
});

// Log food items count
pm.test("Log food items count", function () {
    var jsonData = pm.response.json();
    console.log("Total food items:", jsonData.food_items.length);
});
```

---

## 📱 Complete Example URLs

### **For Local Development:**
```
GET http://localhost:8000/api/waiter/food-items
```

### **For Network IP:**
```
GET http://10.143.103.160:8000/api/waiter/food-items
```

### **For Production:**
```
GET https://yourdomain.com/api/waiter/food-items
```

---

## 🔍 Troubleshooting

### **Problem: 401 Unauthorized**
**Solution:**
- Make sure you're including the `Authorization` header
- Check that the token is valid (not expired)
- Verify the token format: `Bearer {token}` (with space after Bearer)
- Try logging in again to get a fresh token

### **Problem: 404 Not Found**
**Solution:**
- Check the URL is correct
- Verify the API routes are registered
- Make sure you're using `/api/waiter/food-items` (not `/waiter/food-items`)

### **Problem: Empty food_items array**
**Solution:**
- This is normal if no food items are configured
- Check the database `food_items` table
- Verify `is_available = true` for food items
- Check `user_id` matches the waiter's owner

### **Problem: CORS Error**
**Solution:**
- If testing from browser/Postman web, check CORS settings
- For Postman desktop app, CORS shouldn't be an issue

---

## 📋 Sample cURL Command

If you prefer using cURL instead of Postman:

```bash
# First, login and get token
curl -X POST http://10.143.103.160:8000/api/waiter/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "waiter@mauzo.com",
    "password": "NANCY"
  }'

# Then use the token to get food items
curl -X GET http://10.143.103.160:8000/api/waiter/food-items \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

---

## ✅ Checklist for Testing

- [ ] Login endpoint works and returns token
- [ ] Token is copied correctly
- [ ] Authorization header is set correctly
- [ ] URL is correct (includes `/api/waiter/food-items`)
- [ ] Request method is `GET`
- [ ] Response status is `200 OK`
- [ ] Response contains `success: true`
- [ ] Response contains `food_items` array
- [ ] Food items have required fields (id, name, description, variants)
- [ ] Variants array contains objects with `name` and `price`

---

## 🎯 Quick Copy-Paste for Postman

### **Request 1: Login**
```
POST http://10.143.103.160:8000/api/waiter/login
Content-Type: application/json

{
  "email": "waiter@mauzo.com",
  "password": "NANCY"
}
```

### **Request 2: Get Food Items**
```
GET http://10.143.103.160:8000/api/waiter/food-items
Authorization: Bearer PASTE_TOKEN_FROM_LOGIN_HERE
Content-Type: application/json
```

---

**Replace `10.143.103.160:8000` with your actual server address!**




