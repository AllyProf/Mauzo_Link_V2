# Toast Notification System Documentation

## Overview
This document describes the professional toast notification system implemented in MauzoLinkV2 using SweetAlert2.

## Features
- **Non-intrusive notifications**: Toast appears in the top-right corner (top-end position)
- **Auto-dismiss**: Notifications automatically close after 3 seconds (customizable)
- **Timer progress bar**: Visual indicator showing time remaining
- **Hover pause/resume**: Timer pauses when hovering over the toast
- **Multiple types**: Success, Error, Warning, and Info notifications

## Implementation Details

### Toast Mixin Configuration
```javascript
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer);
    toast.addEventListener('mouseleave', Swal.resumeTimer);
  }
});
```

### Global Function
```javascript
function showToast(type, message, title = null, duration = 3000)
```

**Parameters:**
- `type` (string, required): The notification type - 'success', 'error', 'warning', or 'info'
- `message` (string, required): The main message to display
- `title` (string, optional): Optional title for the toast. If not provided, message is used as title
- `duration` (number, optional): Duration in milliseconds before auto-dismiss (default: 3000)

## Usage Examples

### Basic Usage

#### Success Notification
```javascript
showToast('success', 'Operation completed successfully!');
```

#### Error Notification
```javascript
showToast('error', 'Something went wrong!');
```

#### Warning Notification
```javascript
showToast('warning', 'Please check your input');
```

#### Info Notification
```javascript
showToast('info', 'New update available');
```

### Advanced Usage

#### With Custom Title
```javascript
showToast('success', 'Your profile has been updated', 'Profile Updated');
```

#### With Custom Duration (5 seconds)
```javascript
showToast('info', 'This message will stay for 5 seconds', 'Important Notice', 5000);
```

#### With All Parameters
```javascript
showToast('warning', 'Your session will expire soon', 'Session Timeout', 10000);
```

## Backend Integration (Laravel)

### Controller Examples

#### Success Message
```php
return redirect()->back()->with('success', 'Data saved successfully!');
```

#### Error Message
```php
return redirect()->back()->with('error', 'Failed to save data');
```

#### Warning Message
```php
return redirect()->back()->with('warning', 'Some fields are missing');
```

#### Info Message
```php
return redirect()->back()->with('info', 'Processing your request');
```

### AJAX Response Examples

#### Success Response
```javascript
$.ajax({
  url: '/api/save-data',
  method: 'POST',
  data: formData,
  success: function(response) {
    showToast('success', response.message, 'Success!');
  },
  error: function(xhr) {
    showToast('error', xhr.responseJSON.message, 'Error!');
  }
});
```

#### Fetch API Example
```javascript
fetch('/api/update-profile', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify(profileData)
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    showToast('success', data.message, 'Profile Updated');
  } else {
    showToast('error', data.message, 'Update Failed');
  }
})
.catch(error => {
  showToast('error', 'An unexpected error occurred', 'Error');
});
```

## Migration Guide

### Replacing Old SweetAlert Calls

#### Before (Modal Dialog)
```javascript
Swal.fire({
  icon: 'success',
  title: 'Success!',
  text: 'Operation completed',
  confirmButtonColor: '#940000'
});
```

#### After (Toast Notification)
```javascript
showToast('success', 'Operation completed', 'Success!');
```

### Replacing Old Alert/Confirm Dialogs

#### Before
```javascript
alert('Data saved successfully!');
```

#### After
```javascript
showToast('success', 'Data saved successfully!');
```

## Best Practices

### 1. Choose Appropriate Message Types
- **Success**: For completed actions (save, update, delete)
- **Error**: For failures and exceptions
- **Warning**: For validation issues or cautionary messages
- **Info**: For informational messages that don't require action

### 2. Keep Messages Concise
```javascript
// Good
showToast('success', 'Profile updated');

// Avoid overly long messages
showToast('success', 'Your profile has been successfully updated with all the new information you provided including your name, email, and phone number');
```

### 3. Use Titles for Context
```javascript
// Good - provides context
showToast('error', 'Please fill all required fields', 'Validation Error');

// Less clear
showToast('error', 'Please fill all required fields');
```

### 4. Adjust Duration for Important Messages
```javascript
// Standard message (3 seconds)
showToast('info', 'Changes saved');

// Important message (longer duration)
showToast('warning', 'Your session will expire in 2 minutes', 'Session Warning', 8000);
```

## Common Use Cases

### Form Submission
```javascript
$('#myForm').on('submit', function(e) {
  e.preventDefault();
  
  $.ajax({
    url: $(this).attr('action'),
    method: 'POST',
    data: $(this).serialize(),
    success: function(response) {
      showToast('success', 'Form submitted successfully!');
      $('#myForm')[0].reset();
    },
    error: function(xhr) {
      const message = xhr.responseJSON?.message || 'Failed to submit form';
      showToast('error', message, 'Submission Failed');
    }
  });
});
```

### Delete Confirmation with Toast
```javascript
function deleteItem(itemId) {
  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#940000',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      // Perform delete operation
      $.ajax({
        url: '/api/delete/' + itemId,
        method: 'DELETE',
        success: function() {
          showToast('success', 'Item deleted successfully', 'Deleted!');
        },
        error: function() {
          showToast('error', 'Failed to delete item', 'Error');
        }
      });
    }
  });
}
```

### Login Success
```php
// In LoginController
public function login(Request $request)
{
    // ... authentication logic
    
    if (Auth::attempt($credentials)) {
        return redirect()->intended('dashboard')
            ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
    }
    
    return back()->with('error', 'Invalid credentials');
}
```

### Validation Errors
```javascript
// Display validation errors as toast
function showValidationErrors(errors) {
  Object.keys(errors).forEach(function(field) {
    errors[field].forEach(function(message) {
      showToast('warning', message, 'Validation Error', 4000);
    });
  });
}

// Usage in AJAX error handler
error: function(xhr) {
  if (xhr.status === 422) {
    showValidationErrors(xhr.responseJSON.errors);
  } else {
    showToast('error', 'An error occurred', 'Error');
  }
}
```

## Troubleshooting

### Toast Not Appearing
1. Ensure SweetAlert2 is loaded before your script
2. Check browser console for JavaScript errors
3. Verify the function is called with correct parameters

### Toast Appearing Too Quickly
```javascript
// Increase duration
showToast('info', 'Read this carefully', 'Important', 10000);
```

### Multiple Toasts Overlapping
SweetAlert2 automatically queues toasts. If you need to clear all toasts:
```javascript
Swal.close();
```

## Styling Customization

If you need to customize the toast appearance, you can add CSS:

```css
.swal2-toast {
  font-family: 'Century Gothic', sans-serif !important;
}

.swal2-toast .swal2-title {
  font-size: 16px;
  font-weight: 600;
}

.swal2-toast .swal2-icon {
  margin: 0 10px 0 0;
}
```

## Summary

The toast notification system provides a modern, non-intrusive way to communicate with users. Use `showToast(type, message, title, duration)` throughout your application for consistent user feedback.

**Key Points:**
- ✅ Non-intrusive top-right notifications
- ✅ Auto-dismiss with visual timer
- ✅ Pause on hover
- ✅ Support for success, error, warning, and info types
- ✅ Seamless Laravel session integration
- ✅ Easy to use in AJAX calls and form submissions
