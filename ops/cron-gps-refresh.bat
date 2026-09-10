@echo off
REM Refresh GPS pings for active trips. Every minute, 06:00–22:00.
cd /d C:\xampp\htdocs\tpt
"C:\xampp\php\php.exe" spark tpt:gps-refresh >> writable\logs\cron-gps.log 2>&1
