# HR Setup - Next Steps

Since HR permissions are already created, complete the setup by running:

## Step 1: Create HR Menu Items

```bash
php create_hr_menu.php
```

This will:
- Create the HR parent menu in the sidebar
- Create submenu items (Dashboard, Attendance, Leaves, Payroll, Performance Reviews)
- Attach menu to all business types

## Step 2: Attach HR Permissions to Roles

Attach HR permissions to the roles that need access:

```bash
php attach_hr_permissions.php your-email@example.com Manager
```

Replace:
- `your-email@example.com` - Your owner account email
- `Manager` - The role name (can be Manager, Admin, HR Manager, etc.)

## Step 3: Verify Setup

```bash
php verify_hr_setup_final.php
```

This will check:
- ✓ HR permissions exist
- ✓ HR menu items created
- ✓ Roles have HR permissions

## Quick Complete Setup

Or run everything at once:

```bash
php setup_hr_complete.php
```

This script will:
1. Create HR permissions (if missing)
2. Create HR menu items
3. Attach HR permissions to Manager and Admin roles

## After Setup

Once complete, you can:
1. **Login** as owner or staff with HR permissions
2. **See HR menu** in the sidebar
3. **Access** `/hr/dashboard` to view HR dashboard

## HR Features Available

- **Attendance Management** - Track check-in/check-out, attendance status
- **Leave Management** - Approve/reject leave requests
- **Payroll Management** - Generate and manage staff payroll
- **Performance Reviews** - Track staff performance evaluations

---

**Status:** HR Permissions ✅ Created  
**Next:** Run `php setup_hr_complete.php` to finish setup

