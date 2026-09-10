@echo off
REM Weekly compliance digest — emails admins about expiring driver licenses
REM and vehicle documents. Schedule: Monday at 09:00.
cd /d C:\xampp\htdocs\tpt
"C:\xampp\php\php.exe" spark tpt:compliance-digest >> writable\logs\cron-compliance.log 2>&1
