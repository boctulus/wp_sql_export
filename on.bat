@echo off
setlocal

:: Obtener el nombre del directorio actual
for %%I in (.) do set "PLUGIN_NAME=%%~nxI"

:: Activar el plugin
wp plugin activate %PLUGIN_NAME%

endlocal