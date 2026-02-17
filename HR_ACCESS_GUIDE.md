# HR Dashboard Access Guide

## Access Requirements

To access the HR Dashboard, you need:

1. **User Account** - Must be logged in as:
   - Business Owner (has all permissions by default)
   - OR Staff member with HR permissions assigned

2. **Active Subscription** - Must have active payment/subscription

3. **Business Configuration** - Must have completed business setup

4. **HR Permissions** - Role must have HR permissions:
   - `hr.view` - View HR dashboard and data
   - `hr.create` - Create attendance, payroll, reviews
   - `hr.edit` - Edit attendance, approve leaves, update payroll
   - `hr.delete` - Delete records (if needed)

## Access URLs

- **HR Dashboard**: `/hr/dashboard`
- **Attendance**: `/hr/attendance`
- **Leaves**: `/hr/leaves`
- **Payroll**: `/hr/payroll`
- **Performance Reviews**: `/hr/performance-reviews`

## Setting Up HR Access

### Option 1: For Business Owner
Business owners automatically have access to all features, including HR.

**Login Credentials:**
- Use your owner account email and password
- Access: `/hr/dashboard`

### Option 2: For Staff Members

1. **Attach HR Permissions to a Role:**
   ```bash
   php attach_hr_permissions.php your-email@example.com Manager
   ```
   Replace:
   - `your-email@example.com` - Your owner account email
   - `Manager` - The role name you want to give HR access

2. **Assign Staff to that Role:**
   - Go to Staff Management
   - Edit the staff member
   - Assign them the role with HR permissions

3. **Staff Login:**
   - Staff logs in with their email and password
   - They will see HR menu if they have HR permissions

## Available HR Permissions

- **View HR** (`hr.view`) - Required for all HR pages
- **Create HR** (`hr.create`) - Create attendance, payroll, reviews
- **Edit HR** (`hr.edit`) - Edit attendance, approve/reject leaves, update payroll
- **Delete HR** (`hr.delete`) - Delete HR records (optional)

## Quick Setup Command

To quickly give HR access to a Manager role:

```bash
php attach_hr_permissions.php admin@mauzolink.com Manager
```

This will attach all HR permissions to the Manager role.

