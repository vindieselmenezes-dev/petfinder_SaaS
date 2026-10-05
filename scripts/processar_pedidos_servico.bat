@echo off
setlocal
set "PHP_EXE=%~dp0..\..\..\php\php.exe"
if not exist "%PHP_EXE%" exit /b 1
"%PHP_EXE%" "%~dp0processar_pedidos_servico.php"
exit /b %ERRORLEVEL%
