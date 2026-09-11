@echo off
REM Black Meet - open this file (double-click) to start the tunnels.
REM A window will stay open keeping the website + TURN tunnels alive.
REM Close that window to stop them.
cd /d "%~dp0"
title Black Meet - Tunnels
powershell -NoLogo -ExecutionPolicy Bypass -File "%~dp0tunnels.ps1"
