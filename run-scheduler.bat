@echo off
echo ============================================
echo   LARAVEL SCHEDULER - AUTO RUN
echo ============================================
echo.
echo Scheduler dang chay... (Nhan Ctrl+C de dung)
echo.

:loop
php artisan schedule:run
timeout /t 60 /nobreak >nul
goto loop

