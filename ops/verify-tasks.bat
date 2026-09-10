@echo off
REM Quick health-check for the scheduled tasks. No admin rights needed.
echo.
echo Registered TPT tasks:
echo ----------------------
schtasks /Query /TN TPT_Backup           /FO LIST 2>nul | findstr /B /R "TaskName Status Next Last"
echo.
schtasks /Query /TN TPT_RestoreTest      /FO LIST 2>nul | findstr /B /R "TaskName Status Next Last"
echo.
schtasks /Query /TN TPT_ComplianceDigest /FO LIST 2>nul | findstr /B /R "TaskName Status Next Last"
echo.
schtasks /Query /TN TPT_EmailFlush       /FO LIST 2>nul | findstr /B /R "TaskName Status Next Last"
echo.
schtasks /Query /TN TPT_GpsRefresh       /FO LIST 2>nul | findstr /B /R "TaskName Status Next Last"
echo.
schtasks /Query /TN TPT_Heartbeat        /FO LIST 2>nul | findstr /B /R "TaskName Status Next Last"
echo.
schtasks /Query /TN TPT_QueueWorker      /FO LIST 2>nul | findstr /B /R "TaskName Status Next Last"
echo.
echo Heartbeat file age:
echo ----------------------
if exist writable\cron-heartbeat (
    for %%I in (writable\cron-heartbeat) do echo Last touched: %%~tI
) else (
    echo (no heartbeat yet — TPT_Heartbeat hasn't run)
)
echo.
echo Health endpoint:
echo ----------------------
curl -s http://localhost/tpt/public/_health
echo.
