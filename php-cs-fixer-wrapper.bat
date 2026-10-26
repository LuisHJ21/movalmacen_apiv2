@echo off
cd /d "D:\WEBSITES\movAlmacen\API2"
call "D:\WEBSITES\movAlmacen\API2\vendor\bin\php-cs-fixer.bat" %*
exit /b %ERRORLEVEL%