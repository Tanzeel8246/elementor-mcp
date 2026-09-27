@echo off
title MindCrafts AI Plugin Builder
echo ===================================================
echo   MindCrafts AI for Elementor - Clean ZIP Builder
echo ===================================================
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "& { & '%~dp0build-zip.ps1' }"
echo.
echo ===================================================
pause
