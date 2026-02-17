<?php
/**
 * Direct Marketing Login - Creates a login link
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "========================================\n";
echo "Marketing Staff Login Information\n";
echo "========================================\n\n";

echo "LOGIN CREDENTIALS:\n";
echo "  Email: marketing@medalion.com\n";
echo "  Password: MANAGER\n\n";

echo "IMPORTANT NOTES:\n";
echo "  1. Email is CASE-SENSITIVE: marketing@medalion.com (lowercase)\n";
echo "  2. Password is CASE-SENSITIVE: MANAGER (all uppercase)\n";
echo "  3. Make sure there are no extra spaces\n";
echo "  4. Try copying and pasting the credentials\n\n";

echo "TROUBLESHOOTING:\n";
echo "  If login still fails:\n";
echo "  1. Clear browser cache (Ctrl+Shift+Delete)\n";
echo "  2. Clear cookies for this site\n";
echo "  3. Try incognito/private window\n";
echo "  4. Try a different browser\n";
echo "  5. Check browser console (F12) for errors\n";
echo "  6. Make sure you're on: http://127.0.0.1:8000/login\n";
echo "     or: http://192.168.100.101:8000/login\n\n";

echo "ALTERNATIVE: Try with a different email\n";
echo "  I can create a new staff with a different email.\n";
echo "  Would you like me to create: marketing-staff@medalion.com?\n";







