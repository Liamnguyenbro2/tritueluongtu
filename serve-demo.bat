@echo off
cd /d "%~dp0"
C:\php-8.5.6\php.exe artisan optimize:clear
C:\php-8.5.6\php.exe artisan serve --host=127.0.0.1 --port=8001
pause
