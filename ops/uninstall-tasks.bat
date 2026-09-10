@echo off
REM Removes every TPT_* scheduled task. Run as Administrator.
setlocal

for %%T in (TPT_Backup TPT_RestoreTest TPT_ComplianceDigest TPT_EmailFlush TPT_GpsRefresh TPT_Heartbeat TPT_QueueWorker) do (
    schtasks /Delete /F /TN "%%T" >nul 2>&1
    if not errorlevel 1 echo Removed %%T
)
echo Done.
