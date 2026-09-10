@echo off
REM Nightly DB backup. Runs spark tpt:db-backup and pipes output to a log.
REM Schedule: daily at 02:00 (see install-tasks.bat).
cd /d C:\xampp\htdocs\tpt
"C:\xampp\php\php.exe" spark tpt:db-backup >> writable\logs\cron-backup.log 2>&1
