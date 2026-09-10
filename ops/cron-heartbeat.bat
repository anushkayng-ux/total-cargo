@echo off
REM Touch the heartbeat file so /_health knows cron is alive. Every 5 min.
cd /d C:\xampp\htdocs\tpt
type nul >> writable\cron-heartbeat
copy /b writable\cron-heartbeat +,, writable\cron-heartbeat >nul
