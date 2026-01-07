@echo off
echo Starting Family Manager Server...
echo.
echo Open this link in your browser:
echo http://localhost:8000/admin.html
echo.
echo Press Ctrl+C to stop the server
echo.
php -S localhost:8000 -t .
pause
