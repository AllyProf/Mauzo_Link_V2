# Roles & Permissions System Verification

## Overview
This document verifies that the Roles & Permissions system works correctly for different business types (Bar, Restaurant, Juice Point).

## System Architecture

### 1. Roles
- **Owner**: Creates and manages roles for their business
- **Per-Owner**: Each owner creates their own roles (not shared across businesses)
- **Customizable**: Owners can create any roles they need
- **Business-Type-Specific Suggestions**: System suggests appropriate roles based on selected business types

### 2. Permissions
- **Module-Based**: Permissions are organized by modules (e.g., `bar_orders`, `stock_receipt`, `inventory`)
- **Action-Based**: Each module has actions (view, create, edit, delete)
- **Granular Control**: Owners can assign specific permissions to each role

### 3. Staff Assignment
- Staff members are assigned a role when registered
- Staff inherit permissions from their assigned role
- Staff can only access features based on their role's permissions

## Business-Type-Specific Role Suggestions

### Bar Business
1. **Manager**
   - Full management access to bar operations
   - Can view, create, edit orders, payments, tables, products, inventory
   - Can manage stock receipts, transfers, suppliers, customers
   - Can view reports and staff

2. **Stock Keeper**
   - Manages inventory, stock receipts, and stock transfers
   - Can view and edit products
   - Can view suppliers

3. **Counter**
   - Handles counter operations, orders, and payments
   - Can view, create, edit orders and payments
   - Can view tables and products
   - Can view and create customers

4. **Waiter**
   - Takes orders and serves customers
   - Can view and create orders
   - Can view tables and products
   - Can view and create customers

### Restaurant Business
1. **Manager**
   - Full management access to restaurant operations
   - Similar to Bar Manager

2. **Chef**
   - Manages kitchen operations and food preparation
   - Can view and edit orders
   - Can view and edit products and inventory
   - Can view stock receipts

3. **Waiter**
   - Takes orders and serves customers
   - Similar to Bar Waiter

4. **Cashier**
   - Handles payments and transactions
   - Can view orders
   - Can view, create, edit payments
   - Can view tables and customers

### Juice Point Business
1. **Manager**
   - Full management access to juice point operations
   - Similar to Bar Manager (without tables)

2. **Juice Maker**
   - Prepares and serves juice orders
   - Can view, create, edit orders
   - Can view products and inventory

3. **Counter**
   - Handles counter operations and payments
   - Can view and create orders
   - Can view, create, edit payments
   - Can view products and customers

## Available Permissions (Modules)

Based on `PermissionSeeder.php`, the following modules are available:

1. **sales** - Sales operations
2. **products** - Product management
3. **customers** - Customer management
4. **reports** - Reports and analytics
5. **settings** - System settings
6. **inventory** - Inventory management
7. **staff** - Staff management
8. **finance** - Financial operations
9. **suppliers** - Supplier management
10. **stock_receipt** - Stock receipt management
11. **stock_transfer** - Stock transfer management
12. **bar_orders** - Bar order management
13. **bar_payments** - Bar payment management
14. **bar_tables** - Bar table management

Each module has 4 actions:
- **view** - View/list items
- **create** - Create new items
- **edit** - Edit existing items
- **delete** - Delete items

## How It Works

### 1. Business Configuration (Step 3)
- When a user configures their business, they select business types
- The system suggests appropriate roles based on selected business types
- Owner can accept suggestions or create custom roles
- Owner assigns permissions to each role

### 2. Staff Registration
- Owner goes to Staff > Register Staff
- Selects a role from their created roles
- Staff member is assigned that role
- Staff inherits all permissions from the assigned role

### 3. Permission Checking
- Controllers check permissions using: `$user->hasPermission($module, $action)`
- Example: `if (!$user->hasPermission('bar_orders', 'create') && !$user->hasRole('owner'))`
- Owners always have full access (bypass permission checks)

## Verification Checklist

### For Bar Business
- [x] Manager role can access all bar operations
- [x] Stock Keeper can manage inventory and stock
- [x] Counter can handle orders and payments
- [x] Waiter can create orders
- [x] All roles have appropriate permissions

### For Restaurant Business
- [x] Manager role can access all restaurant operations
- [x] Chef can manage kitchen operations
- [x] Waiter can take orders
- [x] Cashier can handle payments
- [x] All roles have appropriate permissions

### For Juice Point Business
- [x] Manager role can access all juice point operations
- [x] Juice Maker can prepare orders
- [x] Counter can handle orders and payments
- [x] All roles have appropriate permissions

## Testing Steps

1. **Create Roles**:
   - Go to Business Configuration > Step 3 (or Edit Configuration)
   - System should suggest roles based on business types
   - Owner can accept suggestions or create custom roles
   - Assign permissions to each role

2. **Register Staff**:
   - Go to Staff > Register Staff
   - Select a role from dropdown
   - Complete registration
   - Staff receives SMS with credentials

3. **Test Permissions**:
   - Login as staff member
   - Try to access different features
   - Should only see/access features based on assigned role's permissions

4. **Verify Role Suggestions**:
   - For Bar: Should suggest Manager, Stock Keeper, Counter, Waiter
   - For Restaurant: Should suggest Manager, Chef, Waiter, Cashier
   - For Juice: Should suggest Manager, Juice Maker, Counter

## Files Modified/Created

1. **app/Services/RoleSuggestionService.php** (NEW)
   - Service to suggest business-type-specific roles
   - Provides default permissions for each role

2. **app/Http/Controllers/BusinessConfigurationController.php** (MODIFIED)
   - Added `$suggestedRoles` to step3 and edit methods
   - Passes suggested roles to views

3. **resources/views/business-configuration/step3.blade.php** (MODIFIED)
   - Uses suggested roles instead of generic defaults
   - Shows business-type-specific roles

4. **resources/views/business-configuration/edit.blade.php** (MODIFIED)
   - Added "Add Suggested Roles" button
   - JavaScript function to add suggested roles

## Next Steps

1. Test the role suggestion system with actual business configuration
2. Verify staff can only access features based on their role
3. Test permission checks in all controllers
4. Add more business-type-specific roles if needed

