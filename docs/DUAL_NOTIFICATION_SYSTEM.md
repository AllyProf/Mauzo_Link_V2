# Dual Notification System - Complete Guide

## 🎯 Overview

MauzoLinkV2 now has **TWO notification systems** that work together:

1. **🍞 Toast Notifications** - Non-intrusive, auto-dismiss notifications (top-right corner)
2. **🔔 SweetAlert Modals** - Attention-grabbing modal dialogs (center screen)

Choose the right one based on the importance and context of your message!

---

## 📊 When to Use Each System

### Use **Toast Notifications** for:
- ✅ Success confirmations (saved, updated, deleted)
- ✅ Quick status updates
- ✅ Non-critical information
- ✅ Background process completions
- ✅ When user should continue working

### Use **SweetAlert Modals** for:
- ⚠️ Critical errors requiring attention
- ⚠️ Confirmations before destructive actions
- ⚠️ Important warnings
- ⚠️ When user must acknowledge the message
- ⚠️ Multi-step processes requiring user input

---

## 🍞 Toast Notifications (Non-Intrusive)

### JavaScript Usage

#### Basic Toast
```javascript
showToast('success', 'Data saved successfully!');
showToast('error', 'Failed to save data');
showToast('warning', 'Please check your input');
showToast('info', 'Processing your request...');
```

#### Toast with Title
```javascript
showToast('success', 'Your profile has been updated', 'Profile Updated');
showToast('error', 'Please fill all required fields', 'Validation Error');
```

#### Toast with Custom Duration
```javascript
showToast('info', 'This will stay for 5 seconds', 'Notice', 5000);
showToast('warning', 'Session expiring soon', 'Warning', 10000);
```

### PHP/Laravel Usage (Controllers)

#### Regular Toast (Default)
```php
// These will show as toast notifications
return redirect()->back()->with('success', 'Product created successfully!');
return redirect()->back()->with('error', 'Failed to create product');
return redirect()->back()->with('warning', 'Stock is running low');
return redirect()->back()->with('info', 'Processing your request');
```

---

## 🔔 SweetAlert Modals (Attention-Grabbing)

### JavaScript Usage

#### Basic Modal Alert
```javascript
showAlert('success', 'Your account has been created!', 'Welcome!');
showAlert('error', 'Critical system error occurred', 'System Error');
showAlert('warning', 'This action cannot be undone', 'Warning');
showAlert('info', 'Please read the terms and conditions', 'Important');
```

#### Modal with Custom Options
```javascript
showAlert('success', 'Payment processed successfully', 'Payment Complete', {
  confirmButtonText: 'View Receipt',
  showCancelButton: true,
  cancelButtonText: 'Close'
});
```

#### Confirmation Dialog
```javascript
showConfirm(
  'This will permanently delete the item',
  'Are you sure?',
  function() {
    // User clicked Yes
    console.log('Confirmed!');
    // Perform delete action
  },
  function() {
    // User clicked No (optional)
    console.log('Cancelled');
  }
);
```

#### Advanced Confirmation Example
```javascript
function deleteProduct(productId) {
  showConfirm(
    'This product will be permanently deleted. This action cannot be undone.',
    'Delete Product?',
    function() {
      // User confirmed - proceed with deletion
      $.ajax({
        url: '/api/products/' + productId,
        method: 'DELETE',
        success: function() {
          showToast('success', 'Product deleted successfully', 'Deleted');
          location.reload();
        },
        error: function() {
          showAlert('error', 'Failed to delete product', 'Error');
        }
      });
    }
  );
}
```

### PHP/Laravel Usage (Controllers)

#### Modal Alert (Critical Messages)
```php
// These will show as modal dialogs requiring user acknowledgment
return redirect()->back()->with('alert_success', 'Payment processed successfully!');
return redirect()->back()->with('alert_error', 'Critical: Database connection failed');
return redirect()->back()->with('alert_warning', 'Your subscription expires in 3 days');
return redirect()->back()->with('alert_info', 'System maintenance scheduled for tomorrow');
```

---

## 🎨 Complete Examples

### Example 1: Form Submission with Toast
```javascript
$('#productForm').on('submit', function(e) {
  e.preventDefault();
  
  $.ajax({
    url: $(this).attr('action'),
    method: 'POST',
    data: $(this).serialize(),
    success: function(response) {
      showToast('success', 'Product created successfully');
      $('#productForm')[0].reset();
    },
    error: function(xhr) {
      showToast('error', xhr.responseJSON.message, 'Error');
    }
  });
});
```

### Example 2: Delete with Confirmation Modal
```javascript
function deleteItem(itemId) {
  showConfirm(
    'You won\'t be able to revert this!',
    'Are you sure?',
    function() {
      // User confirmed
      $.ajax({
        url: '/api/items/' + itemId,
        method: 'DELETE',
        success: function() {
          showToast('success', 'Item deleted successfully', 'Deleted!');
          $('#item-' + itemId).remove();
        },
        error: function() {
          showAlert('error', 'Failed to delete item. Please try again.', 'Error');
        }
      });
    }
  );
}
```

### Example 3: Multi-Step Process
```javascript
function processOrder(orderId) {
  // Step 1: Show confirmation modal
  showConfirm(
    'Process this order and send confirmation to customer?',
    'Process Order?',
    function() {
      // Step 2: Show processing toast
      showToast('info', 'Processing order...', 'Please Wait', 5000);
      
      // Step 3: Make API call
      $.ajax({
        url: '/api/orders/' + orderId + '/process',
        method: 'POST',
        success: function(response) {
          // Step 4: Show success modal with details
          showAlert('success', 
            'Order #' + orderId + ' has been processed. Customer notified via SMS.',
            'Order Processed',
            {
              confirmButtonText: 'View Order',
              showCancelButton: true,
              cancelButtonText: 'Close'
            }
          );
        },
        error: function(xhr) {
          // Step 5: Show error modal if failed
          showAlert('error', 
            xhr.responseJSON.message || 'Failed to process order',
            'Processing Failed'
          );
        }
      });
    }
  );
}
```

### Example 4: Laravel Controller with Both Systems
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        try {
            $product = Product::create($request->all());
            
            // Use toast for quick success feedback
            return redirect()->route('products.index')
                ->with('success', 'Product created successfully!');
                
        } catch (\Exception $e) {
            // Use modal alert for critical errors
            return redirect()->back()
                ->with('alert_error', 'Critical error: ' . $e->getMessage());
        }
    }
    
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            
            // Use toast for delete confirmation
            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully');
                
        } catch (\Exception $e) {
            // Use modal for error
            return redirect()->back()
                ->with('alert_error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
    
    public function processPayment(Request $request)
    {
        // Process payment...
        
        if ($paymentSuccessful) {
            // Use modal for important payment confirmation
            return redirect()->route('dashboard')
                ->with('alert_success', 'Payment of $' . $amount . ' processed successfully! Receipt sent to your email.');
        } else {
            // Use modal for payment failure
            return redirect()->back()
                ->with('alert_error', 'Payment failed: ' . $errorMessage);
        }
    }
}
```

---

## 📋 Quick Reference Table

| Scenario | Use | Function | Session Key |
|----------|-----|----------|-------------|
| Save success | Toast | `showToast('success', 'Saved!')` | `with('success', ...)` |
| Update success | Toast | `showToast('success', 'Updated!')` | `with('success', ...)` |
| Delete success | Toast | `showToast('success', 'Deleted!')` | `with('success', ...)` |
| Validation error | Toast | `showToast('warning', 'Check input')` | `with('warning', ...)` |
| Info message | Toast | `showToast('info', 'Processing...')` | `with('info', ...)` |
| Critical error | Modal | `showAlert('error', 'Critical!')` | `with('alert_error', ...)` |
| Payment success | Modal | `showAlert('success', 'Paid!')` | `with('alert_success', ...)` |
| Delete confirm | Modal | `showConfirm('Delete?', ...)` | N/A |
| Important warning | Modal | `showAlert('warning', 'Warning!')` | `with('alert_warning', ...)` |

---

## 🎯 Function Signatures

### showToast()
```javascript
showToast(type, message, title = null, duration = 3000)
```
- **type**: 'success', 'error', 'warning', 'info'
- **message**: The notification message
- **title**: Optional title (default: uses message)
- **duration**: Duration in ms (default: 3000)

### showAlert()
```javascript
showAlert(type, message, title = null, options = {})
```
- **type**: 'success', 'error', 'warning', 'info', 'question'
- **message**: The alert message
- **title**: Optional title (default: capitalized type)
- **options**: Additional SweetAlert2 options

### showConfirm()
```javascript
showConfirm(message, title = 'Are you sure?', onConfirm, onCancel = null)
```
- **message**: The confirmation message
- **title**: Dialog title (default: 'Are you sure?')
- **onConfirm**: Callback function when user clicks Yes
- **onCancel**: Optional callback when user clicks No

---

## 🎨 Session Keys Summary

### Toast Notifications (Non-Intrusive)
```php
->with('success', 'Message')   // Green toast
->with('error', 'Message')     // Red toast
->with('warning', 'Message')   // Yellow toast
->with('info', 'Message')      // Blue toast
```

### Modal Alerts (Attention-Grabbing)
```php
->with('alert_success', 'Message')   // Green modal
->with('alert_error', 'Message')     // Red modal
->with('alert_warning', 'Message')   // Yellow modal
->with('alert_info', 'Message')      // Blue modal
```

---

## 💡 Best Practices

### ✅ DO:
- Use toasts for routine operations (CRUD operations)
- Use modals for critical errors and important confirmations
- Use `showConfirm()` before destructive actions (delete, etc.)
- Keep toast messages short and clear
- Use modal alerts for payment confirmations
- Provide clear action buttons in modals

### ❌ DON'T:
- Don't use modals for every success message (annoying!)
- Don't use toasts for critical errors (user might miss them)
- Don't show multiple modals at once
- Don't use overly long messages in toasts
- Don't forget to handle both confirm and cancel in `showConfirm()`

---

## 🔧 Customization

### Change Toast Position
```javascript
// Edit in dashboard.blade.php
const Toast = Swal.mixin({
  position: 'top-start',  // Options: top, top-start, top-end, center, bottom, etc.
  // ... other options
});
```

### Change Modal Button Colors
```javascript
// Already configured with brand colors
confirmButtonColor: '#940000',  // Your brand color
cancelButtonColor: '#6c757d'    // Gray
```

### Add Custom Modal Options
```javascript
showAlert('success', 'Message', 'Title', {
  confirmButtonText: 'Custom Button',
  showCancelButton: true,
  cancelButtonText: 'Cancel',
  timer: 5000,  // Auto-close after 5 seconds
  allowOutsideClick: false  // Prevent closing by clicking outside
});
```

---

## 🚀 Summary

You now have **two powerful notification systems**:

1. **🍞 Toasts** - Quick, non-intrusive feedback
   - Use `showToast()` in JavaScript
   - Use `->with('success', ...)` in PHP

2. **🔔 Modals** - Important, attention-grabbing alerts
   - Use `showAlert()` for messages
   - Use `showConfirm()` for confirmations
   - Use `->with('alert_success', ...)` in PHP

Choose wisely based on message importance! 🎯
