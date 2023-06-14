@echo off

REM Obtener el nombre de la rama actual
for /f "delims=" %%i in ('git rev-parse --abbrev-ref HEAD') do set branch=%%i

REM Revertir el último commit
git revert HEAD

REM Hacer push al repositorio remoto
git push origin %branch%

echo Revertir commit completado y cambios enviados al repositorio remoto.
