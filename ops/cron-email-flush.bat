@echo off
REM Drain the email_logs queue. Runs every 5 minutes.
cd /d C:\xampp\htdocs\tpt
"C:\xampp\php\php.exe" spark tpt:email-flush >> writable\logs\cron-email-flush.log 2>&1
