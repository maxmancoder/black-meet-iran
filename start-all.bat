@echo off
REM Black Meet - start everything (run as normal user; Apache needs admin? usually fine)
set ROOT=C:\xampp\htdocs\black-meet
set TMP=C:\Users\BLACKL~1\AppData\Local\Temp\opencode

REM Apache (if not running)
netstat -an | findstr ":80 " | findstr "LISTEN" >nul || start "" "C:\xampp\apache\bin\httpd.exe"

REM MySQL (if not running)
netstat -an | findstr ":3306 " | findstr "LISTEN" >nul || start "" "C:\xampp\mysql\bin\mysqld.exe" --standalone

REM Node signaling server
start "" node "%ROOT%\server.js"
REM TURN server + TCP proxy
start "" node "%ROOT%\turn.js"
start "" node "%ROOT%\turn-proxy.js"
REM Public TURN tunnel (bore) with auto-restart + URL recorder
start "" powershell -NoLogo -WindowStyle Hidden -File "%ROOT%\keep-bore.ps1"
REM Public website tunnel (localhost.run) with auto-restart + URL recorder
start "" powershell -NoLogo -WindowStyle Hidden -File "%ROOT%\keep-tunnel.ps1"

echo Black Meet started. Website URL is written to: %ROOT%\tunnel_url.txt
echo TURN address is written to: %ROOT%\turn_host.txt
pause
