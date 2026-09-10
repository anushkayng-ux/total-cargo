@echo off
REM Background queue worker. Long-running — runs forever, processing jobs.
REM `--max=200 --memory=128` means: exit after 200 jobs OR if RAM exceeds 128 MB,
REM so Task Scheduler can re-launch a fresh worker every time it ends.
cd /d C:\xampp\htdocs\tpt
"C:\xampp\php\php.exe" spark tpt:queue-work --max=200 --memory=128 >> writable\logs\queue-worker.log 2>&1
