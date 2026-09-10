@echo off
REM Monthly backup-restore drill. Verifies backups are actually importable.
REM Schedule: 1st of every month at 04:00.
cd /d C:\xampp\htdocs\tpt
"C:\xampp\php\php.exe" spark tpt:db-restore-test >> writable\logs\cron-restore-test.log 2>&1
