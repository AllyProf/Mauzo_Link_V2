@echo off
echo ========================================
echo Setting up Windows Task Scheduler for
echo Automatic Attendance Sync
echo ========================================
echo.

REM Get the current directory
set SCRIPT_DIR=%~dp0
set ARTISAN_PATH=%SCRIPT_DIR%artisan
set PHP_PATH=C:\xampp\php\php.exe

echo Script Directory: %SCRIPT_DIR%
echo Artisan Path: %ARTISAN_PATH%
echo PHP Path: %PHP_PATH%
echo.

REM Create a batch file that will be run by Task Scheduler
echo Creating scheduler batch file...
echo @echo off > "%SCRIPT_DIR%run_scheduler.bat"
echo cd /d "%SCRIPT_DIR%" >> "%SCRIPT_DIR%run_scheduler.bat"
echo "%PHP_PATH%" artisan schedule:run >> "%SCRIPT_DIR%run_scheduler.bat"

echo.
echo ========================================
echo Batch file created: run_scheduler.bat
echo ========================================
echo.
echo Next steps:
echo 1. Open Task Scheduler (search in Windows)
echo 2. Create Basic Task
echo 3. Name: "Laravel Attendance Sync"
echo 4. Trigger: When computer starts (or Daily)
echo 5. Action: Start a program
echo 6. Program: %SCRIPT_DIR%run_scheduler.bat
echo 7. Start in: %SCRIPT_DIR%
echo 8. In Triggers, set to repeat every 1 minute
echo.
echo OR run this command manually every minute:
echo "%PHP_PATH%" "%ARTISAN_PATH%" schedule:run
echo.
pause

