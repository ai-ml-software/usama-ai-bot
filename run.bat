@echo off
REM Nexus AI Bot — Windows one-click launcher
cd /d "%~dp0"
echo ============================================
echo   Nexus AI Bot
echo ============================================
echo.
if not exist ".env" (
    echo First run detected — running setup wizard...
    python setup.py
    echo.
)
echo Starting bot... (Ctrl+C to stop)
python -m bot.main
pause
