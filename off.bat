@echo off
setlocal

:: Obtener el nombre del directorio actual
for %%I in (.) do set "PLUGIN_NAME=%%~nxI"

:: Desactivar el plugin
wp plugin deactivate %PLUGIN_NAME%

endlocal