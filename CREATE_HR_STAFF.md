# Create HR Staff Member

## Quick Command

Run this command to create the HR staff member:

```bash
php create_hr_simple.php
```

## Manual Steps (if script doesn't work)

If the script doesn't work, you can create the staff member manually through the web interface:

1. **Login** as owner
2. Go to **Staff Management** → **Register New Staff**
3. Fill in the form:
   - **Full Name:** HR Manager
   - **Email:** hr@mauzo.com
   - **Gender:** Other
   - **Phone Number:** +255710000000
   - **Role:** Select Manager or HR Manager role
   - **Salary:** 0
4. **Save** the staff member
5. After creation, **edit** the staff member and change the password to: `password`

## Verify Staff Was Created

Run this to check:

```bash
php verify_hr_staff.php
```

## Credentials

- **Email:** hr@mauzo.com
- **Password:** password
- **Login URL:** /login
- **HR Dashboard:** /hr/dashboard

## Troubleshooting

If staff creation fails:

1. **Check if owner exists:**
   ```bash
   php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$owner = App\Models\User::where('role', '!=', 'admin')->first(); echo \$owner ? 'Owner: ' . \$owner->email : 'No owner found';"
   ```

2. **Check if roles exist:**
   ```bash
   php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$owner = App\Models\User::where('role', '!=', 'admin')->first(); if (\$owner) { \$roles = App\Models\Role::where('user_id', \$owner->id)->get(); echo 'Roles: ' . \$roles->count(); }"
   ```

3. **Check if staff already exists:**
   ```bash
   php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$staff = App\Models\Staff::where('email', 'hr@mauzo.com')->first(); echo \$staff ? 'Staff exists: ' . \$staff->full_name : 'Staff NOT found';"
   ```

