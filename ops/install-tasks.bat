@echo off
REM ============================================================
REM  TPT Aggregator — Windows Task Scheduler installer
REM
REM  Run as Administrator. Registers seven tasks:
REM    TPT_Backup            daily   02:00
REM    TPT_RestoreTest       monthly 1st 04:00
REM    TPT_ComplianceDigest  weekly  Monday 09:00
REM    TPT_EmailFlush        every 5 min
REM    TPT_GpsRefresh        every 1 min (06:00-22:00)
REM    TPT_Heartbeat         every 5 min
REM    TPT_QueueWorker       at startup + restarts if it exits
REM
REM  To remove everything later: run uninstall-tasks.bat.
REM ============================================================

setlocal
set ROOT=C:\xampp\htdocs\tpt
set OPS=%ROOT%\ops

echo Installing TPT scheduled tasks under your user account...
echo.

REM ── Nightly DB backup ──────────────────────────────────────
schtasks /Create /F /TN "TPT_Backup" /TR "\"%OPS%\cron-backup.bat\"" ^
    /SC DAILY /ST 02:00 /RL HIGHEST
if errorlevel 1 goto :fail

REM ── Monthly backup-restore drill ───────────────────────────
schtasks /Create /F /TN "TPT_RestoreTest" /TR "\"%OPS%\cron-restore-test.bat\"" ^
    /SC MONTHLY /D 1 /ST 04:00 /RL HIGHEST
if errorlevel 1 goto :fail

REM ── Weekly compliance digest ───────────────────────────────
schtasks /Create /F /TN "TPT_ComplianceDigest" /TR "\"%OPS%\cron-compliance-digest.bat\"" ^
    /SC WEEKLY /D MON /ST 09:00
if errorlevel 1 goto :fail

REM ── Email queue drain (every 5 min) ────────────────────────
schtasks /Create /F /TN "TPT_EmailFlush" /TR "\"%OPS%\cron-email-flush.bat\"" ^
    /SC MINUTE /MO 5
if errorlevel 1 goto :fail

REM ── GPS refresh (every minute, 06:00–22:00) ────────────────
schtasks /Create /F /TN "TPT_GpsRefresh" /TR "\"%OPS%\cron-gps-refresh.bat\"" ^
    /SC MINUTE /MO 1 /ST 06:00 /ET 22:00 /K
if errorlevel 1 goto :fail

REM ── Heartbeat for /_health ─────────────────────────────────
schtasks /Create /F /TN "TPT_Heartbeat" /TR "\"%OPS%\cron-heartbeat.bat\"" ^
    /SC MINUTE /MO 5
if errorlevel 1 goto :fail

REM ── Queue worker — starts at boot, auto-restarts ───────────
REM Note: the worker exits after 200 jobs or 128 MB; Task Scheduler
REM re-launches it on its next minute tick (set via MINUTE 1).
schtasks /Create /F /TN "TPT_QueueWorker" /TR "\"%OPS%\queue-worker.bat\"" ^
    /SC MINUTE /MO 1 /RL HIGHEST
if errorlevel 1 goto :fail

REM Run the worker + heartbeat once immediately so /_health goes green
schtasks /Run /TN "TPT_Heartbeat"   >nul 2>&1
schtasks /Run /TN "TPT_QueueWorker" >nul 2>&1

echo.
echo ============================================================
echo  All TPT tasks registered.
echo  View them in Task Scheduler under \ (or run "schtasks /Query /TN TPT_*").
echo ============================================================
echo.
echo Tip: run ops\verify-tasks.bat to confirm everything is scheduled.
goto :EOF

:fail
echo.
echo Task creation failed. Run this script as Administrator.
exit /b 1
